<?php

namespace App\Providers;

use App\Models\Connection;
use Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->addConnectionGates();
    }

    private function addConnectionGates(): void
    {
        if (!Schema::hasTable('connections')) return;

        $connectionGates = Connection::pluck('id')->all();

        foreach ($connectionGates as $connectionGate) {
            Gate::define($connectionGate, function ($user) use ($connectionGate) {
                return true; // TODO: Check if user has permission to add this type of connection.
            });
        }
    }
}
