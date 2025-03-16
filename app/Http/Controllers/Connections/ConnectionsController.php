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

        return Inertia::render('connections', compact('connections'));
    }
}
