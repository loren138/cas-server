<?php

namespace Loren138\CASServerTests;

use Loren138\CASServer\Models\CASAuthentication;
use Loren138\CASServer\Models\CASTicket;
use Carbon\Carbon;

class CasTicketTest extends TestCase
{
    use DatabaseMigrations;

    public function testTicketId()
    {
        $ticket = new CASTicket();
        $this->assertSame('ST-test.org-', $ticket->ticketPrefix());
        $this->assertSame('heyYou123', $ticket->unconvertTicketId($ticket->convertTicketId('heyYou123')));
    }

    public function testDeleteTicketsForAuth()
    {
        $auth = new CASAuthentication();
        $auth->username = 't';
        $auth->lastUsed = Carbon::now();
        $auth->createdAt = Carbon::now();
        $auth->sso = 1;
        $auth->save();
        $dontSee = $auth->id;
        $ticket = new CASTicket();
        $time = Carbon::now();
        $ticket->id = 1;
        $ticket->service = 'hey';
        $ticket->used = 1;
        $ticket->authenticationId = $dontSee;
        $ticket->renew = 0;
        $ticket->createdAt = $time;
        $ticket->save();
        $ticket = new CASTicket();
        $time = Carbon::now();
        $ticket->id = 2;
        $ticket->service = 'hey';
        $ticket->used = 1;
        $ticket->authenticationId = $dontSee;
        $ticket->renew = 0;
        $ticket->createdAt = $time;
        $ticket->save();
        $auth = new CASAuthentication();
        $auth->username = 't';
        $auth->lastUsed = Carbon::now();
        $auth->createdAt = Carbon::now();
        $auth->sso = 1;
        $auth->save();
        $see = $auth->id;
        $ticket = new CASTicket();
        $ticket->id = 3;
        $ticket->service = 'hey';
        $ticket->used = 1;
        $ticket->authenticationId = $see;
        $ticket->renew = 0;
        $ticket->createdAt = $time;
        $ticket->save();
        $ticket = new CASTicket();
        $this->assertSame($ticket->deleteTicketsForAuth($dontSee), 2);
        $this->dontSeeInDatabase('CASTicket', ['authenticationId' => $dontSee]);
        $this->seeInDatabase('CASTicket', ['authenticationId' => $see]);
        $this->assertSame($ticket->deleteTicketsForAuth(12), 0);
        $this->assertSame($ticket->deleteTicketsForAuth($see), 1);
        $this->dontSeeInDatabase('CASTicket', ['authenticationId' => $see]);
    }

    public function testCleanup()
    {
        $auth = new CASAuthentication();
        $auth->username = 't';
        $auth->lastUsed = Carbon::now();
        $auth->createdAt = Carbon::now();
        $auth->sso = 1;
        $auth->save();
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

        $this->assertSame(count($ticket->all()), 3, '3 rows');
        $this->assertSame($ticket->cleanup(), 2);
        $this->assertSame(count($ticket->all()), 1, '1 row');
        $this->seeInDatabase('CASTicket', ['id' => 3]);
    }

    public function testGenerateTicket()
    {
        $knownDate = Carbon::create(2001, 5, 21, 12, 1, 2);
        Carbon::setTestNow($knownDate);
        $auth = new CASAuthentication();
        $auth->id = 123;
        $auth->sso = 1;
        $auth->username = 'hey';
        $auth->createdAt = Carbon::now();
        $auth->lastUsed = '2015-01-01 01:01:01';
        $ticket = new CASTicket();

        $id = $ticket->generateTicket($auth, 'test');
        $id = $ticket->unconvertTicketId($id);

        $t = $ticket->find($id);
        $this->assertSame($t->authenticationId, '123');
        $this->assertSame($t->service, 'test');
        $this->assertSame($t->renew, false);
        $this->assertSame($t->used, false);
        $this->assertSame(
            '2001-05-21 12:01:02',
            $t->createdAt->format('Y-m-d H:i:s')
        );
        $a = $auth->find(123);
        $this->assertSame(
            '2001-05-21 12:01:02',
            $a->lastUsed->format('Y-m-d H:i:s')
        );
        Carbon::setTestNow();
    }

