<?php

namespace App\Http\Controllers\Connections\Trading212;

use App\Http\Controllers\Controller;
use App\Models\UserConnection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class Trading212Controller extends Controller
{
    public function index()
    {
        $activeConnections = auth()->user()
            ->connections()
            ->scopes(['trading212'])
            ->get();

        return Inertia::render('connections/trading212', compact('activeConnections'));
    }

    public function store()
    {
        $existingTokens = auth()->user()->connections()->scopes('trading212')->pluck('access_token')->toArray();

        array_map(function ($token) {
            return decrypt($token);
        }, $existingTokens);
        
        request()->validate([
            'token' => ['required', 'string', Rule::notIn($existingTokens), 'min:10']
        ]);

        $connection = new UserConnection();
        $connection->connection_type = 'trading212';
        $connection->access_token = encrypt(request('token'));
        $connection->user_id = auth()->id();
        $connection->last_4_of_token = substr(request('token'), -4);
        $connection->save();

        return to_route('trading212.index')->with([
            'success' => 'Trading212 connection created successfully'
        ]);
    }
}
