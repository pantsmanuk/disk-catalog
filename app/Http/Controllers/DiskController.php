<?php

namespace App\Http\Controllers;

use App\DiskStatus;
use App\MarkdownInventory;
use App\Models\Disk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiskController extends Controller
{
    public function index(): View
    {
        $disks = Disk::query()->orderBy('location')->get();

        return view('disks.index', [
            'frontDisks' => $disks->where('location_type', 'front')->keyBy('location'),
            'usbDisks' => $disks->where('location_type', 'usb'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('disks.create', [
            'disk' => new Disk(['location_type' => $request->string('type')->value() ?: 'front']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $disk = Disk::query()->create($this->validated($request));

        return to_route('disks.show', $disk)->with('status', 'Disk added to inventory.');
    }

    public function show(Disk $disk): View
    {
        return view('disks.show', compact('disk'));
    }

    public function edit(Disk $disk): View
    {
        return view('disks.edit', compact('disk'));
    }

    public function update(Request $request, Disk $disk): RedirectResponse
    {
        $disk->update($this->validated($request, $disk));

        return to_route('disks.show', $disk)->with('status', 'Disk details updated.');
    }

    public function destroy(Disk $disk): RedirectResponse
    {
        $disk->delete();

        return to_route('disks.index')->with('status', 'Disk removed from inventory.');
    }

    public function export(MarkdownInventory $inventory): Response
    {
        $storage = Storage::disk('local');
        $source = $storage->exists('storage-audit.md')
            ? $storage->get('storage-audit.md')
            : file_get_contents(resource_path('storage-audit.example.md'));
        $markdown = $inventory->render($source, Disk::all());

        return response($markdown, 200, [
            'Content-Disposition' => 'attachment; filename="storage-audit-'.now()->format('Y-m-d').'.md"',
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Disk $disk = null): array
    {
        $locationType = $request->string('location_type')->value();

        return $request->validate([
            'location_type' => ['required', Rule::in(['front', 'usb'])],
            'location' => [
                'required',
                'string',
                'max:40',
                Rule::when($locationType === 'front', Rule::in(array_map('strval', range(1, 24)))),
                Rule::unique('disks')->where('location_type', $locationType)->ignore($disk),
            ],
            'gptid' => ['nullable', 'string', 'max:100'],
            'device' => ['nullable', 'string', 'max:40'],
            'serial' => ['required', 'string', 'max:100', Rule::unique('disks')->ignore($disk)],
            'model' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'string', 'max:40'],
            'interface' => ['required', Rule::in($locationType === 'usb' ? ['USB'] : ['SATA', 'SAS'])],
            'pool' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(DiskStatus::class)],
        ]);
    }
}
