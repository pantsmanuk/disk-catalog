<?php

namespace App;

use App\Models\Disk;
use Illuminate\Support\Collection;

class MarkdownInventory
{
    /** @return array<int, array<string, string>> */
    public function parse(string $markdown): array
    {
        preg_match('/## Storage inventory\R\R(.*?)(?=\R## USB devices)/s', $markdown, $storage);
        preg_match('/## USB devices\R\R(.*?)(?=\R## Post-reboot)/s', $markdown, $usb);

        return [
            ...$this->parseTable($storage[1] ?? '', 'front'),
            ...$this->parseTable($usb[1] ?? '', 'usb'),
        ];
    }

    /** @param Collection<int, Disk> $disks */
    public function render(string $markdown, Collection $disks): string
    {
        $front = $disks->where('location_type', 'front')->keyBy('location');
        $storageRows = collect(range(1, config('disk.shelf.slots')))->map(function (int $slot) use ($front): string {
            $disk = $front->get((string) $slot);

            return $this->row([
                (string) $slot, $disk?->gptid, $disk?->device, $disk?->serial,
                $disk?->model, $disk?->capacity, $disk?->interface, $disk?->pool,
                $disk ? $this->notes($disk) : 'Empty',
            ]);
        })->implode("\n");

        $usbRows = $disks->where('location_type', 'usb')->sortBy('location')
            ->map(fn (Disk $disk): string => $this->row([
                $disk->location, $disk->gptid, $disk->device, $disk->serial,
                $disk->model, $disk->capacity, $disk->interface, $disk->pool, $this->notes($disk),
            ]))->implode("\n");

        $storageTable = "| Slot | gptid | daX | Serial | Model | Capacity | Type | Pool/Vdev | Notes |\n"
            ."| --- | --- | --- | --- | --- | --- | --- | --- | --- |\n{$storageRows}\n";
        $usbTable = "| Location | gptid | daX | Serial | Model | Capacity | Type | Pool | Notes |\n"
            ."| --- | --- | --- | --- | --- | --- | --- | --- | --- |\n{$usbRows}\n";

        $markdown = preg_replace_callback(
            '/(## Storage inventory\R\R).*?(?=\R## USB devices)/s',
            fn (array $matches): string => $matches[1].$storageTable,
            $markdown,
        );

        return preg_replace_callback(
            '/(## USB devices\R\R).*?(?=\R## Post-reboot)/s',
            fn (array $matches): string => $matches[1].$usbTable,
            $markdown,
        ) ?? $markdown;
    }

    /** @return array<int, array<string, string>> */
    private function parseTable(string $table, string $locationType): array
    {
        $rows = array_slice(preg_split('/\R/', trim($table)) ?: [], 2);

        return collect($rows)->map(function (string $row) use ($locationType): array {
            $values = array_map('trim', explode('|', trim($row, '|')));

            return [
                'location_type' => $locationType,
                'location' => $values[0] ?? '',
                'gptid' => $values[1] ?? '',
                'device' => $values[2] ?? '',
                'serial' => $values[3] ?? '',
                'model' => $values[4] ?? '',
                'capacity' => $values[5] ?? '',
                'interface' => $values[6] ?? '',
                'pool' => $values[7] ?? '',
                'notes' => $values[8] ?? '',
            ];
        })->filter(fn (array $row): bool => $row['serial'] !== '')->values()->all();
    }

    /** @param array<int, string|null> $values */
    private function row(array $values): string
    {
        $values = array_map(fn (?string $value): string => str_replace('|', '\\|', $value ?? ''), $values);

        return '| '.implode(' | ', $values).' |';
    }

    private function notes(Disk $disk): ?string
    {
        if ($disk->status === DiskStatus::Active) {
            return $disk->notes;
        }

        $status = strtoupper($disk->status->label());

        if (str_contains(strtoupper($disk->notes ?? ''), $status)) {
            return $disk->notes;
        }

        return $status.($disk->notes ? '. '.$disk->notes : '');
    }
}
