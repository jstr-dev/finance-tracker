<?php

namespace App\Services;

use App\Models\UserConnection;
use App\Models\UserInvestment;
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
            ->withHeader('Authorization', 'Basic ' . $conn->getAccessToken())
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

    public function tokenHasAuth(string $token)
    {
        $res = Http::withHeader('Authorization', 'Basic ' . $token)
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
        $syncedAt = now();

        $instruments = Cache::remember('trading212.instruments', 60 * 60 * 24 * 30, function () use ($conn) {
            $instruments = $this->getInstruments($conn);
            $instruments = collect($instruments);
            return $instruments->keyBy('ticker');
        });

        $portfolio = $this->getPortfolio($conn);
        $investments = [];

        foreach ($portfolio as $investment) {
            $this->log("Syncing Investment {$investment['ticker']}");

            $instrument = $instruments->get($investment['ticker']);
            $investments[] = [
                'user_id' => $conn->user_id,
                'connection_id' => $conn->id,
                'ticker' => $investment['ticker'],
                'name' => $instrument['name'] ?? null,
                'amount' => $investment['quantity'],
                'current_price' => $investment['currentPrice'],
                'average_price' => $investment['averagePrice'],
                'currency' => $instrument['currencyCode'],
                'synced_at' => $syncedAt,
            ];
        }

        UserInvestment::upsert($investments, ['user_id', 'connection_id', 'ticker', 'synced_at'], [
            'amount', 'average_price', 'current_price'
        ]);

        // $pies = Cache::remember("trading212.pies.{$conn->id}", 60, fn() => $this->getAllPies($conn));
        // $this->log('Fetching Pie information.');

        // foreach ($pies as $pie) {
        //     $pieId = $pie['id'];
        //     $this->log("Syncing Pie $pieId.");
        //     $pie = $this->getPie($conn, $pieId);
        //     dd($pie);
        // }
    }

    private function log(string $msg)
    {
        if (!app()->runningInConsole() || app()->runningUnitTests()) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        print_r("[$timestamp] $msg\n");
    }
}