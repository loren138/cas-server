@extends('casserver::layout')
@section('content')
    <div class="card success">
        <div class="service">
            <h2 class="title">Successfully Logged In</h2>
            <p>You are logged in as {{$user}}.</p>
            <p>To keep your information secure, <a href="/logout">log out</a> and close your browser when you finish using this service.</p>
        </div>
    </div>
@endsection