@extends('layouts.workspace')

@section('content')
    <livewire:workspace.appointment-detail :appointment-id="$appointmentId" />
@endsection
