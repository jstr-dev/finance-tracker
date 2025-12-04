<?php

namespace App\Http\Controllers\Connections\Trading212;

use App\Http\Controllers\Controller;
use App\Jobs\SyncTrading212Data;
use App\Models\UserConnection;
use App\Services\Trading212Service;
use DB;

class Trading212Controller extends Controller
{
    public function destroy(UserConnection $connection)
    {
        if ($connection->user_id !== auth()->user()->id) {
            abort(403);
        }
        
        $connection->delete();

        return back();
    }

    public function store(Trading212Service $service)
    {
        request()->validate([
            'key_id' => ['required', 'string', 'min:10'],
            'secret_key' => ['required', 'string', 'min:10']
        ], [
            'key_id.min' => 'API Key ID must be at least 10 characters',
            'secret_key.min' => 'Secret key must be at least 10 characters'
        ]);

        $token = request('key_id') . ':' . request('secret_key');
        $token = base64_encode($token);

        if (!$service->tokenHasAuth($token)) {
            return back()->withErrors([
                'secret_key' => 'Invalid credentials provided, connection not established'
            ]);
        }

        $connection = DB::transaction(function () use ($token) {
            $connection = new UserConnection();
            $connection->connection_type = 'trading212';
            $connection->access_token = encrypt($token);
            $connection->user_id = auth()->id();
            $connection->last_4_of_token = substr($token, -4);
            $connection->token_length = strlen($token);
            $connection->save();

            return $connection;
        });

        SyncTrading212Data::dispatch($connection);

        return back(); 
    }
}
