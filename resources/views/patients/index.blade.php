@extends('layouts.app')

@php
    $title = 'Patient Management';
@endphp

@section('content')
    <livewire:patients.index />
@endsection
