@extends('casserver::layout')
@section('content')
<div class="card error">
    <div class="error">
        <h2 class="title">{{ $errorTitle }}</h2>
        <p>{{$error}}</p>
    </div>
</div>
@endsection