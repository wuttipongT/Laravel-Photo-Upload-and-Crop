<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home/{id}', 'HomeController@index')->name('home');
Route::post('/upload', 'HomeController@upload')->name('upload');
Route::post('/thumbnail', 'HomeController@upload_thumbnail')->name('thumbnail');
Route::get('/delete', 'HomeController@delete')->name('del');

Route::get('/session', function(){
    if(!Session::has('random_key') || strlen(session('random_key')) == 0){
        Session::put('random_key', strtotime(date('Y-m-d H:i:s')));
        Session::save();
    }
    die(var_dump(Session::get('random_key')));
});