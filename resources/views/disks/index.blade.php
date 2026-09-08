@extends('layout')

@section('title', 'Inventory')

@section('content')
    @php
        $occupied = $frontDisks->count();
        $hotSpares = $frontDisks->where('status', \App\DiskStatus::HotSpare)->count();
        $warmSpares = $frontDisks->where('status', \App\DiskStatus::WarmSpare)->count();
        $faulted = $frontDisks->where('status', \App\DiskStatus::Faulted)->count();
        $shelfRows = config('disk.shelf.rows');
        $shelfColumns = config('disk.shelf.columns');
        $shelfSlots = config('disk.shelf.slots');
        $slotDigits = max(2, strlen((string) $shelfSlots));
    @endphp

    <section class="hero">
        <div>
            <p class="eyebrow">Storage inventory / live record</p>
            <h1>Disk shelf <span>at a glance.</span></h1>
        </div>
        <dl class="metrics">
            <div><dt>Occupied</dt><dd>{{ $occupied }}<small>/{{ $shelfSlots }}</small></dd></div>
            <div><dt>Hot spare</dt><dd>{{ $hotSpares }}</dd></div>
            <div><dt>Warm spare</dt><dd>{{ $warmSpares }}</dd></div>
            <div><dt>Faulted</dt><dd>{{ $faulted }}</dd></div>
        </dl>
    </section>

    <section class="panel">
        <header class="panel-heading">
            <div><p class="eyebrow">Front enclosure · {{ $shelfRows }} × {{ $shelfColumns }}</p><h2>Bays {{ str_pad(1, $slotDigits, '0', STR_PAD_LEFT) }}–{{ str_pad($shelfSlots, $slotDigits, '0', STR_PAD_LEFT) }}</h2></div>
            <div class="legend" aria-label="Disk status legend"><span class="active">Active</span><span class="hot-spare">Hot spare</span><span class="warm-spare">Warm spare</span><span class="faulted">Faulted</span><span class="empty">Empty</span></div>
        </header>
        <div class="shelf-frame">
            <div @class(['shelf', 'auto-fit' => config('disk.shelf.auto_fit')]) style="--shelf-columns: {{ $shelfColumns }}" aria-label="Front disk bays">
                @foreach (range(1, $shelfSlots) as $slot)
                    @php($disk = $frontDisks->get((string) $slot))
                    @if ($disk)
                        <a class="bay {{ strtolower($disk->interface) }} status-{{ $disk->status->value }}" href="{{ route('disks.show', $disk) }}" aria-label="Bay {{ $slot }}, {{ $disk->status->label() }}, {{ $disk->serial }}">
                            <span class="bay-slot">{{ str_pad($slot, $slotDigits, '0', STR_PAD_LEFT) }}</span>
                            <span class="bay-led"></span>
                            <strong>{{ $disk->capacity ?: '—' }}</strong>
                            @if ($disk->status !== \App\DiskStatus::Active)<span class="status-label">{{ strtoupper($disk->status->label()) }}</span>@endif
                            <small>{{ $disk->device ?: 'unmapped' }}</small>
                            <code>{{ $disk->serial }}</code>
                        </a>
                    @else
                        <a class="bay empty" href="{{ route('disks.create', ['type' => 'front', 'slot' => $slot]) }}">
                            <span class="bay-slot">{{ str_pad($slot, $slotDigits, '0', STR_PAD_LEFT) }}</span>
                            <span class="bay-led"></span>
                            <strong>EMPTY</strong>
                            <small>available bay</small>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
        <p class="orientation">← LEFT · viewed facing server · RIGHT →</p>
    </section>

    <section class="panel usb-panel">
        <header class="panel-heading">
            <div><p class="eyebrow">External media</p><h2>USB devices</h2></div>
            <a class="text-link" href="{{ route('disks.create', ['type' => 'usb']) }}">+ Add USB device</a>
        </header>
        <div class="usb-grid">
            @forelse ($usbDisks as $disk)
                <a class="usb-device status-{{ $disk->status->value }}" href="{{ route('disks.show', $disk) }}">
                    <span class="usb-icon">USB</span>
                    <span><small>{{ strtoupper($disk->location) }}</small><strong>{{ $disk->model }}</strong><code>{{ $disk->serial }}</code>@if ($disk->status !== \App\DiskStatus::Active)<em class="device-status">{{ strtoupper($disk->status->label()) }}</em>@endif</span>
                    <b>{{ $disk->capacity ?: '—' }}</b>
                </a>
            @empty
                <p class="empty-state">No USB devices recorded.</p>
            @endforelse
        </div>
    </section>
@endsection
