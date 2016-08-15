<?php

namespace Loren138\CASServerTests;

use Loren138\CASServer\Models\CASAuthentication;
use Loren138\CASServer\Models\CASTicket;
use Carbon\Carbon;

class CleanupCommandTest extends TestCase
{
    use DatabaseMigrations;

    public function testCommand()
    {
        $auth = new CASAuthentication();
        $auth->username = 't';
        $auth->lastUsed = Carbon::now();
        $auth->createdAt = Carbon::now();
        $auth->sso = 1;
        $auth->save();
        $keepId = $auth->id;

        $auth = new CASAuthentication();
        $lastUsed = Carbon::parse('-'.$auth->maxInterval)->subSecond();
        $createdAt = Carbon::parse('-'.$auth->maxTime)->subSecond();
        $auth->username = 't';
        $auth->lastUsed = $lastUsed;
        $auth->createdAt = $createdAt;
        $auth->sso = 1;
        $auth->save();
        $keepId2 = $auth->id;


        $ticket = new CASTicket();
        $time = Carbon::parse('-'.$ticket->maxInterval)->subSecond();
        $ticket->id = 1;
        $ticket->service = 'hey';
        $ticket->used = 1;
        $ticket->authenticationId = $auth->id;
        $ticket->renew = 0;
        $ticket->createdAt = $time;
        $ticket->save();
        $ticket = new CASTicket();
        $ticket->id = 3;
        $ticket->service = 'hey';
        $ticket->used = 1;
        $ticket->authenticationId = $auth->id;
        $ticket->renew = 0;
        $ticket->createdAt = new \DateTime();
        $ticket->save();
        $ticket = new CASTicket();
        $ticket->id = 4;
        $ticket->service = 'hey';
        $ticket->used = 1;
        $ticket->authenticationId = $auth->id;
        $ticket->renew = 0;
        $ticket->createdAt = $time;
        $ticket->save();

        // Expired Last Used
        $auth = new CASAuthentication();
        $auth->username = 't';
        $auth->lastUsed = $lastUsed;
        $auth->createdAt = Carbon::now();
        $auth->sso = 1;
        $auth->save();

        // Expired Created
        $auth = new CASAuthentication();
        $auth->username = 't';
        $auth->lastUsed = Carbon::now();
        $auth->createdAt = $createdAt;
        $auth->sso = 1;
        $auth->save();

        // Not SSO
        $auth = new CASAuthentication();
        $auth->username = 't';
        $auth->lastUsed = Carbon::now();
        $auth->createdAt = Carbon::now();
        $auth->sso = 0;
        $auth->save();

        $this->assertSame(count($auth->all()), 5, '5 rows');
        $this->assertSame(count($ticket->all()), 3, '3 rows');

        $this->assertSame(\Artisan::call('cas-server:cleanup'), 0);
        $output = \Artisan::output();
        $this->assertTrue(str_contains($output, '3 expired auth'), '3 expired auth');
        $this->assertTrue(str_contains($output, '2 expired ticket'), '2 expired ticket');

        $this->assertSame(count($auth->all()), 2, '2 rows');
        $this->assertSame(count($ticket->all()), 1, '1 row');
        $this->seeInDatabase('CASTicket', ['id' => 3]);
        $this->seeInDatabase('CASAuthentication', ['id' => $keepId]);
        $this->seeInDatabase('CASAuthentication', ['id' => $keepId2]);
    }
}
