<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Disk Shelf') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('disks.index') }}">
            <span class="brand-mark">{{ config('disk.shelf.slots') }}</span>
            <span><strong>{{ config('app.name') }}</strong><small>physical disk catalogue</small></span>
        </a>
        <nav aria-label="Inventory actions">
            <a href="{{ route('disks.create') }}">+ Add disk</a>
            <a class="export" href="{{ route('inventory.export') }}">Export .md</a>
        </nav>
    </header>

    @if (session('status'))
        <div class="flash" role="status">{{ session('status') }}</div>
    @endif

    <main>@yield('content')</main>
    <footer>Serial number is authoritative. Confirm carrier before removal.</footer>
</body>
</html>
