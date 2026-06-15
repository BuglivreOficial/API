<?php
namespace App\Controllers\Api;

use Core\Attributes\Router;

class AuthController {
    #[Router(uri: 'api/login', method: 'POST')]
    public function login() {

    }
}