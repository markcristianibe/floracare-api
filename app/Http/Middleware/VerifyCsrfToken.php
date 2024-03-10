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
        '/api/plants/get-info',
        '/api/users/plants/add-plant',
        '/api/users/plants/get-plants',
        '/api/users/plants/remove-plant',
        '/api/user/plants/'
    ];
}
