<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
@if (array_key_exists('authenticationSuccess', $serviceResponse))
    <cas:authenticationSuccess>
        <cas:user>{{$serviceResponse['authenticationSuccess']['user']}}</cas:user>
        @if (array_key_exists('attributes', $serviceResponse['authenticationSuccess']))
        <cas:attributes>
            @foreach ($serviceResponse['authenticationSuccess']['attributes'] as $k => $v)
                @if (is_array($v))
                    @foreach ($v as $v2)
                        <cas:{{$k}}>{{$v2}}</cas:{{$k}}>
                    @endforeach
                @else
                    <cas:{{$k}}>{{$v}}</cas:{{$k}}>
                @endif
            @endforeach
        </cas:attributes>
        @endif
        @if ($serviceResponse['authenticationSuccess']['proxyGrantingTicket'])
            <cas:proxyGrantingTicket>{{$serviceResponse['authenticationSuccess']['proxyGrantingTicket']}}</cas:proxyGrantingTicket>
        @else
            <cas:proxyGrantingTicket/>
        @endif
    </cas:authenticationSuccess>
@else
    <cas:authenticationFailure code="{{$serviceResponse['authenticationFailure']['code']}}">
        {{$serviceResponse['authenticationFailure']['description']}}
    </cas:authenticationFailure>
@endif
</cas:serviceResponse>