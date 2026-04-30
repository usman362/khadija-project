@extends('errors._layout')

@section('err-code', '500')
@section('err-emoji', '🛠️')
@section('err-title')
    Something <span class="grad">broke on our end</span>
@endsection
@section('err-tagline', 'This one\'s on us — our team has been notified and we\'re looking into it. Please try again in a moment, or head home to keep moving.')
