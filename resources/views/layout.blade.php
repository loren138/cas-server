<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laravel CAS Server</title>

    <link rel="stylesheet" href="/vendor/casserver/reset.css">
    <link rel='stylesheet prefetch' href='//fonts.googleapis.com/css?family=Roboto:400,100,300,500,700,900|RobotoDraft:400,100,300,500,700,900'>
    <link rel='stylesheet prefetch' href='//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css'>
    <link rel="stylesheet" href="/vendor/casserver/casserver.css">




</head>

<body>

<div class="pen-title">
    <h1>CAS Server</h1>
</div>

<div class="container">
    <div class="card"></div>
    @yield('content')
</div> <!-- END #container -->
<script>
    (function() {
        // Check if cookies are enabled
        if (!("cookie" in document && (document.cookie.length > 0 ||
        (document.cookie = "test").indexOf.call(document.cookie, "test") > -1))) {
            var cookieDiv = document.getElementById("cookiesDisabled")
            if (cookieDiv) {
                cookieDiv.style.display = 'block';
            }
        }
        var capsDiv = document.getElementById("capsOn")

        document.onkeypress = function ( e ) {
            e = (e) ? e : window.event;

            var kc = ( e.keyCode ) ? e.keyCode : e.which; // get keycode
            var isUp = !!(kc >= 65 && kc <= 90); // uppercase
            var isLow = !!(kc >= 97 && kc <= 122); // lowercase
            var isShift = ( e.shiftKey ) ? e.shiftKey : ( (kc == 16) ); // shift is pressed -- works for IE8-

            // uppercase w/out shift or lowercase with shift == caps lock
            if (capsDiv) {
                if ((isUp && !isShift) || (isLow && isShift)) {
                    capsDiv.style.display = 'block';
                } else {
                    capsDiv.style.display = 'none';
                }
            }

        }
    })();
</script>
</body>
</html>