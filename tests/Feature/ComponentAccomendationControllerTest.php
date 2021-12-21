<?php

namespace Tests\Feature;

use App\Http\Controllers\ComponentAccomendationController;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;


class BasicTest extends TestCase
{

    public static function get_token()
    {
        $username = 'parker@gmail.com';
        $password = "123456";

        $response = $this->postJson('/api/auth/login', ['email' => $username, 'password'=>$password]);

        return $response->json()['access_token'];
    }

}

class ComponentAccomendationControllerTest extends TestCase
{
    public function test_login()
    {
        $username = 'parker@gmail.com';
        $password = "123456";
        
        $response = $this->postJson('http://127.0.0.1:8000/api/auth/login', ['email' => $username, 'password'=>$password]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'access_token' => true,
            ]);
    }

    public function test_list()
    {   
        $username = 'parker@gmail.com';
        $password = "123456";
        // $token = BasicTest::get_token();
        $token = JWTAuth::fromUser($username);
        dd($token);
        $response = $this->postJson('http://127.0.0.1:8000/api/accomendations/list', array(), array('Bearer '.$token));

        $response
            ->assertStatus(200)
            ->assertJson([
                'docs' => true,
            ]);
    }
}
