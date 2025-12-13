<?php

use Core\Routes\Route;
use Core\Middleware\AuthToken;
use Core\Middleware\AuthSession;

// route login
Route::post('login', 'AuthController@login');
Route::post('logout', 'AuthController@logout');

Route::get('verify', 'AuthController@verify');
Route::get('profile/{img}', 'AuthController@profile', [new AuthSession("user")]);

// toke controller for qbo
Route::get('token', 'QBOTokenController@token', [new AuthSession("user")]);
Route::get('token/generate', 'QBOTokenController@generate', [new AuthSession("user")]);

Route::get('users', 'AppController@index');
Route::post('users', 'AppController@store');
Route::put('users', 'AppController@update');
Route::delete("users", "AppController@delete");
Route::get("user/{id}", "AppController@edit");

// Route::get('users/{id}', 'AppController@edit', [new AuthToken()]);
Route::get('users/id/{id}/date/{date}', 'AppController@showByIdDate', [new AuthToken()]);

Route::post('index', 'AppController@index', [new AuthToken()]);
Route::get('index', 'AppController@index', [new AuthSession("user")]);
Route::post('index', 'AppController@index', [new AuthSession("user")]);


// Group with prefix
Route::group(['prefix' => 'api/inventory', 'middleware' => [new AuthToken()]], function () {
    Route::get('items', 'InventoryController@index');           // GET /api/inventory/items
    Route::get('items/{id}', 'InventoryController@show');       // GET /api/inventory/items/123
    Route::post('items', 'InventoryController@store');          // POST /api/inventory/items
});
