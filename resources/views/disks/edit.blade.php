@extends('layout')
@section('title', 'Edit '.$disk->serial)
@section('content')
    <section class="form-shell">
        <p class="eyebrow">Inventory change / {{ $disk->serial }}</p>
        <h1>Edit disk</h1>
        <form method="POST" action="{{ route('disks.update', $disk) }}">@include('disks._form')</form>
    </section>
@endsection
