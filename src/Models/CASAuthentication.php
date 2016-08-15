<?php

namespace Loren138\CASServer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Carbon\Carbon;

class CASAuthentication extends Model
{
    /*
     DROP TABLE dbo.CASTicket;
     DROP TABLE dbo.CASAuthentication;
     CREATE TABLE dbo.CASAuthentication (
        id bigint PRIMARY KEY IDENTITY(1,1) NOT NULL,
        username varchar(MAX) NOT NULL,
        attributeJson varchar(MAX) NULL,
        lastUsed DATETIME NOT NULL,
        createdAt DATETIME NOT NULL,
        sso BIT NOT NULL
    );
     GRANT SELECT on dbo.CASAuthentication to CAS_User;
     GRANT INSERT on dbo.CASAuthentication to CAS_User;
     GRANT UPDATE on dbo.CASAuthentication to CAS_User;
     GRANT DELETE on dbo.CASAuthentication to CAS_User;
     */
    public $table = 'CASAuthentication';
    public $primaryKey = 'id';
    public $timestamps = false;
    public $maxTime;
    public $maxInterval;
    public $dates = ['lastUsed', 'createdAt'];
    private $sessionVar = 'casserver.authenticationId';
    protected $casts = [
        'id' => 'int',
        'attributeJson' => 'array',
        'sso' => 'boolean'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->maxTime = config('casserver.timeouts.ssoSessionTimeout', '8 hours');
        $this->maxInterval = config('casserver.timeouts.ssoSessionMaxIdle', '40 minutes');

        if (config('casserver.dateFormatOverride')) {
            $this->setDateFormat(config('casserver.dateFormatOverride'));
        }
    }

    public function loggedIn()
    {
        $authId = session($this->sessionVar);
        if ($authId) {
            $lastUsed = new \DateTime();
            $lastUsed->modify('-'.$this->maxInterval);
            $createdAt = new \DateTime();
            $createdAt->modify('-'.$this->maxTime);
            $auth = $this->where($this->primaryKey, '=', $authId)->where('lastUsed', '>', $lastUsed)
                ->where('createdAt', '>', $createdAt)->where('sso', '=', 1)->first();
            if (is_null($auth)) {
                return false;
            }
            return $auth;
        }

        return false;
    }

    public function login($username, $attributes, $sso)
    {
        $auth = new self;
        $auth->username = $username;
        $auth->attributeJson = $attributes;
        $auth->lastUsed = Carbon::now();
        $auth->createdAt = Carbon::now();
        $auth->sso = $sso;
        $auth->save();

        session([$this->sessionVar => $auth->id]);

        return $auth;
    }

    public function logout()
    {
        $authId = session($this->sessionVar);
        if ($authId) {
            $auth = $this->where($this->primaryKey, '=', $authId)->first();
            if (!is_null($auth)) {
                $ticket = new CASTicket();
                $ticket->deleteTicketsForAuth($authId);
                $auth->delete();
            }
        }

        session([$this->sessionVar => false]);
    }

    public function useAuth()
    {
        $this->lastUsed = Carbon::now();
        $this->save();
    }

    public function tickets()
    {
        return $this->hasMany('Loren138\CASServer\Models\CASTicket', 'authenticationId');
    }

    public function cleanup()
    {
        $lastUsed = Carbon::parse('-'.$this->maxInterval);
        $createdAt = Carbon::parse('-'.$this->maxTime);
        return $this->where(function ($query) use ($lastUsed, $createdAt) {
            $query->where('lastUsed', '<', $lastUsed)
                ->orWhere('createdAt', '<', $createdAt)
                ->orWhere('sso', '=', 0);
        })->whereNotExists(function ($query) {
            $ticket = new CASTicket();
            $query->select(new Expression('1'))
                ->from($ticket->table)
                ->where($ticket->table.'.authenticationId', '=', new Expression($this->table.'.'.$this->primaryKey));
        })->delete();
    }
}
