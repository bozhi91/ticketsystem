@extends('emails.layout')

@section('content')
    {!! empty($content) ? '' : nl2br($content) !!}
@endsection
