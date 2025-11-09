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

        return Inertia::render('connections/index', [
            'connections' => $connections,
            'userConnections' => $userConnections,
            'connectionDrawerProps' => $this->getConnectionProp(),
        ]);
    }

    private function getConnectionProp()
    {
        return Inertia::lazy(function () {
            $connectionType = request()->get('connection');

            if (!$connectionType) {
                return null;
            }

            $connections = request()->user()
                ->connections()
                ->where('connection_type', $connectionType)
                ->get();

            return [
                'type' => $connectionType,
                'connections' => $connections,
            ];
        });       
    }
}
