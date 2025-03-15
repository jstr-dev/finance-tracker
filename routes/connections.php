<?php

use App\Http\Controllers\Connections\ConnectionsController;

Route::group(['controller' => ConnectionsController::class, 'prefix' => 'connections', 'middleware' => 'auth'], function () {
    Route::get('', 'index');
});
