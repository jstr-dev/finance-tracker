<?php

namespace App\Services;

use App\Models\UserConnection;
use Cache;
use Http;

class Trading212Service {
    private string $uri;

    public function __construct()
    {
        $this->uri = config('services.trading212.uri') . '/' . config('services.trading212.version');
    }

    private function getSleepAmount(string $endpoint)
    {
        switch ($endpoint) {
            case '/equity/account/info':
            case '/equity/pies':
                return 30;
            default:
                return 5;
        }
    }

    private function get(UserConnection $conn, string $endpoint, int $retries = 0)
    {
        $result = Http::baseUrl($this->uri)
            ->withHeader('Authorization', $conn->getAccessToken())
            ->get($endpoint);

        if ($result->getStatusCode() === 429 && $retries < 3) {
            $sleepAmount = $this->getSleepAmount($endpoint);
            $retries++;
            $this->log('Rate limit exceeded, sleeping for ' . $sleepAmount . ' seconds.');
            sleep($sleepAmount);
            return $this->get($conn, $endpoint, $retries);
        }

        if ($result->failed()) {
            throw new \Exception($result->body(), $result->getStatusCode());
        }

        return $result->json();
    }

    public function getAccountInformation(UserConnection $conn)
    {
        return $this->get($conn, '/equity/account/info');
    }

    public function getAllPies(UserConnection $conn)
    {
        return $this->get($conn, '/equity/pies');
    }

    public function getPie(UserConnection $conn, int $id)
    {
        return $this->get($conn, "/equity/pies/$id");
    }

    public function getInstruments(UserConnection $conn)
    {
        return $this->get($conn, '/equity/metadata/instruments');
    }

    public function validateToken(string $token)
    {
        $res = Http::withHeader('Authorization', $token)
            ->baseUrl($this->uri)
            ->get("/equity/account/info");
        
        return $res->getStatusCode() !== 401;
    }

    private function getPortfolio(UserConnection $conn)
    {
        return $this->get($conn, '/equity/portfolio');
    }

    public function sync(UserConnection $conn)
    {
        $portfolio = $this->getPortfolio($conn);
        dd($portfolio);

        dd();
        $pies = Cache::remember("trading212.pies.{$conn->id}", 60, fn() => $this->getAllPies($conn));
        $this->log('Fetching Pie information.');

        foreach ($pies as $pie) {
            $pieId = $pie['id'];
            $this->log("Syncing Pie $pieId.");
            $pie = $this->getPie($conn, $pieId);
            dd($pie);
        }
    }

    private function log(string $msg)
    {
        if (!app()->runningInConsole()) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        print_r("[$timestamp] $msg\n");
    }
}