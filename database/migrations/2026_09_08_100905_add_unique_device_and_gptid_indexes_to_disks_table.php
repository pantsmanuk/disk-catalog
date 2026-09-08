<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $disks = DB::table('disks')
            ->select(['id', 'device', 'gptid'])
            ->orderBy('id')
            ->get()
            ->map(fn (stdClass $disk): array => [
                'id' => $disk->id,
                'device' => $this->normalizeIdentifier($disk->device),
                'gptid' => $this->normalizeIdentifier($disk->gptid),
            ]);

        foreach (['device', 'gptid'] as $identifier) {
            $seen = [];

            foreach ($disks as $disk) {
                $value = $disk[$identifier];

                if ($value === null) {
                    continue;
                }

                if (isset($seen[$value])) {
                    throw new RuntimeException("Cannot enforce unique {$identifier}: duplicate value {$value}");
                }

                $seen[$value] = true;
            }
        }

        foreach ($disks as $disk) {
            DB::table('disks')->where('id', $disk['id'])->update([
                'device' => $disk['device'],
                'gptid' => $disk['gptid'],
            ]);
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX disks_device_unique ON disks (LOWER(device))');
            DB::statement('CREATE UNIQUE INDEX disks_gptid_unique ON disks (LOWER(gptid))');

            return;
        }

        if (DB::getDriverName() === 'sqlsrv') {
            Schema::table('disks', function (Blueprint $table) {
                $table->computed('device_normalized', 'LOWER(device)')->persisted();
                $table->computed('gptid_normalized', 'LOWER(gptid)')->persisted();
            });

            DB::statement('CREATE UNIQUE INDEX disks_device_unique ON disks (device_normalized) WHERE device_normalized IS NOT NULL');
            DB::statement('CREATE UNIQUE INDEX disks_gptid_unique ON disks (gptid_normalized) WHERE gptid_normalized IS NOT NULL');

            return;
        }

        Schema::table('disks', function (Blueprint $table) {
            $table->string('device_normalized')->nullable()->storedAs('LOWER(device)');
            $table->string('gptid_normalized')->nullable()->storedAs('LOWER(gptid)');
            $table->unique('device_normalized', 'disks_device_unique');
            $table->unique('gptid_normalized', 'disks_gptid_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX disks_device_unique');
            DB::statement('DROP INDEX disks_gptid_unique');

            return;
        }

        Schema::table('disks', function (Blueprint $table) {
            $table->dropUnique('disks_device_unique');
            $table->dropUnique('disks_gptid_unique');
            $table->dropColumn(['device_normalized', 'gptid_normalized']);
        });
    }

    private function normalizeIdentifier(?string $value): ?string
    {
        $value = trim($value ?? '');

        if (! mb_check_encoding($value, 'ASCII')) {
            throw new RuntimeException('Cannot enforce unique disk identifiers containing non-ASCII characters.');
        }

        $value = strtolower($value);

        return $value === '' ? null : $value;
    }
};
