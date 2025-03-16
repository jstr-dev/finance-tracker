<?php

use App\Http\Controllers\Connections\ConnectionsController;
use App\Http\Controllers\Connections\Trading212\Trading212Controller;
use Illuminate\Support\Facades\Route;

Route::group(['controller' => ConnectionsController::class, 'prefix' => 'connections', 'middleware' => 'auth'], function () {
    Route::get('', 'index');

    Route::group(['prefix' => 'trading212', 'controller' => Trading212Controller::class, 'middleware' => 'can:trading212'], function () {
        Route::get('', 'index')->name('trading212.index');
        Route::post('', 'store')->name('trading212.store');
    });
});
