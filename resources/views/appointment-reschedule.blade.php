@extends('layouts.app')

@section('content')
    <livewire:public-appointment-reschedule :token="$token" />
@endsection
