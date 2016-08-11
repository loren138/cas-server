<?php

namespace Loren138\CASServerTests;

use Loren138\CASServer\Models\CASUserInterface;

class UserFake implements CASUserInterface
{
    public $testUser = 'other';
    public $testPassword = 'v';

    public function checkLogin($username, $password)
    {
        if ($password === 'valid') {
            return true;
        }
        if ($username === $this->testUser && $password === $this->testPassword) {
            return true;
        }

        return false;
    }

    public function userAttributes($username)
    {
        return [
            'firstChar' => substr($username, 0, 1),
            'other' => 4
        ];
    }
}
