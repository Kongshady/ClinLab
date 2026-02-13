@extends('layouts.app')

@section('content')
    @include('certificates._tabs')
    <livewire:certificates.issued.index />
@endsection
