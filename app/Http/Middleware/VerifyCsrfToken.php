<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/api/user/sign-in',
        '/api/user/sign-up',
        '/api/user/verify/',
        '/api/user/update-user-info/',
        '/api/plants/get-info',
        '/api/users/plants/add-plant',
        '/api/users/plants/get-plants',
        '/api/users/plants/remove-plant',
        '/api/user/plants/',
        '/api/users/plants/get-plant-activities/',
        '/api/users/plants/get-plant-diagnoses/',
        '/api/users/plants/diagnose/create',
        '/api/users/devices/get-devices/',
        '/api/users/devices/pair-devices/',
        '/api/users/devices/unpair-devices',
        '/api/users/devices/plant/',
        '/api/users/devices/rename-device/',
        '/api/users/devices/connect-device/',
        '/api/users/devices/disconnect-device/',
        '/api/users/reminders/create-reminder/',
        '/api/users/plants/get-plant-reminders/',
        '/api/users/plants/get-plant-reminder/',
        '/api/users/plants/complete-plant-reminder/',
        '/api/users/plants/delete-plant-reminder/',
        '/api/users/plants/get-user-reminders/',
        '/api/users/plant/get-plant-monitoring/',
        '/api/users/get-user-info/',
        '/api/iot/send-data',
        '/api/users/plants/get-plant-scores/'
    ];
}
