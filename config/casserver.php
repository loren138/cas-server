<?php

return [
    // The class for users
    // Must Implement Loren138\CASServer\Models\CASUserInterface
    /* IE

     use Loren138\CASServer\Models\CASUserInterface

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
     */

    'userClass' => App\Models\YourUser::class,

    /*
     * Disables access to CAS without using SSL
     *
     * If using this, please use mod-rewrite or another method to ensure non-ssl requests
     * are sent to SSL.
     *
     * Default: false (recommended to be set to true for production given the above mod-rewrite usage)
     */
    'disableNonSSL' => env('CASSERVER_DISABLENONSSL', false),

    'logout' => [
        /*
         * Per the CAS Protocol, the /logout endpoint is responsible for destroying the
         * current SSO session. Upon logout, it may also be desirable to redirect back
         * to a service. This is controlled via specifying the redirect link via the
         * service parameter. The specified service must be registered in the service
         * registry of CAS and enabled and CAS must be allowed to follow service
         * redirects.
         *
         * 'FollowServiceRedirects' enables or disables the redirect functionality
         * It defaults to false
         */

        // Should default false
        'followServiceRedirects' => true,
    ],

    'loginThrottling' => [
        // Can be 'username', 'ip', or 'ipAndUsername'
        // IP uses $request->ip() to get the IP
        // This should pull the client IP in the case of proxies but could
        // potentially be wrong or faked.
        //
        // Default: username
        'throttleBy' => 'username',

        // Enforces additional attempts must be at least 3 seconds apart
        'secondsBetweenAttempts' => 3,

        // You get x attempts at any speed before throttling begins
        'maxAttemptsBeforeThrottle' => 3,

        // Throttling is reset after no attempts are made for this
        // length of time
        //
        // Can be any time understood by DateTime::modify
        // http://php.net/manual/en/datetime.modify.php
        // Default: 30 minutes
        'throttleReset' => '30 minutes',
    ],

    'timeouts' => [
        /*
         * With default settings, a session would expire after 8 hours if used
         * at least once every 40 minutes, but a session would also expire
         * after only one use if it wasn't used within the next 40 minutes.
         */

        // This is a fixed window for the maximum life regardless of
        // use of the SSO session.
        // For this to work lifetime in your session.php config file must also be this long!
        // Can be any time understood by DateTime::modify
        // http://php.net/manual/en/datetime.modify.php
        // Default: 8 hours
        'ssoSessionTimeout' => '8 hours',
        // This is a sliding window for max idle time.
        // Can be any time understood by DateTime::modify
        // http://php.net/manual/en/datetime.modify.php
        // Default: 40 minutes
        'ssoSessionMaxIdle' => '40 minutes',
        // This kills a ticket if it hasn't been used within this time length.
        // It helps prevent replay attacks.
        // Default: 10 seconds
        'ticketTimeout' => '10 seconds',
    ],

    /*
     * These are the valid services.  This should be an array of arrays.
     * Each subarray contains a name, description, urlRegex, and optionally attributes to release.
     * If attributes are not specified, no attributes will be released.
     */
    'services' => [
        [
            'name' => 'Test Application',
            'description' => 'This is for test applications only.',
            'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)app(/.*){0,1}$',
            'attributes' => ['FIRST_NAME', 'LAST_NAME', 'CITY', 'EMAIL']
        ],
        [
            'name' => 'Test Application',
            'description' => 'Warning: This is for test applications only.',
            'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)localhost.*'
        ],
    ],

    /*
     * This is useful for people running SQL Server and freeTDS to correct the date format
     * so it can be properly parsed by Laravel if it is currently causing errors.
     */
    'dateFormatOverride' => null, //'Y-m-d H:i:s'
];
