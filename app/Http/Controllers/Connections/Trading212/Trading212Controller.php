<?php

namespace App\Http\Controllers\Connections\Trading212;

use App\Http\Controllers\Controller;
use App\Jobs\SyncTrading212Data;
use App\Models\UserConnection;
use App\Services\Trading212Service;
use DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class Trading212Controller extends Controller
{
    public function index()
    {
        $connection = auth()->user()
            ->connections()
            ->scopes(['trading212'])
            ->with(['metas' => fn ($q) => $q->where('key', 'initial_sync')])
            ->first();

        return Inertia::render('connections/trading212', compact('connection'));
    }

    public function store(Trading212Service $service)
    {
        $existingTokens = auth()->user()->connections()->scopes('trading212')->pluck('access_token')->toArray();

        array_map(fn($token) => decrypt($token), $existingTokens);
        request()->validate([
            // 'token' => ['required', 'string', Rule::notIn($existingTokens), 'min:10', function ($attribute, $value, $fail) use ($service) {
            //     if (!$service->validateToken($value)) {
            //         $fail('The provided token is invalid.');
            //     }
            // }]
        ]);

        $connection = DB::transaction(function () {
            $connection = new UserConnection();
            $connection->connection_type = 'trading212';
            $connection->access_token = encrypt(request('token'));
            $connection->user_id = auth()->id();
            $connection->last_4_of_token = substr(request('token'), -4);

            $connection->save();
            $connection->setMeta('initial_sync', false);

            return $connection;
        });

        SyncTrading212Data::dispatch($connection);

        return to_route('trading212.index')->with([
            'success' => 'Trading212 connection created successfully'
        ]);
    }
}
