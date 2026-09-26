@extends('core::print.id-card-layout', [
    'title' => __('Staff ID cards (:count)', ['count' => count($cards)]),
    'wrapperClass' => 'print-sheet',
])

@section('content')
    @foreach ($cards as $card)
        @include('staff::print.partials.id-card', $card)
    @endforeach
@endsection
