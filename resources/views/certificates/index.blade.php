@php
    $title = 'Certificates Management';
@endphp

@extends('layouts.app')

@section('content')
    @include('certificates._tabs')
    <livewire:certificates.index />
@endsection
