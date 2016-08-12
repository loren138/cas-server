<?php

namespace Loren138\CASServerTests;

use Loren138\CASServer\Models\CASLogin;
use Carbon\Carbon;

class CASLoginTest extends TestCase
{
    use DatabaseMigrations;

    public function testConfig()
    {
        config([
            'casserver.loginThrottling.throttleBy' => 'ip',
            'casserver.loginThrottling.secondsBetweenAttempts' => '200',
            'casserver.loginThrottling.maxAttemptsBeforeThrottle' => '400.5o',
            'casserver.loginThrottling.throttleReset' => '2 hours',
            'casserver.userClass' => UserFake::class,
            'casserver.dateFormatOverride' => 'aa'
        ]);
        $login = new CASLogin();
        $this->assertSame($login->throttleByConfig, 'ip');
        $this->assertSame($login->secondsBetweenAttempts, 200);
        $this->assertSame($login->maxAttemptsBeforeThrottle, 400);
        $this->assertSame($login->throttleReset, '2 hours');
        $this->assertSame($login->userClass, 'Loren138\CASServerTests\UserFake');
        $this->assertSame($login->fromDateTime(new \DateTime('2015-01-01 11:00am')), 'amam');
    }

    public function testBadSetting()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp("/does not exist/");
        config([
            'casserver.loginThrottling.throttleBy' => 'username2',
            'casserver.loginThrottling.secondsBetweenAttempts' => '2',
            'casserver.loginThrottling.maxAttemptsBeforeThrottle' => '3',
            'casserver.loginThrottling.throttleReset' => '30 minutes',
            'casserver.userClass' => UserBad::class,
        ]);
        $login = new CASLogin();
    }

    public function testBadImplements()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp("/must implement/");
        config([
            'casserver.loginThrottling.throttleBy' => 'username2',
            'casserver.loginThrottling.secondsBetweenAttempts' => '2',
            'casserver.loginThrottling.maxAttemptsBeforeThrottle' => '3',
            'casserver.loginThrottling.throttleReset' => '30 minutes',
            'casserver.userClass' => UserFake2::class,
        ]);
        $login = new CASLogin();
    }

    public function testLogin()
    {
        config([
            'casserver.loginThrottling.throttleBy' => 'username',
            'casserver.loginThrottling.secondsBetweenAttempts' => '2',
            'casserver.loginThrottling.maxAttemptsBeforeThrottle' => '3',
            'casserver.loginThrottling.throttleReset' => '30 minutes',
            'casserver.userClass' => UserFake::class,
        ]);
        $login = new CASLogin();
        $this->assertFalse($login->validate('webster', '127.0.0.1', 'wrong'));
        $this->assertTrue($login->validate('webster', '127.0.0.1', 'valid'));
        $this->assertTrue($login->validate('other', '127.0.0.1', 'v'));
        $this->assertFalse($login->validate('other', '127.0.0.1', 'v2'));
    }

    /**
     * @medium
     */
    public function testThrottleUsername()
    {
        Carbon::setTestNow(Carbon::create(2001, 5, 21, 12, 1, 2));
        config([
            'casserver.loginThrottling.throttleBy' => 'username',
            'casserver.loginThrottling.secondsBetweenAttempts' => '2',
            'casserver.loginThrottling.maxAttemptsBeforeThrottle' => '3',
            'casserver.loginThrottling.throttleReset' => '30 minutes',
            'casserver.userClass' => UserFake::class,
        ]);
        $login = new CASLogin();
        $time1 = microtime(true);
        $login->validate('webster', '127.0.0.1', 'wrong');
        $login->validate('webster', '127.0.0.2', 'wrong');
        $login->validate('webster', '127.0.0.3', 'wrong');
        $time2 = microtime(true);
        $this->assertTrue(($time2 - $time1) < 1, 'no delay');
        Carbon::setTestNow(Carbon::create(2001, 5, 21, 12, 1, 3));
        $login->validate('webster', '127.0.0.4', 'wrong');
        $time3 = microtime(true);
        $this->assertTrue($time3 - $time2 >= 1 && $time3 - $time2 < 2, 'delay');
        $login->validate('webster2', '127.0.0.5', 'wrong');
        $time4 = microtime(true);
        $this->assertTrue(($time4 - $time3) < 1, 'no delay');
        Carbon::setTestNow(Carbon::create(2001, 5, 21, 12, 31, 3));
        $login->validate('webster', '127.0.0.6', 'wrong');
        $time5 = microtime(true);
        $this->assertTrue(($time5 - $time4) < 1, 'no delay');
    }

    /**
     * @medium
     */
    public function testThrottleIp()
    {
        Carbon::setTestNow(Carbon::create(2001, 5, 21, 12, 1, 2));
        config([
            'casserver.loginThrottling.throttleBy' => 'ip',
            'casserver.loginThrottling.secondsBetweenAttempts' => '2',
            'casserver.loginThrottling.maxAttemptsBeforeThrottle' => '3',
            'casserver.loginThrottling.throttleReset' => '30 minutes',
            'casserver.userClass' => UserFake::class,
        ]);
        $login = new CASLogin();
        $time1 = microtime(true);
        $login->validate('webster', '127.0.0.1', 'wrong');
        $login->validate('webster2', '127.0.0.1', 'wrong');
        $login->validate('webster3', '127.0.0.1', 'wrong');
        $time2 = microtime(true);
        $this->assertTrue(($time2 - $time1) < 1, 'no delay');
        Carbon::setTestNow(Carbon::create(2001, 5, 21, 12, 1, 3));
        $login->validate('webster4', '127.0.0.1', 'wrong');
        $time3 = microtime(true);
        $this->assertTrue($time3 - $time2 >= 1 && $time3 - $time2 < 2, 'delay');
        $login->validate('webster5', '127.0.0.2', 'wrong');
        $time4 = microtime(true);
        $this->assertTrue(($time4 - $time3) < 1, 'no delay');
        Carbon::setTestNow(Carbon::create(2001, 5, 21, 12, 31, 3));
        $login->validate('webster6', '127.0.0.1', 'wrong');
        $time5 = microtime(true);
        $this->assertTrue(($time5 - $time4) < 1, 'no delay');
    }

    /**
     * @medium
     */
    public function testThrottleIpandUsername()
    {
        Carbon::setTestNow(Carbon::create(2001, 5, 21, 12, 1, 2));
        config([
            'casserver.loginThrottling.throttleBy' => 'usernameAndIP',
            'casserver.loginThrottling.secondsBetweenAttempts' => '2',
            'casserver.loginThrottling.maxAttemptsBeforeThrottle' => '3',
            'casserver.loginThrottling.throttleReset' => '30 minutes',
            'casserver.userClass' => UserFake::class,
        ]);
        $login = new CASLogin();
        $time1 = microtime(true);
        $login->validate('webster', '127.0.0.1', 'wrong');
        $login->validate('webster', '127.0.0.1', 'wrong');
        $login->validate('webster', '127.0.0.1', 'wrong');
        $time2 = microtime(true);
        $this->assertTrue(($time2 - $time1) < 1, 'no delay');

        Carbon::setTestNow(Carbon::create(2001, 5, 21, 12, 1, 3));
        $login->validate('webster', '127.0.0.1', 'wrong');
        $time3 = microtime(true);
        $this->assertTrue($time3 - $time2 >= 1 && $time3 - $time2 < 2, 'delay');

        $login->validate('webster2', '127.0.0.1', 'wrong');
        $time4 = microtime(true);
        $this->assertTrue(($time4 - $time3) < 1, 'no delay');

        $login->validate('webster', '127.0.0.2', 'wrong');
        $time4 = microtime(true);
        $this->assertTrue(($time4 - $time3) < 1, 'no delay');

        Carbon::setTestNow(Carbon::create(2001, 5, 21, 12, 31, 3));
        $login->validate('webster', '127.0.0.1', 'wrong');
        $time5 = microtime(true);
        $this->assertTrue(($time5 - $time4) < 1, 'no delay');
    }

    public function testUserAttributes()
    {
        config([
            'casserver.userClass' => UserFake::class,
        ]);
        $login = new CASLogin();
        $this->assertSame(
            $login->userAttributes('hey'),
            [
                'firstChar' => 'h',
                'other' => 4
            ]
        );
        $this->assertSame(
            $login->userAttributes('john'),
            [
                'firstChar' => 'j',
                'other' => 4
            ]
        );
    }
}
