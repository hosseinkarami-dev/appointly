@extends('layouts.app')

@section('content')
    <livewire:public-appointment-lookup :token="$token" />
@endsection
