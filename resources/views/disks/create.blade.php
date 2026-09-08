@extends('layout')
@section('title', 'Add disk')
@section('content')
    <section class="form-shell">
        <p class="eyebrow">Inventory change</p>
        <h1>Add disk</h1>
        <p>Record durable identity first. Device mapping can change after reboot.</p>
        <form method="POST" action="{{ route('disks.store') }}">@include('disks._form')</form>
    </section>
@endsection
