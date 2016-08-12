<?php

namespace Loren138\CASServer\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CASTicket extends Model
{
    /*

     CREATE TABLE dbo.CASTicket (
        id VARCHAR(32) PRIMARY KEY NOT NULL,
        authenticationId bigint  FOREIGN KEY REFERENCES dbo.CASAuthentication(id),
        service VARCHAR(MAX) NOT NULL,
        renew BIT NOT NULL,
        used BIT NOT NULL,
        createdAt DATETIME NOT NULL
    );

     GRANT SELECT on dbo.CASTicket to CAS_User;
     GRANT INSERT on dbo.CASTicket to CAS_User;
     GRANT UPDATE on dbo.CASTicket to CAS_User;
     GRANT DELETE on dbo.CASTicket to CAS_User;
     */
    public $table = 'CASTicket';
    public $primaryKey = 'id';
    public $dates = ['createdAt'];
    public $maxInterval;
    public $timestamps = false;
    public $incrementing = false;
    protected $casts = [
        'renew' => 'boolean',
        'used' => 'boolean'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->maxInterval = config('casserver.timeouts.ticketTimeout', '10 seconds');

        if (config('casserver.dateFormatOverride')) {
            $this->setDateFormat(config('casserver.dateFormatOverride'));
        }
    }

    private function randomStr($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    private function getId()
    {
        do {
            $key = $this->randomStr(32);
            if (!self::where('id', '=', $key)->exists()) {
                return $key;
            }
        } while (true);
    }

    public function deleteTicketsForAuth($authId)
    {
        return $this->where('authenticationId', '=', $authId)->delete();
    }

    public function ticketPrefix()
    {
        return 'ST-'.str_replace(['http:', 'https:', '/'], '', url('/')).'-';
    }

    public function convertTicketId($id)
    {
        return $this->ticketPrefix().$id;
    }

    public function unconvertTicketId($id)
    {
        $prefix = $this->ticketPrefix();
        if (substr($id, 0, strlen($prefix)) !== $prefix) {
            return false;
        }

        return substr($id, strlen($prefix));
    }

    public function generateTicket(CASAuthentication $CASAuthentication, $service, $renew = false)
    {
        $ticket = new self;

        $ticket->id = $this->getId();
        $ticket->service = $service;
        $ticket->renew = $renew;
        $ticket->used = false;
        $ticket->createdAt = Carbon::now();
        $ticket->authentication()->associate($CASAuthentication);
        $CASAuthentication->useAuth();
        $ticket->save();

        return $this->convertTicketId($ticket->id);
    }

    private function useTicket($ticket)
    {
        $ticket->used = true;
        $ticket->save();
    }

    public function cleanup()
    {
        $created = Carbon::parse('-'.$this->maxInterval);
        return $this->where('createdAt', '<', $created)->delete();
    }

    public function validate($ticket, $service, $renew = false)
    {
        if (!$ticket || !$service) {
            return 'INVALID_REQUEST';
        }
        $ticket = $this->unconvertTicketId($ticket);
        if (!$ticket) {
            return 'INVALID_TICKET_SPEC';
        }

        // TODO? UNAUTHORIZED_SERVICE_PROXY INVALID_PROXY_CALLBACK
        $created = Carbon::parse('-'.$this->maxInterval);
        $t = $this->where('id', '=', $ticket)->where('createdAt', '>', $created)->first();
        if (is_null($t) || $t->used) {
            return 'INVALID_TICKET';
        }

        // There is a ticket, it has now been used no matter what so we mark it used
        $this->useTicket($t);

        if ($t->service !== $service) {
            return 'INVALID_SERVICE';
        }

        if ($renew && !$t->renew) {
            return 'INVALID_RENEW';
        }

        return $t;
    }

    public function authentication()
    {
        return $this->belongsTo('Loren138\CASServer\Models\CASAuthentication', 'authenticationId');
    }
}