    public function testConfig()
    {
        config(['casserver.timeouts.ticketTimeout' => '2 hours']);
        $ticket = new CASTicket();
        $this->assertSame($ticket->maxInterval, '2 hours');
        config(['casserver.timeouts.ticketTimeout' => '10 seconds']);
    }

    public function testGenerateTicket2()
    {
        $knownDate = Carbon::create(2001, 5, 21, 12, 1, 2);
        Carbon::setTestNow($knownDate);
        $auth = new CASAuthentication();
        $auth->id = 124;
        $auth->sso = 1;
        $auth->username = 'hey';
        $auth->createdAt = Carbon::now();
        $ticket = new CASTicket();
        $service = 'http://my.happy.place/cute/kittens/images';

        $id = $ticket->generateTicket($auth, $service, true);
        $id = $ticket->unconvertTicketId($id);

        $t = $ticket->find($id);
        $this->assertSame($t->authenticationId, '124');
        $this->assertSame($t->service, $service);
        $this->assertSame($t->renew, true);
        $this->assertSame($t->used, false);
        $this->assertSame(
            '2001-05-21 12:01:02',
            $t->createdAt->format('Y-m-d H:i:s')
        );
        Carbon::setTestNow();
    }

    public function testValidate()
    {
        $ticket = new CASTicket();
        $this->assertSame('INVALID_REQUEST', $ticket->validate('', 'hey'));
        $this->assertSame('INVALID_REQUEST', $ticket->validate('hey', ''));
        $this->assertSame('INVALID_REQUEST', $ticket->validate('', ''));
        $this->assertSame('INVALID_TICKET_SPEC', $ticket->validate('hey', 'hey'));
        $this->assertSame('INVALID_TICKET', $ticket->validate('ST-test.org-1', 'hey'));

        $auth = new CASAuthentication();
        $auth->username = 't';
        $auth->lastUsed = Carbon::now();
        $auth->createdAt = Carbon::now();
        $auth->sso = 1;
        $auth->save();
        $ticket->id = 1;
        $ticket->service = 'hey';
        $ticket->used = 1;
        $ticket->authenticationId = $auth->id;
        $ticket->renew = 0;
        $ticket->createdAt = new \DateTime();
        $ticket->save();

        $this->assertSame('INVALID_TICKET', $ticket->validate('ST-test.org-1', 'hey'));

        $ticket->createdAt = Carbon::parse('-'.$ticket->maxInterval);
        $ticket->used = 0;
        $ticket->save();

        $this->assertSame('INVALID_TICKET', $ticket->validate('ST-test.org-1', 'hey'));

        $ticket->createdAt = Carbon::now();
        $ticket->save();
        $this->assertSame('INVALID_SERVICE', $ticket->validate('ST-test.org-1', 'hey2'));

        $ticket = $ticket->find(1);
        $this->assertSame(true, $ticket->used);

        $ticket->used = 0;
        $ticket->save();

        $this->assertSame('INVALID_RENEW', $ticket->validate('ST-test.org-1', 'hey', true));
        $ticket = $ticket->find(1);
        $this->assertSame(true, $ticket->used);

        $ticket->used = 0;
        $ticket->renew = 1;
        $ticket->save();
        $this->assertSame('1', $ticket->validate('ST-test.org-1', 'hey', true)->id);
        $ticket = $ticket->find(1);
        $this->assertSame(true, $ticket->used);

        $ticket->used = 0;
        $ticket->save();
        $this->assertSame('hey', $ticket->validate('ST-test.org-1', 'hey')->service);
        $ticket = $ticket->find(1);
        $this->assertSame(true, $ticket->used);

        $ticket->used = 0;
        $ticket->renew = 0;
        $ticket->save();
        $this->assertSame('hey', $ticket->validate('ST-test.org-1', 'hey')->service);
    }
}
