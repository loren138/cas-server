<?php

namespace Loren138\CASServerTests;

use Loren138\CASServer\Models\Service;

class ServiceTest extends TestCase
{
    public function testValid()
    {
        $testService = [
            'name' => 'Test',
            'description' => 'Test2',
            'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
            'attributes' => ['PROFILE_A']
        ];
        $testService2 = [
            'name' => 'Test2',
            'description' => 'Test2',
            'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing2\.edu/{0,1}.*',
            'attributes' => ['PROFILE_A']
        ];
        config(['casserver.services' => [$testService, $testService2]]);
        $s = new Service();
        $this->assertSame($s->validate('http://my.testing.edu/'), $testService);
        $this->assertSame($s->validate('https://my.testing.edu/'), $testService);
        $this->assertSame($s->validate('http://testing2.edu/'), $testService2);
        $this->assertSame($s->validate('https://testing2.edu/'), $testService2);
        $this->assertSame($s->validate('https://oeueou.testing.edu/oeu/oeut'), $testService);
    }

    public function testFirst()
    {
        $testService = [
            'name' => 'Test',
            'description' => 'Test2',
            'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
            'attributes' => ['PROFILE_A']
        ];
        config(
            ['casserver.services' =>
            [
                [
                    'name' => 'Test3',
                    'description' => 'Test2',
                    'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*sbt2s\.edu/{0,1}.*',
                    'attributes' => ['PROFILE_A']
                ],
                $testService,
                [
                    'name' => 'Test2',
                    'description' => 'Test2',
                    'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
                    'attributes' => ['PROFILE_A']
                ]
            ]]
        );
        $s = new Service();
        $this->assertSame($s->validate('http://my.testing.edu/'), $testService);
    }

    public function testInvalid()
    {
        $testService = [
            'name' => 'Test',
            'description' => 'Test2',
            'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
            'attributes' => ['PROFILE_A']
        ];
        config(
            ['casserver.services' =>
            [
                $testService,
                [
                    'name' => 'Test2',
                    'description' => 'Test2',
                    'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
                    'attributes' => ['PROFILE_A']
                ]
            ]]
        );
        $s = new Service();
        $this->assertSame($s->validate('http://test.app/'), false);
    }

    public function testRedirect()
    {
        $service = new Service();
        $this->assertEquals(
            'http://my.testing.edu/index.php?ticket=hey',
            $service->redirect('http://my.testing.edu/index.php', 'hey')
        );
        $this->assertEquals(
            'http://my.testing.edu/index.php?redirect=oeun&ticket=he%26y',
            $service->redirect('http://my.testing.edu/index.php?redirect=oeun', 'he&y')
        );
    }

    public function testAttributesMore()
    {
        config(
            ['casserver.services' =>
                [
                    [
                        'name' => 'Test3',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
                        'attributes' => ['PROFILE_A']
                    ]
                ]
            ]
        );
        $userAttributes = ['PROFILE_A' => 'hey', 'PROFILE_B' => 'you'];
        $s = new Service();
        $this->assertEquals(
            $s->attributes('http://my.testing.edu/', $userAttributes),
            ['PROFILE_A' => 'hey']
        );
        $userAttributes = [];
        $s = new Service();
        $this->assertEquals(
            $s->attributes('http://my.testing.edu/', $userAttributes),
            []
        );
        $userAttributes = '';
        $s = new Service();
        $this->assertEquals(
            $s->attributes('http://my.testing.edu/', $userAttributes),
            []
        );
        $userAttributes = null;
        $s = new Service();
        $this->assertEquals(
            $s->attributes('http://my.testing.edu/', $userAttributes),
            []
        );
    }

    public function testAttributesLess()
    {
        config(
            ['casserver.services' =>
                [
                    [
                        'name' => 'Test3',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu/{0,1}.*',
                        'attributes' => ['PROFILE_A', 'PROFILE_C']
                    ]
                ]
            ]
        );
        $userAttributes = ['PROFILE_A' => 'hey', 'PROFILE_B' => 'you'];
        $s = new Service();
        $this->assertSame(
            $s->attributes('http://my.testing.edu/', $userAttributes),
            ['PROFILE_A' => 'hey']
        );
    }

    public function testLogoutRedirect()
    {
        config(
            ['casserver.services' =>
                [
                    [
                        'name' => 'Test3',
                        'description' => 'Test2',
                        'urlRegex' => '^(https?)://([A-Za-z0-9_-]+\.)*testing\.edu(/.*){0,1}$',
                        'attributes' => ['PROFILE_A']
                    ]
                ],
            'casserver.logout.followServiceRedirects' => false
            ]
        );
        $s = new Service();
        $this->assertSame(
            $s->logoutRedirect('http://my.testing.edu/'),
            false
        );
        $this->assertSame(
            $s->logoutRedirect('http://my.testing.edu2/'),
            false
        );
        config(['casserver.logout.followServiceRedirects' => true]);
        $this->assertSame(
            $s->logoutRedirect('http://my.testing.edu/'),
            'http://my.testing.edu/'
        );
        $this->assertSame(
            $s->logoutRedirect('http://my.testing.edu2/'),
            false
        );
    }
}
