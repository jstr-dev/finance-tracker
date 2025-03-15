<?php

namespace App\Http\Controllers\Connections;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ConnectionsController extends Controller
{
    public function index()
    {
        $connections = collect();

        return Inertia::render('connections');
    }
}
