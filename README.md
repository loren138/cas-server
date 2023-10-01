# Archived

Feel free to fork/clone/copy this for your own uses (and update to more recent versions of Laravel)

# Laravel CAS Server

## Todo
- Add SAML/Warn
- Unit test flashing an error to session

This is a Laravel implementation of the CAS protocol because tomcat/CAS are way to
hard to deal with and keep up to date.  This should be much easier and much easier
to understand.

Because of the required URLs for CAS, it is recommended that you install this
in it's own Laravel instance.

Laravel installation instructions can be found here:
https://laravel.com/docs/5.2/installation

## Install

    composer require loren138/cas-server

After updating composer, add the service provider to the providers array in config/app.php

    Loren138\CASServer\CASServerServiceProvider::class,

Next, you'll want to publish the views, public files, config, and migrations.

    php artisan vendor:publish --provider="Loren138\CASServer\CASServerServiceProvider"

Next, you'll need to implement your user authentication class.
The class for users must Implement Loren138\CASServer\Models\CASUserInterface

     use Loren138\CASServer\Models\CASUserInterface;
     use Illuminate\Database\Eloquent\Model;

     class MyUser extends Model implements CASUserInterface
     {
          public function checkLogin($username, $password)
          {
                if (loginGood) {
                    return true;
                }
                return false;
          }
          public function userAttributes($username);
          {
                return [
                    'attribute' => 'value'
                ];
          }
     }

Last, update the ``casserver.php`` file in the config folder such that the userClass 
setting references your class (and update other settings as desired).

You should now have a working CAS Server.

## Table Cleanup

It is recommended that you have CAS cleanup the authentication and ticket tables daily.
You can do that by adding the following in the schedule function in your
``console/Kernel.php`` file

    $schedule->command('casserver:cleanup')->daily();
    
Note: For this to work, you must have setup a cron job to call Laravel's command:
https://laravel.com/docs/5.2/scheduling#introduction

## Session configuration

It is recommended to verify/set the following in ``config/session.php``:
(These settings are toward the bottom of the file.)

    'secure'    => true,
    'http_only' => true,
    'expire_on_close' => true,

It is also recommended to change the cookie name in ``config/session.php``:

    'cookie' => 'cas_session',

You should also make sure the ``lifetime`` variable is at least as long as your
sso lifetime in ``casserver.php`` in ``config/session.php`` by default,
you'll want to set session lifetime to ``480``.

## Catching CSRF Errors

In the render (not report) function of ``app/Exceptions/Handler.php``, you
may want to add the following:

    if ($e instanceof TokenMismatchException) {
        session()->flash('error', 'Validation Token expired. Please try again.');
        return redirect()->back();
    }

## SSL

Single Sign on Sessions are only stored if you are using SSL.
Thus, you may want to force SSL.  You can do this on your web
server or with a middleware such as: https://github.com/lesstif/laravel-secure-url

You can also use modrewrite

    # This will enable the Rewrite capabilities
    RewriteEngine On

    # This checks to make sure the connection is not already HTTPS
    RewriteCond %{HTTPS} !=on

    # This rule will redirect users from their original location, to the same location but using HTTPS.
    # i.e.  http://www.example.com/foo/ to https://www.example.com/foo/
    # The leading slash is made optional so that this will work either in httpd.conf
    # or .htaccess context
    RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1 [R,L]

## Server Install

Testing requires sqlite.
