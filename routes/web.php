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
Route::get('/api/users/plants/get-plants/{userId}', [APIController::class, 'get_user_plants']);
Route::post('/api/users/plants/remove-plant', [APIController::class, 'remove_user_plant']);
Route::get('/api/user/plants/{id}', [APIController::class, 'get_user_plant_info']);
Route::get('/api/users/plants/get-plant-activities/{plant_id}', [APIController::class, 'get_plant_activities']);
Route::get('/api/users/plants/get-plant-diagnoses/{plant_id}', [APIController::class, 'get_plant_diagnoses']);
Route::post('/api/users/plants/diagnose/create', [APIController::class, 'create_diagnosis']);
Route::get('/api/users/devices/get-devices/{user_id}', [APIController::class, 'get_user_devices']);
Route::get('/api/users/devices/pair-devices/{device_id}/{plant_id}', [APIController::class, 'pair_user_devices']);
Route::get('/api/users/devices/unpair-devices/{device_id}/{plant_id}', [APIController::class, 'unpair_user_devices']);
Route::get('/api/users/devices/plant/{plant_id}', [APIController::class, 'get_plant_device']);
Route::get('/api/users/devices/rename-device/{device_id}/{name}', [APIController::class, 'rename_device']);
Route::get('/api/users/devices/connect-device/{device_id}', [APIController::class, 'connect_device']);
Route::get('/api/users/devices/disconnect-device/{device_id}', [APIController::class, 'disconnect_device']);
Route::get('/api/users/reminders/create-reminder/{plant_id}/{activity}/{date}/{time}', [APIController::class, 'create_reminder']);
Route::get('/api/users/plants/get-plant-reminders/{plant_id}', [APIController::class, 'get_plant_reminders']);
Route::get('/api/users/plants/get-plant-reminder/{id}', [APIController::class, 'get_plant_reminder_info']);
Route::get('/api/users/plants/complete-plant-reminder/{id}', [APIController::class, 'complete_plant_reminder']);
Route::get('/api/users/plants/delete-plant-reminder/{id}', [APIController::class, 'delete_plant_reminder']);
Route::get('/api/users/plants/get-user-reminders/{user_id}', [APIController::class, 'get_user_reminders']);
Route::get('/api/users/plant/get-plant-monitoring/{plant_id}', [APIController::class, 'get_user_plant_monitoring']);
Route::get('/api/users/get-user-info/{user_id}', [APIController::class, 'get_user_info']);
Route::get('/api/iot/send-data', [APIController::class, 'iot_send_data']);