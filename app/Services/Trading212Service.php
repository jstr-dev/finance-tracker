<?php

namespace App\Services;

use Http;

class Trading212Service {
    private string $uri;

    public function __construct()
    {
        $this->uri = config('services.trading212.uri') . '/' . config('services.trading212.version');
    }

    public function validateToken(string $token)
    {
        $res = Http::withHeader('Authorization', $token)
            ->get("{$this->uri}/equity/account/info");
        
        return $res->getStatusCode() !== 401;
    }
}