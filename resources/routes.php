<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', '\Loren138\CASServer\Http\Controllers\CasController@getIndex');
Route::get('/login', '\Loren138\CASServer\Http\Controllers\CasController@getLogin');
Route::post('/login', '\Loren138\CASServer\Http\Controllers\CasController@postLogin');
Route::get('/logout', '\Loren138\CASServer\Http\Controllers\CasController@getLogout');

// CAS 1.0 Validate
Route::get('/validate', '\Loren138\CASServer\Http\Controllers\CasController@getValidate');
// CAS 2.0 Validate
Route::get('/serviceValidate', '\Loren138\CASServer\Http\Controllers\CasController@getServiceValidate');
// CAS 3.0 Validate
Route::get('/p3/serviceValidate', '\Loren138\CASServer\Http\Controllers\CasController@getServiceValidate3');
