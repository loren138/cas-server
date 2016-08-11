<?php

namespace Loren138\CASServer\Models;

interface CASUserInterface
{
    public function checkLogin($username, $password);
    public function userAttributes($username);
}
