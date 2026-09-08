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

        Schema::table('disks', function (Blueprint $table) {
            $table->unique('device');
            $table->unique('gptid');
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
            $table->dropUnique(['device']);
            $table->dropUnique(['gptid']);
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
