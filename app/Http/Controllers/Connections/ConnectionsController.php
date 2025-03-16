<?php

namespace App\Http\Controllers\Connections;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use Inertia\Inertia;

class ConnectionsController extends Controller
{
    public function index()
    {
        $connections = Connection::all()->append('access');
        $connections->each(function ($connection) {
            $connection->image = asset('storage/assets/logos/' . $connection->image);
        });

        $userConnections = auth()->user()
            ->connections()
            ->distinct('connection_type')
            ->pluck('connection_type');

        return Inertia::render('connections/index', compact('connections', 'userConnections'));
    }
}
