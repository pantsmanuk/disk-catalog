@csrf
@if ($disk->exists) @method('PUT') @endif

@if ($errors->any())
    <div class="errors" role="alert"><strong>Check {{ $errors->count() }} field(s).</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="form-grid">
    <label>Location type
        <select name="location_type" required>
            <option value="front" @selected(old('location_type', $disk->location_type) === 'front')>Front bay</option>
            <option value="usb" @selected(old('location_type', $disk->location_type) === 'usb')>USB device</option>
        </select>
    </label>
    <label>Slot or location
        <input name="location" value="{{ old('location', $disk->location ?: request('slot')) }}" maxlength="40" placeholder="1–24, Upper, Lower…" required>
    </label>
    <label>Interface
        <select name="interface" required>
            @foreach (['SAS', 'SATA', 'USB'] as $interface)
                <option @selected(old('interface', $disk->interface ?: ($disk->location_type === 'usb' ? 'USB' : 'SAS')) === $interface)>{{ $interface }}</option>
            @endforeach
        </select>
    </label>
    <label>Capacity
        <input name="capacity" value="{{ old('capacity', $disk->capacity) }}" maxlength="40" placeholder="2.4TB">
    </label>
    <label class="wide">Serial number <span>authoritative identity</span>
        <input name="serial" value="{{ old('serial', $disk->serial) }}" maxlength="100" required autofocus>
    </label>
    <label class="wide">Model
        <input name="model" value="{{ old('model', $disk->model) }}" maxlength="100" required>
    </label>
    <label>Current device
        <input name="device" value="{{ old('device', $disk->device) }}" maxlength="40" placeholder="da0">
    </label>
    <label>Pool / vdev
        <input name="pool" value="{{ old('pool', $disk->pool) }}" maxlength="100" placeholder="example-pool">
    </label>
    <label class="wide">GPTID
        <input name="gptid" value="{{ old('gptid', $disk->gptid) }}" maxlength="100">
    </label>
    <label class="wide">Notes
        <textarea name="notes" maxlength="1000" rows="4">{{ old('notes', $disk->notes) }}</textarea>
    </label>
    <fieldset class="status-field wide">
        <legend>Disk status</legend>
        <div class="status-options">
            @foreach (\App\DiskStatus::cases() as $status)
                <label class="status-option {{ $status->value }}">
                    <input type="radio" name="status" value="{{ $status->value }}" required @checked(old('status', $disk->status?->value ?? \App\DiskStatus::Active->value) === $status->value)>
                    <span><strong>{{ $status->label() }}</strong>{{ match ($status) { \App\DiskStatus::Active => 'In service', \App\DiskStatus::HotSpare => 'Array-ready', \App\DiskStatus::WarmSpare => 'Nearline reserve', \App\DiskStatus::Faulted => 'Do not use' } }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>
</div>
<div class="form-actions">
    <button type="submit">{{ $disk->exists ? 'Save changes' : 'Add to inventory' }}</button>
    <a href="{{ $disk->exists ? route('disks.show', $disk) : route('disks.index') }}">Cancel</a>
</div>
