<?php

namespace Database\Seeders;

use App\DiskStatus;
use App\MarkdownInventory;
use App\Models\Disk;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $storage = Storage::disk('local');
        $markdown = $storage->exists('storage-audit.md')
            ? $storage->get('storage-audit.md')
            : file_get_contents(resource_path('storage-audit.example.md'));

        DB::transaction(function () use ($markdown): void {
            foreach (app(MarkdownInventory::class)->parse($markdown) as $disk) {
                $disk['status'] = collect(DiskStatus::cases())->first(
                    fn (DiskStatus $status): bool => str_starts_with(strtoupper($disk['notes']), strtoupper($status->label())),
                ) ?? DiskStatus::Active;
                Disk::query()->updateOrCreate(['serial' => $disk['serial']], $disk);
            }
        });
    }
}
