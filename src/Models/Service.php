<?php

namespace Loren138\CASServer\Models;

class Service
{

    private $services;

    public function __construct()
    {
        $this->services = collect(config('casserver.services'));
    }

    public function validate($service)
    {
        return $this->services->first(function ($key, $value) use ($service) {
            return preg_match('#'.$value['urlRegex'].'#', $service);
        }, false);
    }

    public function redirect($service, $ticket)
    {
        $ticket = 'ticket='.urlencode($ticket);
        if (str_contains($service, '?')) {
            return $service.'&'.$ticket;
        } else {
            return $service.'?'.$ticket;
        }
    }

    public function attributes($service, $userAttributes)
    {
        $s = $this->validate($service);
        if (!is_array($s) || !array_key_exists('attributes', $s)) {
            return [];
        }
        return collect($userAttributes)->only($s['attributes'])->all();
    }

    public function logoutRedirect($service)
    {
        if (config('casserver.logout.followServiceRedirects') !== true) {
            return false;
        }
        if ($service && $this->validate($service) !== false) {
            return $service;
        }

        return false;
    }
}
