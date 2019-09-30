<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:api')->group(function() {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::resource('cities', 'API\CityController')->except(['index']);
    Route::resource('categories', 'API\CategoryController')->except(['index']);
    Route::resource('restaurants', 'API\RestaurantController')->except(['index']);
    Route::resource('menus', 'API\MenuController')->except(['index']);
    Route::resource('items', 'API\ItemController')->except(['index']);
    Route::post('/cities/insertmany', 'API\CityController@insertMany');
    Route::post('/categories/insertmany', 'API\CategoryController@insertMany');
    Route::post('/restaurants/insertmany', 'API\RestaurantController@insertMany');
    Route::post('/menus/insertmany', 'API\MenuController@insertMany');
    Route::post('/menus/insert_menus_items', 'API\MenuController@insertMenusItems');
    Route::post('/items/insertmany', 'API\ItemController@insertMany');
});

Route::resource('cities', 'API\CityController')->only(['index']);
Route::resource('categories', 'API\CategoryController')->only(['index']);
Route::resource('restaurants', 'API\RestaurantController')->only(['index']);
Route::resource('menus', 'API\MenuController')->only(['index']);
Route::resource('items', 'API\ItemController')->only(['index']);

Route::post('/menus/max_menu_id', 'API\MenuController@getMaxOfMenuId');

Route::post('/qrcode', 'API\RestaurantController@qrcode');
Route::post('/qrcode_generate', 'API\RestaurantController@qrcode_generate');
Route::get('/qrcode_download', 'API\RestaurantController@qrcode_download');
Route::post('/set_qrcode_status', 'API\RestaurantController@set_qrcode_status');
Route::get('/get_qrcode_status', 'API\RestaurantController@get_qrcode_status');
