@extends('casserver::layout')
@section('content')
    <div class="card">
        @if ($serviceObject)
            <div class="service">
                <h1 class="title">Login to {{ $serviceObject['name'] }}</h1>
                <p>{{ $serviceObject['description'] }}</p>
            </div>
        @else
            <div class="service">
                <h1 class="title">Login to CAS</h1>
            </div>
        @endif
        @if (!$secure)
            <div class="error">
                <h2 class="title">Non-secure Connection</h2>
                <p>
                    Since your connection is not secure, single sign on has been disabled.
                    Please use a secure (HTTPS) connection.
                </p>
            </div>
        @endif

        <div id="cookiesDisabled" class="error" style="display:none;">
            <h2 class="title">Cookies are Disabled</h2>
            <p>Since your browser is not accepting cookies, single sign on will not work.</p>
        </div>
        <form method="POST" action="{{ url('login') }}" accept-charset="UTF-8" autocomplete="off"><input name="_token" type="hidden" value="oYOPrgrkBzQvCajVE4zOxsI0dmp1fevjlZlD9JeD">

            <div class="input-container">
                <input required="required" tabindex="1" accesskey="u" size="25" autocomplete="off" name="username" type="text" value="">
                <label for="username">Username</label>
                <div class="bar"></div>
            </div>

            <div class="input-container no-bottom-margin">
                <input required="required" tabindex="2" accesskey="p" size="25" autocomplete="off" name="password" type="password" value="">
                <label for="password">Password</label>
                <div class="bar"></div>
            </div>
            <div class="input-container">
                <p id="capsOn" style="display:none;">Warning: Caps lock is on!</p>
            </div>

            <!--
            These features were not implemented in Laravel CAS Server 1.0
                    <input id="warn" name="warn" value="true" tabindex="3" accesskey="w" type="checkbox" />
                    <label for="warn"><span class="accesskey">W</span>arn me before logging me into other sites.</label>
                    <br/>
                    <input id="publicWorkstation" name="publicWorkstation" value="false" tabindex="4" type="checkbox" />
                    <label for="publicWorkstation">I am at a public workstation.</label>
                    <br/>
                    <input type="checkbox" name="rememberMe" id="rememberMe" value="true" tabindex="5"  />
                    <label for="rememberMe">Remember Me</label>
            -->

            <div class="button-container">
                <button type="submit"><span>Login</span></button>
            </div>
        </form>
        <div class="footer"><a href="#">Forgot your password?</a></div>
        <div class="footer2">To keep your information secure, <a href="/logout">log out</a> and close your browser when you finish using this service.</div>
    </div>
@endsection