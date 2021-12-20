<?php

namespace Tests\Feature;

use App\Http\Controllers\ComponentAccomendationController;
use Tests\TestCase;


class BasicTest extends TestCase
{

    public static function get_token()
    {
        $username = 'parker@gmail.com';
        $password = "123456";

        $response = $this->postJson('/api/auth/login', ['email' => $username, 'password'=>$password]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'access_token' => true,
            ]);
    }

}

class ComponentAccomendationControllerTest extends TestCase
{
    public function test_login()
    {
        $username = 'parker@gmail.com';
        $password = "123456";
        
        $response = $this->post('http://127.0.0.1:8000/api/auth/login', ['email' => $username, 'password'=>$password]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'access_token' => true,
            ]);
    }
}
