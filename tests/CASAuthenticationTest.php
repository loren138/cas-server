<?php

namespace Loren138\CASServerTests;

use Loren138\CASServer\Models\CASAuthentication;
use Loren138\CASServer\Models\CASTicket;
use Carbon\Carbon;

class CasAuthenticationTest extends TestCase
{
    use DatabaseMigrations;

    public function testNotLoggedIn()
    {
        $auth = new CASAuthentication();
        $this->assertSame($auth->loggedIn(), false);
    }

    public function testNotLoggedIn2()
    {
        $auth = new CASAuthentication();
        \Session::set('casserver.authenticationId', 2);
        $this->assertSame($auth->loggedIn(), false);
    }

    public function testLoggedIn()
    {
        $data = \Faker\Factory::create();
        $auth = new CASAuthentication();
        $user = $data->userName;
        $auth->username = $user;
        $auth->attributeJson = '';
        $auth->createdAt = new \DateTime();
        $auth->lastUsed = new \DateTime();
        $auth->sso = 1;
        $auth->save();
        \Session::set('casserver.authenticationId', $auth->id);

        $this->assertSame($auth->loggedIn()->username, $user);
    }

    public function testLoggedInNotSSO()
    {
        $data = \Faker\Factory::create();
        $auth = new CASAuthentication();
        $user = $data->userName;
        $auth->username = $user;
        $auth->attributeJson = '';
        $auth->createdAt = new \DateTime();
        $auth->lastUsed = new \DateTime();
        $auth->sso = 0;
        $auth->save();
        \Session::set('casserver.authenticationId', $auth->id);

        $this->assertSame($auth->loggedIn(), false);
    }
    
    public function testLogout()
    {
        session(['casserver.authenticationId' => 1]);
        $auth = new CASAuthentication();
        $auth->logout();
        $this->assertSame(session('casserver.authenticationId'), false);
    }

    public function testLogoutDBRow()
    {
        $data = \Faker\Factory::create();
        $auth = new CASAuthentication();
        $user = $data->userName;
        $auth->username = $user;
        $auth->attributeJson = '';
        $auth->createdAt = new \DateTime();
        $auth->lastUsed = new \DateTime();
        $auth->sso = 1;
        $auth->save();
        \Session::set('casserver.authenticationId', $auth->id);

        $auth = new CASAuthentication();
        $auth->logout();
        $this->assertSame(\Session::get('casserver.authenticationId'), false);
        $this->dontSeeInDatabase('CASAuthentication', ['id' => $auth->id]);
    }

    public function testLogoutTickets()
    {
        $data = \Faker\Factory::create();
        $auth = new CASAuthentication();
        $user = $data->userName;
        $auth->username = $user;
        $auth->attributeJson = '';
        $auth->createdAt = new \DateTime();
        $auth->lastUsed = new \DateTime();
        $auth->sso = 1;
        $auth->save();
        $ticket = new CASTicket();
        $ticket->id = 1;
        $ticket->service = 'hey';
        $ticket->used = 1;
        $ticket->authenticationId = $auth->id;
        $ticket->renew = 0;
        $ticket->createdAt = new \DateTime();
        $ticket->save();
        \Session::set('casserver.authenticationId', $auth->id);

        $auth = new CASAuthentication();
        $auth->logout();
        $this->assertSame(\Session::get('casserver.authenticationId'), false);
        $this->dontSeeInDatabase('CASAuthentication', ['id' => $auth->id]);
        $this->dontSeeInDatabase('CASTicket', ['id' => 1]);
    }

    public function testLoggedInExpired()
    {
        $data = \Faker\Factory::create();
        $auth = new CASAuthentication();
        $auth->username = $data->userName;
        $auth->attributeJson = '';
        $createdAt = new \DateTime();
        $createdAt->modify('-'.$auth->maxTime);
        $auth->createdAt = $createdAt;
        $auth->lastUsed = new \DateTime();
        $auth->sso = 1;
        $auth->save();
        \Session::set('casserver.authenticationId', $auth->id);

        $this->assertSame($auth->loggedIn(), false);
    }

    public function testLoggedInExpiredInterval()
    {
        $data = \Faker\Factory::create();
        $auth = new CASAuthentication();
        $auth->username = $data->userName;
        $auth->attributeJson = '';
        $lastUsed = new \DateTime();
        $lastUsed->modify('-'.$auth->maxInterval);
        $auth->createdAt = new \DateTime();
        $auth->lastUsed = $lastUsed;
        $auth->sso = 1;
        $auth->save();
        \Session::set('casserver.authenticationId', $auth->id);

        $this->assertSame($auth->loggedIn(), false);
    }

