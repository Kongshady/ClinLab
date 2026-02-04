@extends('layouts.app')

@php
    $title = 'Employee Management';
@endphp

@section('content')
    <livewire:employees.index />
@endsection
