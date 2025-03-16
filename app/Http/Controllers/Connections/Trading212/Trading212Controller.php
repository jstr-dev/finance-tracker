<?php

namespace App\Http\Controllers\Connections\Trading212;

use App\Http\Controllers\Controller;
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
        
    }
}
