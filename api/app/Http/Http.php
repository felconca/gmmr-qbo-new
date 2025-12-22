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
Route::get('token', 'QBOServiceController@token', [new AuthSession("user")]);
Route::get('token/generate', 'QBOServiceController@generate', [new AuthSession("user")]);

// gmmr nonpharmacy
Route::group(['prefix' => 'nonpharmacy', 'middleware' => [new AuthSession("user")]], function () {
    // gmmr functions
    Route::get('invoices', 'NonPharmaController@index');
    Route::get('edit', 'NonPharmaController@edit');
    Route::get('details', 'NonPharmaController@details');
    Route::post('update', 'NonPharmaController@update');

    // for quickbooks functions
    Route::post('book_invoice', 'NonPharmaController@book_invoice');
    Route::post('update_invoice', 'NonPharmaController@update_invoice');
    Route::post('edit_invoice', 'NonPharmaController@edit_invoice');
    Route::post('delete_invoice', 'NonPharmaController@delete_invoice');
    Route::post('findInvoice', 'NonPharmaController@findInvoice');
});

// gmmr pharmacy
Route::group(['prefix' => 'pharmacy', 'middleware' => [new AuthSession("user")]], function () {
    // gmmr functions
    Route::get('list', 'PharmacyController@index');
    Route::get('edit', 'PharmacyController@edit');
    Route::get('details', 'PharmacyController@details');
    Route::post('update', 'PharmacyController@update');
    // for quickbooks functions
    Route::get('add_qbo', 'PharmacyController@update_qbo');
    Route::get('update_qbo', 'PharmacyController@update_qbo');
    Route::get('edit_qbo', 'PharmacyController@edit_qbo');
    Route::get('delete_qbo', 'PharmacyController@update_qbo');
});
// gmmr professional fees
Route::group(['prefix' => 'pf', 'middleware' => [new AuthSession("user")]], function () {
    // gmmr functions
    Route::get('list', 'ProfessionalFeeController@index');
    Route::get('edit', 'ProfessionalFeeController@edit');
    Route::post('update', 'ProfessionalFeeController@update');
    Route::get('details', 'ProfessionalFeeController@details');
    // for quickbooks functions
    Route::post('add_qbo', 'ProfessionalFeeController@update_qbo');
    Route::post('update_qbo', 'ProfessionalFeeController@update_qbo');
    Route::post('edit_qbo', 'ProfessionalFeeController@edit_qbo');
    Route::post('delete_qbo', 'ProfessionalFeeController@delete_qbo');
});


// Route::get('users/{id}', 'AppController@edit', [new AuthToken()]);
Route::get('users/id/{id}/date/{date}', 'AppController@showByIdDate', [new AuthToken()]);

Route::post('index', 'AppController@index', [new AuthToken()]);
Route::get('index', 'AppController@index', [new AuthSession("user")]);
Route::post('index', 'AppController@index', [new AuthSession("user")]);


// Group with prefix
Route::group(['prefix' => 'api/inventory', 'middleware' => [new AuthToken()]], function () {
    Route::get('items', 'InventoryController@index');
    Route::get('items/{id}', 'InventoryController@show');
    Route::post('items', 'InventoryController@store');
});
