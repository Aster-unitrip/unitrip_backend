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
            $dm_data_new['is_display'] = "false"; // 是否上架
            $insert_one_to_dm = $this->requestService->insert_one('dm', $dm_data_new);
        }else{ //old
            $dm_data_new['_id'] = $dm_before['document']['_id'];
            $update_one_to_dm = $this->requestService->update_one('dm', $dm_data_new);
        }
        $after_dm_data_new = $this->requestService->find_one('dm', null, 'itinerary_group_id', $id);
        return $after_dm_data_new['document'];
    }

}
