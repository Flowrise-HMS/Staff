@extends('core::print.id-card-layout', [
    'title' => $staff->full_name.' – '.__('Staff ID'),
])

@section('content')
    @include('staff::print.partials.id-card')
@endsection
