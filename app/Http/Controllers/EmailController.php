<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;
use App\Services\CompanyService;
use App\Services\UserService;
use App\Services\PasswordResetService;
use Illuminate\Support\Facades\Crypt;
use Validator;


class EmailController extends Controller
{
    public function __construct(CompanyService $companyService, UserService $userService, PasswordResetService $passwordResetService)
    {
        $this->companyService = $companyService;
        $this->userService = $userService;
        $this->passwordResetService = $passwordResetService;

    }

    public function mail(Request $request)
    {
        // 判斷是否有此帳號
        $email = $request->email;
        if(!$userdata = $this->userService->getUserByEmail($email)){
            return response()->json(['error' => "系統上沒有這個帳號!"]);
        } else{
            // 建立資訊到資料庫
            $data = [
                'email' => $userdata['email'],
                'signature' => $this->randomkeys()
            ];
            $this->passwordResetService->create($data);

            $data['contact_name'] = $userdata['contact_name'];
            $mail_url_base =env('MAIL_URL_BASE');
            // 傳 email
            Mail::to($email)->send(new TestEmail($data));
        }
    }

    public function get_token($mail_encryption, $token)
    {
        $mail = Crypt::decrypt($mail_encryption);
        $filter = ["email" =>  $mail, "signature" => $token];
        $check_email_time = strtotime(date('Y-m-d H:i:s'));
        $result = $this->passwordResetService->getEmailAndToken($filter);
        if(!$result){
            return response()->json(['error' => "請重新送出忘記密碼申請!"]);
        }
        else if($check_email_time - strtotime($result['created_at']) > 3600) {
            return response()->json(['error' => "URL連結已超過1小時，請重新送出忘記密碼申請"]);
        }
        else if($result['is_password_change'] == true){
            return response()->json(['error' => "URL連結已提出申請過，若需更改，請重新提出申請"]);
        }
        return $result;
    }

    public function reset_password(Request $request)
    {
        //傳送帳號/密碼/確認密碼
        $rule = [
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|confirmed|min:8',
            'token' => 'required|string'
        ];
        $validator = Validator::make($request->all(), $rule);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
        else{
            $validated = $validator->validated();
            $validated['password'] = bcrypt($validated["password"]);
            $this->passwordResetService->update_user($validated);
            $result = $this->modifyPassword($validated);
            return $result;
        }
    }

    public function randomkeys(){
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        str_shuffle($pattern);
        $signature = substr(str_shuffle($pattern),0,40);
        return $signature;
    }
    public function modifyPassword($validated){
        $filter = ["email" =>  $validated['email'], "signature" => $validated['token']];
        $result = $this->passwordResetService->getEmailAndToken($filter);
        $modifyData = ["signature" =>  $result['signature'], "is_password_change" => true];
        return $this->passwordResetService->update_is_password_change($modifyData);
    }
}
