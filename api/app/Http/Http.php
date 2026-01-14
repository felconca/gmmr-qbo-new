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
    Route::post('find_invoice', 'NonPharmaController@find_invoice');
});
// gmmr pharmacy
Route::group(['prefix' => 'pharmacy', 'middleware' => [new AuthSession("user")]], function () {
    // gmmr functions
    Route::get('invoices', 'PharmacyController@index');
    Route::get('edit', 'PharmacyController@edit');
    Route::get('details', 'PharmacyController@details');
    Route::post('update', 'PharmacyController@update');

    // for quickbooks functions
    Route::post('book_invoice', 'PharmacyController@book_invoice');
    Route::post('update_invoice', 'PharmacyController@update_invoice');
    Route::post('edit_invoice', 'PharmacyController@edit_invoice');
    Route::post('delete_invoice', 'PharmacyController@delete_invoice');
    Route::post('find_invoice', 'PharmacyController@find_invoice');
});
// gmmr professional fees
Route::group(['prefix' => 'pf', 'middleware' => [new AuthSession("user")]], function () {
    // gmmr functions
    Route::get('invoices', 'ProfessionalFeeController@index');
    Route::get('edit', 'ProfessionalFeeController@edit');
    Route::post('update', 'ProfessionalFeeController@update');
    Route::get('details', 'ProfessionalFeeController@details');
    // for quickbooks functions
    Route::post('book_invoice', 'ProfessionalFeeController@book_invoice');
    Route::post('update_invoice', 'ProfessionalFeeController@update_invoice');
    Route::post('edit_invoice', 'ProfessionalFeeController@edit_invoice');
    Route::post('delete_invoice', 'ProfessionalFeeController@delete_invoice');
    Route::post('find_invoice', 'ProfessionalFeeController@find_invoice');
});


// inventory
Route::group(['prefix' => 'inventory', 'middleware' => [new AuthSession("user")]], function () {
    // gmmr functions
    Route::get('pharmacy', 'InventoryController@pharmacy');
    Route::get('nonpharma', 'InventoryController@nonpharma');
    Route::post('book_inventory', 'InventoryController@book_inventory');
    Route::post('delete_inventory', 'InventoryController@delete_inventory');
    // for quickbooks functions

});

Route::group(['prefix' => 'quickbooks', 'middleware' => [new AuthSession("user")]], function () {
    // QBO invoice specific endpoints
    Route::get('items/list', 'QBOServiceController@items_list');
    Route::post('items/add', 'QBOServiceController@items_add');
    Route::post('items/update', 'QBOServiceController@items_update');
    Route::post('items/delete', 'QBOServiceController@items_delete');
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
