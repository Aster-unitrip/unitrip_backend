<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Rules\Boolean;


use Validator;

class CrmController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestService){
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->passenger_profile_rule = [
            '_id' => 'string',
            'name' => 'required|max:30',
            'name_en' => 'string|max:30',
            'nationality' => 'required|string',
            'company' => 'string|max:50',
            'gender' => 'required|string|max:50',
            'id_number' => 'string|max:50',
            'passport_number' => 'string|max:50',
            'birthday' => 'required|string|date',
            'is_vegetarian' => ['nullable', new Boolean],
            'email' => 'email',
            'phone' => 'required|string',
            'job' => 'string|max:50',
            'needs' => 'string|max:500',
            'address' => 'array',
            'note' => 'string|max:500',
            'mtp_number' => 'string|max:50',
            'visa_number' => 'string|max:50',
        ];
    }

    public function get_by_id($id){   //$id => passenger_profile_id

        // 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        $passenger_profile_data = $this->requestService->get_one('passenger_profile', $id);
        return $passenger_profile_data;

    }

    public function edit_profile(Request $request){

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->passenger_profile_rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        $validated['address']['detail'] = $validated['address']['city'].$validated['address']['town'].$validated['address']['address'];
        $validated = $this->ensure_value_is_upper('passenger_profile',$validated);
        $passenger_profile_data = $this->requestService->update_one('passenger_profile', $validated);
        return $passenger_profile_data;

    }

    public function profile_list(Request $request){

        // 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }



    }

    public function past_orders_list(Request $request){

        // 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }



    }
    public function ensure_value_is_upper($path, $value){ //將需要為大寫value轉成大寫
        // mtp_number visa_number id_number passport_number
        if($path === "order_passenger"){
            $value['id_number'] = strtoupper($value['id_number']);
        }
        else if($path === "passenger_profile"){
            $value['mtp_number'] = strtoupper($value['mtp_number']);
            $value['visa_number'] = strtoupper($value['visa_number']);
            $value['id_number'] = strtoupper($value['id_number']);
        }

        // foreach($value as $key => $val) {
        //     if($key === 'mtp_number' || $key === 'visa_number' || $key === 'id_number' || $key === 'passport_number'){
        //         $val = strtoupper($val);
        //     }
        // }
        return $value;
    }

}
