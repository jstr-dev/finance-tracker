<?php

use App\Http\Controllers\Connections\ConnectionsController;
use App\Http\Controllers\Connections\Trading212\Trading212Controller;
use Illuminate\Support\Facades\Route;

Route::group(['controller' => ConnectionsController::class, 'prefix' => 'connections', 'middleware' => 'auth'], function () {
    Route::get('', 'index');

    Route::group(['prefix' => 'trading212', 'controller' => Trading212Controller::class], function () {
        Route::get('', 'index');
    });
});
