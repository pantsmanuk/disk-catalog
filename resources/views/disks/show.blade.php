@extends('layout')
@section('title', $disk->serial)
@section('content')
    <section class="detail-shell">
        <a class="back" href="{{ route('disks.index') }}">← Inventory</a>
        <header class="detail-header">
            <div><p class="eyebrow">{{ $disk->location_type === 'front' ? 'Front bay '.$disk->location : 'USB · '.$disk->location }}</p><h1>{{ $disk->model }}</h1><code>{{ $disk->serial }}</code></div>
            <div class="detail-badges"><span class="type-badge {{ strtolower($disk->interface) }}">{{ $disk->interface }}</span><span class="type-badge status-{{ $disk->status->value }}">{{ strtoupper($disk->status->label()) }}</span></div>
        </header>
        <dl class="detail-grid">
            <div><dt>Serial</dt><dd>{{ $disk->serial }}</dd></div>
            <div><dt>Capacity</dt><dd>{{ $disk->capacity ?: 'Not recorded' }}</dd></div>
            <div><dt>Current device</dt><dd>{{ $disk->device ?: 'Not mapped' }}</dd></div>
            <div><dt>Pool / vdev</dt><dd>{{ $disk->pool ?: 'Unassigned' }}</dd></div>
            <div class="wide"><dt>GPTID</dt><dd>{{ $disk->gptid ?: 'Not assigned' }}</dd></div>
            <div class="wide"><dt>Notes</dt><dd>{{ $disk->notes ?: 'No notes' }}</dd></div>
        </dl>
        <div class="detail-actions">
            <a class="button" href="{{ route('disks.edit', $disk) }}">Edit disk</a>
            <form method="POST" action="{{ route('disks.destroy', $disk) }}" onsubmit="return confirm('Remove {{ $disk->serial }} from inventory?')">@csrf @method('DELETE')<button class="danger">Remove</button></form>
        </div>
    </section>
@endsection
