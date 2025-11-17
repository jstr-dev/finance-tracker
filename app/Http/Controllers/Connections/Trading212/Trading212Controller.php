<?php

namespace App\Http\Controllers\Connections\Trading212;

use App\Http\Controllers\Controller;
use App\Jobs\SyncTrading212Data;
use App\Models\UserConnection;
use App\Services\Trading212Service;
use DB;
use Illuminate\Validation\Rule;

class Trading212Controller extends Controller
{
    public function store(Trading212Service $service)
    {
        $existingTokens = auth()->user()->connections()->scopes('trading212')->pluck('access_token')->toArray();

        array_map(fn($token) => decrypt($token), $existingTokens);
        request()->validate([
            'token' => ['required', 'string', Rule::notIn($existingTokens), 'min:10', function ($attribute, $value, $fail) use ($service) {
                if (!$service->validateToken($value)) {
                    $fail('The provided token is invalid.');
                }
            }]
        ]);

        $connection = DB::transaction(function () {
            $connection = new UserConnection();
            $connection->connection_type = 'trading212';
            $connection->access_token = encrypt(request('token'));
            $connection->user_id = auth()->id();
            $connection->last_4_of_token = substr(request('token'), -4);

            $connection->save();

            return $connection;
        });

        SyncTrading212Data::dispatch($connection);

        return back(); 
    }
}
