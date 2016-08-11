<?php

namespace Loren138\CASServerTests;

use Loren138\CASServer\Http\Controllers\CasController;
use Loren138\CASServer\Models\CASAuthentication;
use Loren138\CASServer\Models\CASTicket;
use Loren138\CASServer\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CasControllerTest extends TestCase
{
    use DatabaseMigrations;

    public $testUser;
    public $testPassword;

    public function setUp()
    {
        $userFake = new UserFake();
        $this->testUser = $userFake->testUser;
        $this->testPassword = $userFake->testPassword;
        parent::setUp();
        config(['casserver.userClass' => UserFake::class]);
    }

    public function testIndex()
    {
        $this->visit('/')
            ->seePageIs('/login');
    }

    public function testLogin()
    {
        $this->visit('/login')
            ->see('Login')
            ->see('non-secure')
            ->dontSeeElement('#msg');
    }

    public function testLoginServiceFail()
    {
        config(
            ['casserver.services' =>
                [
                    [
                        'name' => 'Test3',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*\.app/{0,1}.*',
                        'attributes' => ['PROFILE_A']
                    ],
                    [
                        'name' => 'Test2',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
                        'attributes' => ['PROFILE_A']
                    ]
                ]]
        );
        $this->visit('/login?service=http%3A%2F%2Ftesting-portal.app2%2F')
            ->see('Not Authorized');
    }

    public function testLoginServicePass()
    {
        config(
            ['casserver.services' =>
                [
                    [
                        'name' => 'Test3',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)app/.*',
                        'attributes' => ['PROFILE_A']
                    ],
                    [
                        'name' => 'Test2',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
                        'attributes' => ['PROFILE_A']
                    ]
                ]]
        );
        $this->visit('/login?service=http%3A%2F%2Ftesting-portal.app%2F')
            ->see('Test3')
            ->see('Login');
    }

    public function testLoginSucceedsAndLogout()
    {
        $this->visit('https://test.org/login')
        ->type($this->testUser, 'username')
        ->type($this->testPassword, 'password')
        ->press('Login')
        ->seePageIs('https://test.org/login')
        ->dontSee('non-secure')
        ->see('Success')
        ->see($this->testUser);

        // Test SSO
        $this->visit('https://test.org/login')
            ->see('Success')
            ->see($this->testUser);

        // Test Logout
        $this->visit('https://test.org/logout')
            ->see('logged out');

        $this->visit('https://test.org/login')
            ->see('Login')
            ->dontSee('#msg');
    }

    public function testNoSSOWithoutSSL()
    {
        $this->visit('http://test.org/login')
            ->type($this->testUser, 'username')
            ->type($this->testPassword, 'password')
            ->press('Login')
            ->seePageIs('http://test.org/login')
            ->see('Success')
            ->see($this->testUser);

        // Test no SSO
        $this->visit('http://test.org/login')
            ->dontSee('Success')
            ->dontSee('#msg')
            ->see('Login');
    }

    public function testLoginSucceedsForward()
    {
        config(
            ['casserver.services' =>
                [
                    [
                        'name' => 'Test3',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)app/.*',
                        'attributes' => ['group']
                    ],
                    [
                        'name' => 'Test2',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
                        'attributes' => ['libraryType', 'group']
                    ]
                ]]
        );
        $this->visit('https://test.org/login?service=https%3A%2F%2Flogin.testing.edu%2Fvalidate')
            ->type($this->testUser, 'username')
            ->type($this->testPassword, 'password')
            ->press('Login');
        $this->assertContains('https://login.testing.edu/validate?ticket=', $this->currentUri);
        $this->seeInDatabase('CASTicket', ['service' => 'https://login.testing.edu/validate', 'renew' => 1]);
        $this->dontSeeInDatabase('CASTicket', ['service' => 'https://login.testing.edu/validate', 'renew' => 0]);

        $auth = new CASAuthentication();
        $a = $auth->where('username', '=', $this->testUser)->first();
        $a->lastUsed = Carbon::parse('-29 minutes');
        $a->save();

        // Test SSO
        $this->visit('https://test.org/login?service=https%3A%2F%2Flogin.testing.edu%2Fvalidate');
        $this->assertContains('https://login.testing.edu/validate?ticket=', $this->currentUri);
        $ticket = new CASTicket();
        $this->seeInDatabase('CASTicket', ['service' => 'https://login.testing.edu/validate', 'renew' => 0]);

        // Test No SSO2
        $this->visit('http://test.org/login?service=https%3A%2F%2Flogin.testing.edu%2Fvalidate')
            ->see('Login')
            ->seePageIs('/login?service=https%3A%2F%2Flogin.testing.edu%2Fvalidate');

        $a = $auth->where('username', '=', $this->testUser)->first();

        $this->assertTrue(
            $a->lastUsed->diffInSeconds(null, true) < 3,
            $a->lastUsed->toDateTimeString().' is not within 3 seconds of '.
            Carbon::now()->toDateTimeString()
        );
    }

    public function testLoginSucceedsForwardNoSSL()
    {
        config(
            ['casserver.services' =>
                [
                    [
                        'name' => 'Test3',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)app/.*',
                        'attributes' => ['group']
                    ],
                    [
                        'name' => 'Test2',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
                        'attributes' => ['libraryType', 'group']
                    ]
                ]]
        );
        $this->visit('http://test.org/login?service=https%3A%2F%2Flogin.testing.edu%2Fvalidate')
            ->type($this->testUser, 'username')
            ->type($this->testPassword, 'password')
            ->press('Login');
        $this->assertContains('https://login.testing.edu/validate?ticket=', $this->currentUri);

        $auth = new CASAuthentication();
        $a = $auth->where('username', '=', $this->testUser)->first();
        $this->assertSame($a->sso, false);

        // Test No SSO
        $this->visit('http://test.org/login?service=https%3A%2F%2Flogin.testing.edu%2Fvalidate')
            ->see('Login')
            ->seePageIs('/login?service=https%3A%2F%2Flogin.testing.edu%2Fvalidate');
    }

    public function testLoginFails()
    {
        $this->visit('/login')
            ->type($this->testUser, 'username')
            ->type('ntoueh', 'password')
            ->press('Login')
            ->seePageIs('/login')
            ->see('Invalid');
    }

    public function setupTicket($service = 'test1', $created = '')
    {
        config(
            ['casserver.services' =>
                [
                    [
                        'name' => 'Test3',
                        'description' => 'Test2',
                        'urlRegex' => 'test1',
                        'attributes' => ['libraryType', 'group']
                    ],
                    [
                        'name' => 'Test2',
                        'description' => 'Test2',
                        'urlRegex' => 'test2',
                        'attributes' => ['group']
                    ]
                ]]
        );
        $auth = new CASAuthentication();
        $auth->username = 't';
        $auth->attributeJson = [
            'libraryType' => 'faculty',
            'group' => ['g1', 'g2']
        ];
        $auth->lastUsed = Carbon::now();
        $auth->createdAt = Carbon::now();
        $auth->sso = 1;
        $auth->save();
        $ticket = new CASTicket();
        $ticket->id = 1;
        $ticket->service = $service;
        $ticket->used = 0;
        $ticket->authenticationId = $auth->id;
        $ticket->renew = 0;
        $ticket->createdAt = new \DateTime($created);
        $ticket->save();
    }

    public function testValidate()
    {
        $response = $this->call(
            'GET',
            '/validate',
            ['service' => 'test1', 'ticket' => 'ST-test.org-1']
        );

        $this->assertEquals(200, $response->status());
        $this->assertEquals("no\n", $response->getContent());

        $this->setupTicket();
        $response = $this->call(
            'GET',
            '/validate',
            ['service' => 'test1', 'ticket' => 'ST-test.org-1']
        );

        $this->assertEquals(200, $response->status());
        $this->assertEquals("yes\n", $response->getContent());

        $response = $this->call('GET', '/validate', ['service' => 'test1', 'ticket' => 'ST-test.org-1']);

        $this->assertEquals(200, $response->status());
        $this->assertEquals("no\n", $response->getContent());
    }

    public function testServiceValidate()
    {
        $response = $this->call('GET', '/serviceValidate');

        $this->assertEquals(200, $response->status());
        $this->assertEquals(
            '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"> '.
            '<cas:authenticationFailure code="INVALID_REQUEST"> '.
            'Ticket not recognized. '.
            '</cas:authenticationFailure> </cas:serviceResponse>',
            trim(preg_replace('/\s+/', ' ', $response->getContent()))
        );

        $this->setupTicket();
        $response = $this->call('GET', '/serviceValidate', ['service' => 'test1', 'ticket' => 'ST-test.org-1']);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(
            '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"> <cas:authenticationSuccess> '.
            '<cas:user>t</cas:user> '.
            '<cas:proxyGrantingTicket/> '.
            '</cas:authenticationSuccess> </cas:serviceResponse>',
            trim(preg_replace('/\s+/', ' ', $response->getContent()))
        );

        $response = $this->call('GET', '/serviceValidate', ['service' => 'test1', 'ticket' => 'ST-test.org-1']);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(
            '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"> '.
            '<cas:authenticationFailure code="INVALID_TICKET"> '.
            'Ticket ST-test.org-1 not recognized. '.
            '</cas:authenticationFailure> </cas:serviceResponse>',
            trim(preg_replace('/\s+/', ' ', $response->getContent()))
        );
    }

    public function testServiceValidateExpired()
    {
        $this->setupTicket('test1', '-1 minute');
        $response = $this->call('GET', '/serviceValidate', ['service' => 'test1', 'ticket' => 'ST-test.org-1']);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(
            '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"> '.
            '<cas:authenticationFailure code="INVALID_TICKET"> '.
            'Ticket ST-test.org-1 not recognized. '.
            '</cas:authenticationFailure> </cas:serviceResponse>',
            trim(preg_replace('/\s+/', ' ', $response->getContent()))
        );
    }

    public function testServiceValidateJson()
    {
        $response = $this->call('GET', '/serviceValidate', ['format' => 'json']);

        $this->assertEquals(200, $response->status());
        $this->seeJson(
            [
                'serviceResponse' => [
                    'authenticationFailure' => [
                        'code' => 'INVALID_REQUEST',
                        'description' => 'Ticket  not recognized.',
                    ]
                ]
            ]
        );

        $this->setupTicket();
        $response = $this->call(
            'GET',
            '/serviceValidate',
            ['service' => 'test1', 'ticket' => 'ST-test.org-1', 'format' => 'JSON']
        );

        $this->assertEquals(200, $response->status());
        $this->seeJson(
            [
                'serviceResponse' => [
                    'authenticationSuccess' => [
                        'user' => 't',
                        'proxyGrantingTicket' => null,
                    ]
                ]
            ]
        );

        $response = $this->call(
            'GET',
            '/serviceValidate',
            ['service' => 'test1', 'ticket' => 'ST-test.org-1', 'format' => 'JSON']
        );

        $this->assertEquals(200, $response->status());
        $this->seeJson(
            [
                'serviceResponse' => [
                    'authenticationFailure' => [
                        'code' => 'INVALID_TICKET',
                        'description' => 'Ticket ST-test.org-1 not recognized.',
                    ]
                ]
            ]
        );
    }

    public function testP3ServiceValidate()
    {
        $response = $this->call('GET', '/p3/serviceValidate');

        $this->assertEquals(200, $response->status());
        $this->assertEquals(
            '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"> '.
            '<cas:authenticationFailure code="INVALID_REQUEST"> '.
            'Ticket not recognized. '.
            '</cas:authenticationFailure> </cas:serviceResponse>',
            trim(preg_replace('/\s+/', ' ', $response->getContent()))
        );

        $this->setupTicket();
        $response = $this->call('GET', '/p3/serviceValidate', ['service' => 'test1', 'ticket' => 'ST-test.org-1']);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(
            '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"> <cas:authenticationSuccess> '.
            '<cas:user>t</cas:user> '.
            '<cas:attributes> '.
            '<cas:libraryType>faculty</cas:libraryType> '.
            '<cas:group>g1</cas:group> '.
            '<cas:group>g2</cas:group> '.
            '</cas:attributes> '.
            '<cas:proxyGrantingTicket/> '.
            '</cas:authenticationSuccess> </cas:serviceResponse>',
            trim(preg_replace('/\s+/', ' ', $response->getContent()))
        );

        $response = $this->call('GET', '/p3/serviceValidate', ['service' => 'test1', 'ticket' => 'ST-test.org-1']);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(
            '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"> '.
            '<cas:authenticationFailure code="INVALID_TICKET"> '.
            'Ticket ST-test.org-1 not recognized. '.
            '</cas:authenticationFailure> </cas:serviceResponse>',
            trim(preg_replace('/\s+/', ' ', $response->getContent()))
        );
    }

    public function testP3ServiceValidateJson()
    {
        $response = $this->call('GET', '/p3/serviceValidate', ['format' => 'json']);

        $this->assertEquals(200, $response->status());
        $this->seeJson(
            [
                'serviceResponse' => [
                    'authenticationFailure' => [
                        'code' => 'INVALID_REQUEST',
                        'description' => 'Ticket  not recognized.',
                    ]
                ]
            ]
        );

        $this->setupTicket();
        $response = $this->call(
            'GET',
            '/p3/serviceValidate',
            ['service' => 'test1', 'ticket' => 'ST-test.org-1', 'format' => 'JSON']
        );

        $this->assertEquals(200, $response->status());
        $this->seeJson(
            [
                'serviceResponse' => [
                    'authenticationSuccess' => [
                        'user' => 't',
                        'attributes' => [
                            'libraryType' => 'faculty',
                            'group' => ['g1', 'g2']
                        ],
                        'proxyGrantingTicket' => null,
                    ]
                ]
            ]
        );

        $response = $this->call(
            'GET',
            '/p3/serviceValidate',
            ['service' => 'test1', 'ticket' => 'ST-test.org-1', 'format' => 'JSON']
        );

        $this->assertEquals(200, $response->status());
        $this->seeJson(
            [
                'serviceResponse' => [
                    'authenticationFailure' => [
                        'code' => 'INVALID_TICKET',
                        'description' => 'Ticket ST-test.org-1 not recognized.',
                    ]
                ]
            ]
        );
    }

    public function testP3ServiceValidateJsonLimited()
    {
        $this->setupTicket('test2');
        $response = $this->call(
            'GET',
            '/p3/serviceValidate',
            ['service' => 'test2', 'ticket' => 'ST-test.org-1', 'format' => 'JSON']
        );

        $this->assertEquals(200, $response->status());
        $this->seeJson(
            [
                'serviceResponse' => [
                    'authenticationSuccess' => [
                        'user' => 't',
                        'attributes' => [
                            'group' => ['g1', 'g2']
                        ],
                        'proxyGrantingTicket' => null,
                    ]
                ]
            ]
        );
    }

    public function testLogout()
    {
        $this->visit('/logout')
            ->see('logged out');
    }

    public function testLogoutServiceFail()
    {
        config(
            ['casserver.services' =>
                [
                    [
                        'name' => 'Test3',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*\.app/{0,1}.*',
                        'attributes' => ['PROFILE_A']
                    ],
                    [
                        'name' => 'Test2',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
                        'attributes' => ['PROFILE_A']
                    ]
                ]]
        );
        $this->visit('/logout?service=http%3A%2F%2Ftesting-portal.app2%2F')
            ->see('logged out')
            ->seePageIs('/logout?service=http%3A%2F%2Ftesting-portal.app2%2F');
    }

    public function testLogoutServicePass()
    {
        \Route::get(
            '/hey',
            function () {
                return 'hey';
            }
        );
        config(
            ['casserver.services' =>
                [
                    [
                        'name' => 'Test3',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)app/.*',
                        'attributes' => ['PROFILE_A']
                    ],
                    [
                        'name' => 'Test2',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
                        'attributes' => ['PROFILE_A']
                    ]
                ]]
        );
        $this->visit('/logout?service=http%3A%2F%2Ftesting-portal.app%2Fhey')
            ->seePageIs('http://testing-portal.app/hey');
    }

    public function testUnitGetLogin()
    {
        $request = new Request();
        $cas = new CasController($request);
        $service = new Service();
        $auth = \Mockery::mock('\Loren138\CASServer\Models\CASAuthentication');
        $ticket = new CASTicket();
        $request->server->set('HTTPS', 'on');
        $auth->shouldReceive('loggedIn')->once()->andReturn(false);
        $view =  $cas->getLogin($request, $service, $auth, $ticket)->render();
        $this->assertTrue(str_contains($view, 'login') && str_contains($view, 'password'), 'should get a login page');
    }

    public function testUnitGetLogin2()
    {
        $request = new Request();
        $cas = new CasController($request);
        $service = new Service();
        $auth = \Mockery::mock('\Loren138\CASServer\Models\CASAuthentication');
        $ticket = new CASTicket();
        $view =  $cas->getLogin($request, $service, $auth, $ticket)->render();
        $this->assertTrue(str_contains($view, 'login') && str_contains($view, 'password'), 'should get a login page');
    }

    public function testUnitGetLogin3()
    {
        $request = new Request();
        config(['casserver.disableNonSSL' => true]);
        $request->server->set('HTTPS', 'on');
        $cas = new CasController($request);
        $service = new Service();
        $auth = \Mockery::mock('\Loren138\CASServer\Models\CASAuthentication');
        $ticket = new CASTicket();
        $auth->shouldReceive('loggedIn')->once()->andReturnSelf();
        $auth->shouldReceive('getAttribute')->once()->with('username')->andReturn('testUser');
        $view =  $cas->getLogin($request, $service, $auth, $ticket)->render();
        $this->assertTrue(str_contains($view, 'testUser'), 'should get a success page');
    }

    public function testUnitGetLogin4()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp("/SSL/");
        config(['casserver.disableNonSSL' => true]);
        $request = new Request();
        $cas = new CasController($request);
        $service = new Service();
        $auth = \Mockery::mock('\Loren138\CASServer\Models\CASAuthentication');
        $ticket = new CASTicket();
        $cas->getLogin($request, $service, $auth, $ticket);
    }

    public function testUnitPostLogin()
    {
        $request = new Request();
        $cas = new CasController($request);
        $request->replace(['username' => 'testUser', 'password' =>'test']);
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $service = new Service();
        $auth = \Mockery::mock('\Loren138\CASServer\Models\CASAuthentication');
        $ticket = new CASTicket();
        $login = \Mockery::mock('\Loren138\CASServer\Models\CASLogin');
        $login->shouldReceive('validate')->with('testUser', '10.0.0.1', 'test')->andReturn(false);
        $view =  $cas->postLogin($request, $service, $login, $auth, $ticket)->render();
        $this->assertTrue(stripos($view, 'invalid login') !== false, 'should get a login page');
    }

    public function testUnitPostLogin2()
    {
        $request = new Request();
        $cas = new CasController($request);
        $request->replace(['username' => 'testUser', 'password' =>'test2']);
        $request->server->set('REMOTE_ADDR', '10.0.0.2');
        $service = new Service();
        $auth = \Mockery::mock('\Loren138\CASServer\Models\CASAuthentication');
        $ticket = new CASTicket();
        $login = \Mockery::mock('\Loren138\CASServer\Models\CASLogin');
        $login->shouldReceive('validate')->with('testUser', '10.0.0.2', 'test2')->andReturn(true);
        $login->shouldReceive('userAttributes')->with('testUser')->once()->andReturn([]);
        $auth->shouldReceive('login')->once()->with('testUser', [], false)->andReturnSelf();
        $auth->shouldReceive('getAttribute')->once()->with('username')->andReturn('testUser');
        $view =  $cas->postLogin($request, $service, $login, $auth, $ticket)->render();
        $this->assertTrue(str_contains($view, 'testUser'), 'should get a success page');
    }

    public function testUnitPostLogin3()
    {
        $request = new Request();
        $cas = new CasController($request);
        $request->replace(['username' => 'testUser', 'password' =>'test2']);
        $request->server->set('REMOTE_ADDR', '10.0.0.2');
        $service = new Service();
        $auth = \Mockery::mock('\Loren138\CASServer\Models\CASAuthentication');
        $ticket = new CASTicket();
        $login = \Mockery::mock('\Loren138\CASServer\Models\CASLogin');
        $request->server->set('HTTPS', 'on');
        $login->shouldReceive('validate')->with('testUser', '10.0.0.2', 'test2')->andReturn(true);
        $login->shouldReceive('userAttributes')->with('testUser')->once()->andReturn([]);
        $auth->shouldReceive('login')->once()->with('testUser', [], true)->andReturnSelf();
        $auth->shouldReceive('getAttribute')->once()->with('username')->andReturn('testUser');
        $view =  $cas->postLogin($request, $service, $login, $auth, $ticket)->render();
        $this->assertTrue(str_contains($view, 'testUser'), 'should get a success page');
    }
}
