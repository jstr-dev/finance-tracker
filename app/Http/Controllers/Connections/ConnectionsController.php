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

        return Inertia::render('connections', compact('connections'));
    }
}
