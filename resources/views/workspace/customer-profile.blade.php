@extends('layouts.workspace')

@section('content')
    <livewire:workspace.customer-profile :customer-id="$customerId" />
@endsection
