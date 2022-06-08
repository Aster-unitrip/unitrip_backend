<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;


class EmailController extends Controller
{
    public function send(Request $request)
    {
        $data = ['message' => 'This is a test!'];

        Mail::to('parker@unitrip.asia')->send(new TestEmail($data));

    }
}
