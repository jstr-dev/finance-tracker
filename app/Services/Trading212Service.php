<?php

namespace App\Services;

use App\Models\UserConnection;
use Http;

class Trading212Service {
    private string $uri;

    public function __construct()
    {
        $this->uri = config('services.trading212.uri') . '/' . config('services.trading212.version');
    }

    private function getHttp(UserConnection $conn)
    {
        return Http::baseUrl($this->uri)
            ->withHeader('Authorization', $conn->getAccessToken());
    }

    public function getAccountInformation(UserConnection $conn)
    {
        $res = $this->getHttp($conn)
            ->get("/equity/account/info");

        return $res->json();
    }

    public function getAllPies(UserConnection $conn)
    {
        $res = $this->getHttp($conn)
            ->get("/equity/pies");

        return $res->json();
    }

    public function getPie(UserConnection $conn, int $id)
    {
        $res = $this->getHttp($conn)
            ->get("/equity/pies/$id");

        return $res->json();
    }

    public function getInstruments(UserConnection $conn)
    {
        $res = $this->getHttp($conn)
            ->get("/equity/metadata/instruments");

        return $res->json();
    }

    public function validateToken(string $token)
    {
        $res = Http::withHeader('Authorization', $token)
            ->baseUrl($this->uri)
            ->get("/equity/account/info");
        
        return $res->getStatusCode() !== 401;
    }
}