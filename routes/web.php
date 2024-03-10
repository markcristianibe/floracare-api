<?php

use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


// User Controller
Route::post('/api/user/sign-in', [APIController::class, 'user_auth']);


// Plants Controller
Route::post('/api/plants/get-info', [APIController::class, 'get_plant_info']);

//User Plants Controller
Route::post('/api/users/plants/add-plant', [APIController::class, 'add_user_plant']);
Route::get('/api/users/plants/get-plants', [APIController::class, 'get_user_plants']);
Route::post('/api/users/plants/remove-plant', [APIController::class, 'remove_user_plant']);
Route::get('/api/user/plants/{id}', [APIController::class, 'get_user_plant_info']);