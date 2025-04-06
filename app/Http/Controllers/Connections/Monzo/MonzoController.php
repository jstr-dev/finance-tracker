<?php

namespace App\Http\Controllers\Connections\Monzo;

use App\Http\Controllers\Controller;
use App\Models\UserConnection;
use App\Services\Trading212Service;
use DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MonzoController extends Controller
{
    public function index()
    {
        $connection = auth()->user()
            ->connections()
            ->scopes(['monzo'])
            ->with(['metas' => fn ($q) => $q->where('key', 'initial_sync')])
            ->first();

        return Inertia::render('connections/monzo', compact('connection'));
    }
}