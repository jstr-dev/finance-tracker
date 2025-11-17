<?php

use App\Http\Controllers\Connections\ConnectionsController;
use App\Http\Controllers\Connections\Monzo\MonzoController;
use App\Http\Controllers\Connections\Trading212\Trading212Controller;
use Illuminate\Support\Facades\Route;

Route::group(['controller' => ConnectionsController::class, 'prefix' => 'connections', 'middleware' => 'auth'], function () {
    Route::get('', 'index')->name('connections.index');

    Route::group(['prefix' => 'trading212', 'controller' => Trading212Controller::class, 'middleware' => 'can:trading212'], function () {
        Route::post('', 'store')->name('trading212.store');
        Route::delete('/{connection}', 'destroy')->name('trading212.destroy');
    });

    Route::group(['prefix' => 'monzo', 'controller' => MonzoController::class, 'middleware' => 'can:monzo'], function () {
        Route::get('', 'index')->name('monzo.index');
        Route::post('', 'store')->name('monzo.store');
    });
    
});
