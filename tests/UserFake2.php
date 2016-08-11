<?php

namespace Loren138\CASServerTests;

class UserFake2
{
    public function checkLogin($username, $password)
    {
        if ($password === 'valid') {
            return true;
        }
        if ($username === 'other' && $password === 'v') {
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
