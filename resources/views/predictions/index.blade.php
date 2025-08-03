@extends('layouts.app')



@section('title', 'Stock Predictions')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Header Component -->
    @include('predictions.header')

    <!-- Worker Status Component -->
    {{-- @include('predictions.worker-status') --}}

    <!-- Prediction Form Component -->
    @include('predictions.form')

    <!-- Results Component -->
    @include('predictions.results')

</div>

<!-- Styles Component -->
{{-- @include('predictions.styles') --}}

<!-- JavaScript Component -->

@endsection
