<?php

namespace App\Http\Controllers\Connections\Trading212;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class Trading212Controller extends Controller
{
    public function index()
    {
        
        return Inertia::render('connections/trading212');
    }
}
