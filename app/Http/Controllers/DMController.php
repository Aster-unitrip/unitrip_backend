<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Services\CompanyService;
use App\Rules\Boolean;

use Validator;


class DMController extends Controller
{
    private $requestService;
    private $companyService;

    public function __construct(RequestPService $requestPService, CompanyService $companyService)
    {
        //$this->middleware('auth');
        $this->requestService = $requestPService;
        $this->companyService = $companyService;

        $this->edit_rule = [
            '_id'=>'required|string|max:24',
            'price_per_person'=>'integer',
            'dm_layout' => ['required', new Boolean],
            'is_display' => ['required', new Boolean],
            'if_show_logo'=>'required|string'

            /* 'dm_layout'=>'required|string',
            'is_display'=>'required|string', */
        ];
    }

    public function get_dm_setting($id)
    { //id 團行程id
        $cus_itinerary_group = $this->requestService->get_one('itinerary_group', $id);
        $cus_itinerary_group_data =  json_decode($cus_itinerary_group->content(), true);
        if(array_key_exists('count', $cus_itinerary_group_data) && $cus_itinerary_group_data['count'] === 0){
            return response()->json(['error' => '沒有此筆團行程資料。'], 400);
        }

        // 先確定是否有DM中是否有資料
        $dm_before = $this->requestService->find_one('dm', null, 'itinerary_group_id', $id);

        // 更新資料
        $dm_data_new['owned_by'] = $cus_itinerary_group_data['owned_by'];
        $dm_data_new['order_id'] = $cus_itinerary_group_data['order_id'];
        $dm_data_new['itinerary_group_id'] = $cus_itinerary_group_data['_id'];
        $dm_data_new['itinerary_group_cost'] = $cus_itinerary_group_data['itinerary_group_cost'];
        $dm_data_new['itinerary_group_price'] = $cus_itinerary_group_data['itinerary_group_price'];

        if($dm_before===false){ //new
            $dm_data_new['is_display'] = "false";
            $dm_data_new['dm_layout'] = "BlueDM"; // 目前預設為 "BlueDM"
            $dm_data_new['price_per_person'] = 0;
            $dm_data_new['if_show_logo'] = "true";
            $insert_one_to_dm = $this->requestService->insert_one('dm', $dm_data_new);
        }else{ //old
            $dm_data_new['_id'] = $dm_before['document']['_id'];
            $update_one_to_dm = $this->requestService->update_one('dm', $dm_data_new);
        }
        $after_dm_data_new = $this->requestService->find_one('dm', null, 'itinerary_group_id', $id);

        if($dm_before===false){//是否第一次使用DM->更新團行程資料庫
            $edit_dm_activated["_id"] = $after_dm_data_new['document']['itinerary_group_id'];
            $edit_dm_activated["dm_activated"] = "true";
            $this->requestService->update_one('itinerary_group', $edit_dm_activated);
        }
        return $after_dm_data_new['document'];
    }

    public function get_dm_group_itinerary($id)
    {   //id 團行程id
        $cus_itinerary_group = $this->requestService->get_one('itinerary_group', $id);
        $cus_itinerary_group_data =  json_decode($cus_itinerary_group->content(), true);

        if(array_key_exists('count', $cus_itinerary_group_data) && $cus_itinerary_group_data['count'] === 0){
            return response()->json(['error' => '沒有此筆團行程資料。'], 400);
        }

        $dm_itinerary_group_data['name'] = $cus_itinerary_group_data['name'];
        $dm_itinerary_group_data['summary'] = $cus_itinerary_group_data['summary'];
        $dm_itinerary_group_data['code'] = $cus_itinerary_group_data['code'];
        $dm_itinerary_group_data['owned_by'] = $cus_itinerary_group_data['owned_by'];
        $dm_itinerary_group_data['travel_start'] = $cus_itinerary_group_data['travel_start'];
        $dm_itinerary_group_data['travel_end'] = $cus_itinerary_group_data['travel_end'];
        $dm_itinerary_group_data['total_day'] = $cus_itinerary_group_data['total_day'];
        $dm_itinerary_group_data['itinerary_content'] = $cus_itinerary_group_data['itinerary_content'];
        $dm_itinerary_group_data['include_description'] = $cus_itinerary_group_data['include_description'];
        $dm_itinerary_group_data['exclude_description'] = $cus_itinerary_group_data['exclude_description'];
        $dm_itinerary_group_data['itinerary_group_note'] = $cus_itinerary_group_data['itinerary_group_note'];
        $ta_profile = $this->companyService->getPublicDataById($cus_itinerary_group_data['owned_by']);
        $ta_profile['address'] = $ta_profile['address_city'].$ta_profile['address_town'].$ta_profile['address'];
        unset($ta_profile['address_city']);
        unset($ta_profile['address_town']);
        $dm_itinerary_group_data['company_data'] = $ta_profile;
        return $dm_itinerary_group_data;

    }

    public function edit_dm_setting(Request $request)
    {
        // 1-1 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->edit_rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();


        // 1-2 限制只能同公司員工作修正 -> 關聯 get_id
        $cus_dm_edit = $this->requestService->get_one('dm', $validated['_id']);
        $cus_dm_edit_data =  json_decode($cus_dm_edit->content(), true);
        if($owned_by !== $cus_dm_edit_data['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        $update_one_to_dm = $this->requestService->update_one('dm', $validated);
        return $update_one_to_dm;
    }
}
