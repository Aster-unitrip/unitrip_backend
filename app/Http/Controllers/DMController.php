<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;

use Validator;




class DMController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestPService)
    {
        $this->middleware('auth');
        $this->requestService = $requestPService;
        $this->edit_rule = [
            '_id'=>'required|string|max:24', //required
            'price_per_person'=>'integer',
            'dm_layout'=>'required|string',
            'is_display'=>'required|string',
        ];
    }

    public function get_by_id($id)
    { //id 團行程id

        // 非旅行社及該旅行社人員不可修改訂單
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        $cus_itinerary_group = $this->requestService->get_one('itinerary_group', $id);
        $cus_itinerary_group_data =  json_decode($cus_itinerary_group->content(), true);
        if($user_company_id !== $cus_itinerary_group_data['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
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
            $dm_data_new['dm_layout'] = "layout_1"; // 目前預設為 "layout_1"
            $dm_data_new['price_per_person'] = 0;
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

    public function edit(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->edit_rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        // 非旅行社及該旅行社人員不可修改訂單
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        $cus_dm_edit = $this->requestService->get_one('dm', $validated['_id']);
        $cus_dm_edit_data =  json_decode($cus_dm_edit->content(), true);
        if($user_company_id !== $cus_dm_edit_data['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        $update_one_to_dm = $this->requestService->update_one('dm', $validated);
        return $update_one_to_dm;
    }
}
