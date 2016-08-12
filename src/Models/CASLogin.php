<?php

namespace Loren138\CASServer\Models;

use Illuminate\Database\Eloquent\Model;
use Session;
use Carbon\Carbon;

class CASLogin extends Model
{
    /*
     DROP TABLE dbo.CASLogin;
     CREATE TABLE dbo.CASLogin (
        throttleBy nvarchar(510) PRIMARY KEY NOT NULL,
        attempts INT,
        lastAttempt DATETIME NOT NULL
    );
     GRANT SELECT, INSERT, UPDATE, DELETE on dbo.CASLogin to CAS_User;
     */
    public $table = 'CASLogin';
    public $primaryKey = 'throttleBy';
    public $timestamps = false;
    public $throttleByConfig; // Can't conflict with the column named throttleBy
    public $maxAttemptsBeforeThrottle;
    public $secondsBetweenAttempts;
    public $throttleReset;
    public $userClass;
    public $incrementing = false;
    public $dates = ['lastAttempt'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->throttleByConfig = config('casserver.loginThrottling.throttleBy', 'username');
        $this->secondsBetweenAttempts = intval(config('casserver.loginThrottling.secondsBetweenAttempts', 3), 10);
        $this->maxAttemptsBeforeThrottle = intval(config('casserver.loginThrottling.maxAttemptsBeforeThrottle', 3), 10);
        $this->throttleReset = config('casserver.loginThrottling.throttleReset', '30 minutes');
        $this->userClass = config('casserver.userClass', 'blank');

        if (!class_exists($this->userClass)) {
            throw new \Exception($this->userClass.' does not exist!');
        }

        if (!array_key_exists('Loren138\CASServer\Models\CASUserInterface', class_implements($this->userClass))) {
            throw new \Exception('The user class must implement Loren138\CASServer\Models\CASUserInterface!');
        }

        if (config('casserver.dateFormatOverride')) {
            $this->setDateFormat(config('casserver.dateFormatOverride'));
        }
    }

    private function throttleBy($username, $ip)
    {
        switch (strtolower($this->throttleByConfig)) {
            case 'username':
                return $username;
                break;
            case 'ip':
                return $ip;
                break;
            case 'ipandusername':
            case 'usernameandip':
                return $username.$ip;
                break;
        }
        throw new \Exception(
            'Invalid throttleBy setting: '.$this->throttleByConfig.'. '.
            'Valid settings are \'username\', \'ip\', or \'ipAndUsername\''
        );
    }

    private function throttle($throttleBy)
    {
        $throttle = $this->findOrNew($throttleBy);
        $throttle->throttleBy = $throttleBy; // New doesn't do this for us
        $throttling = Carbon::parse('-'.$this->throttleReset);
        if ($throttle->lastAttempt > $throttling) {
            $throttle->attempts++;
            if ($throttle->attempts > $this->maxAttemptsBeforeThrottle) {
                $delay = $this->secondsBetweenAttempts - $throttle->lastAttempt->diffInSeconds(null);
                if ($delay > 0) {
                    sleep($delay);
                }
            }
        } else {
            $throttle->attempts = 1;
        }
        $throttle->lastAttempt = Carbon::now();
        $throttle->save();
    }

    public function validate($username, $ip, $password)
    {
        $this->throttle($this->throttleBy($username, $ip));
        $userClass = new $this->userClass;

        if ($userClass->checkLogin($username, $password)) {
            return true;
        }

        return false;
    }

    public function userAttributes($username)
    {
        $userClass = new $this->userClass;
        return $userClass->userAttributes($username);
    }
}
