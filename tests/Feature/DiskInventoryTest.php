<?php

namespace Tests\Feature;

use App\DiskStatus;
use App\Models\Disk;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DiskInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_markdown_inventory_seeds_and_appears_on_index(): void
    {
        Storage::fake('local');

        $this->seed();

        $this->assertDatabaseCount('disks', 3);
        $this->assertDatabaseHas('disks', ['serial' => 'SERIAL-EXAMPLE-002', 'status' => DiskStatus::Faulted->value]);
        $this->assertDatabaseHas('disks', ['serial' => 'SERIAL-EXAMPLE-USB-001', 'status' => DiskStatus::WarmSpare->value]);
        $this->get(route('disks.index'))
            ->assertOk()
            ->assertSee('SERIAL-EXAMPLE-001')
            ->assertSee('Example USB Disk')
            ->assertSeeInOrder(['bay sas status-active', 'SERIAL-EXAMPLE-001', 'bay sata status-faulted', 'SERIAL-EXAMPLE-002'], false);
    }

    public function test_private_markdown_inventory_takes_precedence_over_example(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(
            'storage-audit.md',
            str_replace('SERIAL-EXAMPLE-001', 'SERIAL-PRIVATE-001', file_get_contents(resource_path('storage-audit.example.md'))),
        );

        $this->seed();

        $this->assertDatabaseHas('disks', ['serial' => 'SERIAL-PRIVATE-001']);
        $this->assertDatabaseMissing('disks', ['serial' => 'SERIAL-EXAMPLE-001']);
    }

    public function test_disk_can_be_created_updated_and_removed(): void
    {
        $response = $this->post(route('disks.store'), $this->diskData());
        $disk = Disk::query()->where('serial', 'NEW-SERIAL')->firstOrFail();
        $response->assertRedirect(route('disks.show', $disk));

        $this->put(route('disks.update', $disk), [
            ...$this->diskData(),
            'notes' => 'Warm spare',
            'status' => DiskStatus::WarmSpare->value,
        ])->assertRedirect(route('disks.show', $disk));
        $this->assertDatabaseHas('disks', [
            'serial' => 'NEW-SERIAL',
            'notes' => 'Warm spare',
            'status' => DiskStatus::WarmSpare->value,
        ]);

        $this->delete(route('disks.destroy', $disk))->assertRedirect(route('disks.index'));
        $this->assertDatabaseMissing('disks', ['serial' => 'NEW-SERIAL']);
    }

    public function test_duplicate_device_and_gptid_are_rejected_when_creating(): void
    {
        Disk::query()->create([
            ...$this->diskData(),
            'location' => '1',
            'serial' => 'EXISTING-SERIAL',
        ]);

        $this->from(route('disks.create'))
            ->post(route('disks.store'), [
                ...$this->diskData(),
                'location' => '2',
                'serial' => 'DUPLICATE-IDENTIFIERS',
                'device' => ' DA24 ',
                'gptid' => 'TEST-GPTID',
            ])
            ->assertRedirect(route('disks.create'))
            ->assertSessionHasErrors(['device', 'gptid']);

        $this->assertDatabaseMissing('disks', ['serial' => 'DUPLICATE-IDENTIFIERS']);
    }

    public function test_duplicate_device_and_gptid_are_rejected_when_updating(): void
    {
        Disk::query()->create([
            ...$this->diskData(),
            'location' => '1',
            'serial' => 'FIRST-SERIAL',
            'device' => 'da1',
            'gptid' => 'first-gptid',
        ]);
        $disk = Disk::query()->create([
            ...$this->diskData(),
            'location' => '2',
            'serial' => 'SECOND-SERIAL',
            'device' => 'da2',
            'gptid' => 'second-gptid',
        ]);

        $this->from(route('disks.edit', $disk))
            ->put(route('disks.update', $disk), [
                ...$this->diskData(),
                'location' => '2',
                'serial' => 'SECOND-SERIAL',
                'device' => 'DA1',
                'gptid' => ' FIRST-GPTID ',
            ])
            ->assertRedirect(route('disks.edit', $disk))
            ->assertSessionHasErrors(['device', 'gptid']);

        $this->assertDatabaseHas('disks', [
            'id' => $disk->id,
            'device' => 'da2',
            'gptid' => 'second-gptid',
        ]);
    }

    public function test_multiple_disks_may_have_blank_device_and_gptid(): void
    {
        foreach ([1, 2] as $slot) {
            $this->post(route('disks.store'), [
                ...$this->diskData(),
                'location' => (string) $slot,
                'serial' => "BLANK-IDENTIFIERS-{$slot}",
                'device' => ' ',
                'gptid' => '',
            ])->assertRedirect();
        }

        $this->assertSame(2, Disk::query()->whereNull('device')->whereNull('gptid')->count());
    }

    public function test_device_and_gptid_must_be_ascii(): void
    {
        $this->from(route('disks.create'))
            ->post(route('disks.store'), [
                ...$this->diskData(),
                'device' => 'dä1',
                'gptid' => 'gptïd',
            ])
            ->assertRedirect(route('disks.create'))
            ->assertSessionHasErrors(['device', 'gptid']);

        $this->assertDatabaseMissing('disks', ['serial' => 'NEW-SERIAL']);
    }

    /** @param 'device'|'gptid' $identifier */
    #[DataProvider('uniqueIdentifierProvider')]
    public function test_database_rejects_duplicate_identifiers(string $identifier): void
    {
        $firstDisk = [
            ...$this->diskData(),
            'location' => '1',
            'serial' => 'FIRST-SERIAL',
            'device' => 'da1',
            'gptid' => 'first-gptid',
        ];
        DB::table('disks')->insert($firstDisk);

        $this->expectException(QueryException::class);

        DB::table('disks')->insert([
            ...$this->diskData(),
            'location' => '2',
            'serial' => 'SECOND-SERIAL',
            'device' => 'da2',
            'gptid' => 'second-gptid',
            $identifier => strtoupper($firstDisk[$identifier]),
        ]);
    }

    public static function uniqueIdentifierProvider(): array
    {
        return [
            'device' => ['device'],
            'gptid' => ['gptid'],
        ];
    }

    public function test_duplicate_import_rolls_back_all_disk_changes(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(
            'storage-audit.md',
            str_replace('example-gptid-002', 'example-gptid-001', file_get_contents(resource_path('storage-audit.example.md'))),
        );

        try {
            $this->seed();
            $this->fail('Expected duplicate GPTID to fail import.');
        } catch (QueryException) {
            $this->assertDatabaseCount('disks', 0);
        }
    }

    public function test_export_replaces_inventory_and_preserves_rest_of_audit(): void
    {
        Storage::fake('local');
        Disk::query()->create([
            ...$this->diskData(),
            'status' => DiskStatus::HotSpare,
        ]);

        $this->get(route('inventory.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/markdown; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename="storage-audit-'.now()->format('Y-m-d').'.md"')
            ->assertSee('# Storage Audit Example', false)
            ->assertSee('| 24 |', false)
            ->assertSee('NEW-SERIAL', false)
            ->assertSee('HOT SPARE', false)
            ->assertSee('Add local audit procedures', false);
    }

    public function test_each_disk_status_is_identified_on_the_index(): void
    {
        foreach (DiskStatus::cases() as $index => $status) {
            Disk::query()->create([
                ...$this->diskData(),
                'location' => (string) ($index + 1),
                'serial' => 'STATUS-'.$status->value,
                'device' => 'status-device-'.$index,
                'gptid' => 'status-gptid-'.$index,
                'status' => $status,
            ]);
        }

        $this->get(route('disks.index'))
            ->assertOk()
            ->assertSee('status-active', false)
            ->assertSee('status-hot-spare', false)
            ->assertSee('HOT SPARE')
            ->assertSee('status-warm-spare', false)
            ->assertSee('WARM SPARE')
            ->assertSee('status-faulted', false)
            ->assertSee('FAULTED');
    }

    public function test_disk_status_must_be_valid(): void
    {
        $this->from(route('disks.create'))
            ->post(route('disks.store'), [...$this->diskData(), 'status' => 'offline'])
            ->assertRedirect(route('disks.create'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('disks', ['serial' => 'NEW-SERIAL']);
    }

    /** @return array<string, string> */
    private function diskData(): array
    {
        return [
            'location_type' => 'front',
            'location' => '24',
            'gptid' => 'test-gptid',
            'device' => 'da24',
            'serial' => 'NEW-SERIAL',
            'model' => 'TEST-MODEL',
            'capacity' => '4TB',
            'interface' => 'SATA',
            'pool' => 'spares',
            'notes' => '',
            'status' => DiskStatus::Active->value,
        ];
    }
}