    public function testLogIn()
    {
        $knownDate = Carbon::create(2001, 5, 21, 12, 1, 2);
        Carbon::setTestNow($knownDate);
        $data = \Faker\Factory::create();
        $user = $data->userName;
        $auth = new CASAuthentication();

        $auth->logIn($user, [], true);

        $auth2 = new CASAuthentication();
        $auths = $auth2->where('username', '=', $user)->get();
        $this->assertSame(1, count($auths));
        $this->assertSame([], $auths[0]['attributeJson']);
        $this->assertSame(true, $auths[0]['sso']);
        $this->assertEquals(session('casserver.authenticationId'), $auths[0]['id']);
        $this->assertSame(
            '2001-05-21 12:01:02',
            $auths[0]['lastUsed']->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            '2001-05-21 12:01:02',
            $auths[0]['createdAt']->format('Y-m-d H:i:s')
        );
        Carbon::setTestNow();
    }

    public function testLogIn2()
    {
        $knownDate = Carbon::create(2001, 5, 21, 12, 1, 2);
        Carbon::setTestNow($knownDate);
        $data = \Faker\Factory::create();
        $user = $data->userName;
        $auth = new CASAuthentication();

        $auth->logIn($user, ['hey' => 'hey2'], false);

        $auth2 = new CASAuthentication();
        $auths = $auth2->where('username', '=', $user)->get();
        $this->assertSame(1, count($auths));
        $this->assertSame(['hey' => 'hey2'], $auths[0]['attributeJson']);
        $this->assertSame(false, $auths[0]['sso']);
        $this->assertSame(session('casserver.authenticationId'), $auths[0]['id']);
        $this->assertSame(
            '2001-05-21 12:01:02',
            $auths[0]['lastUsed']->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            '2001-05-21 12:01:02',
            $auths[0]['createdAt']->format('Y-m-d H:i:s')
        );
        Carbon::setTestNow();
    }

    public function testCleanup()
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
        $time = Carbon::parse('-'.$ticket->maxInterval);
        $time->subSecond(1);
        $ticket->id = 1;
        $ticket->service = 'hey';
        $ticket->used = 1;
        $ticket->authenticationId = $keepId2;
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
        $this->assertSame($auth->cleanup(), 3);
        // It should keep these two
        $this->assertSame(count($auth->all()), 2, '2 rows');
        $this->seeInDatabase('CASAuthentication', ['id' => $keepId]);
        $this->seeInDatabase('CASAuthentication', ['id' => $keepId2]);
    }

    public function testConfig()
    {
        //casserver.timeouts.ssoSessionTimeout
        config(['casserver.timeouts.ssoSessionTimeout' => '2 days']);
        config(['casserver.timeouts.ssoSessionMaxIdle' => '2 minutes']);
        config(['casserver.dateFormatOverride' => 'aa']);
        $auth = new CASAuthentication();
        $this->assertSame($auth->maxInterval, '2 minutes');
        $this->assertSame($auth->maxTime, '2 days');
        $this->assertSame($auth->fromDateTime(new \DateTime('2015-01-01 11:00am')), 'amam');
    }

    public function testDefaultDateOverrideConfig()
    {
        config([
            'casserver.userClass' => UserFake::class
        ]);
        $auth = new CASAuthentication();
        $this->assertSame($auth->fromDateTime(new \DateTime('2015-01-01 11:00am')), '2015-01-01 11:00:00');
    }

    public function testUseAuth()
    {
        // Bumps lastUsed
        $auth = new CASAuthentication();
        $auth->username = 'test2';
        $auth->lastUsed = '2012-01-02 01:01:01';
        $auth->createdAt = '2012-01-02';
        $auth->sso = 1;
        $auth->save();

        $this->seeInDatabase(
            'CASAuthentication',
            [
                'username' => 'test2',
                'lastUsed' => '2012-01-02 01:01:01'
            ]
        );

        $knownDate = Carbon::create(2001, 5, 21, 12, 1, 2);
        Carbon::setTestNow($knownDate);

        $auth->useAuth();
        $this->seeInDatabase(
            'CASAuthentication',
            [
                'username' => 'test2',
                'lastUsed' => '2001-05-21 12:01:02'
            ]
        );
        Carbon::setTestNow();
    }
}
