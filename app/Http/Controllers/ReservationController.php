<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Services\RequestReservationNameService;



use Validator;

class ReservationController extends Controller
{
    private $requestService;


    public function __construct(RequestPService $requestService, RequestReservationNameService $requestReservationNameService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->requestReservationNameService = $requestReservationNameService;
    }

    public function get_by_id($id)
    {   //$id => 訂單id

        // 1-1 使用者公司必須是旅行社
        $owned_by = auth()->user()->company_id;
        $company_data = Company::find($owned_by);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        // 1-2 限制只能同公司員工作修正
        $order = $this->requestService->get_one('cus_orders', $id);
        $order_data = json_decode($order->getContent(), true);
        /* if($owned_by !== $order_data['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        } */

        // 取得訂單相關資訊
        $order = $this->requestService->get_one('cus_orders', $id);
        $order_data = json_decode($order->getContent(), true);

        // 取得團行程相關資訊
        if(array_key_exists('itinerary_group_id', $order_data)){
            $itinerary_group = $this->requestService->get_one('itinerary_group_groupby_component_type', $order_data["itinerary_group_id"]);
            $itinerary_group_component_type_data = json_decode($itinerary_group->getContent(), true);
        }else{
            return response()->json(['error' => '訂單中沒有關聯團行程，可能是團行程尚未建立。']);
        }


        // 分別組合需要資料 - 檔案命名方式 + 資料obj
        $reservation_data['cus_group_code'] = $order_data["cus_group_code"];
        $reservation_data['order_id'] = $order_data["_id"];
        $reservation_data['itinerary_group_id'] = $order_data["itinerary_group_id"];
        $reservation_data['attractions'] = $this->requestReservationNameService->is_array_empty($itinerary_group_component_type_data, "attractions");
        $reservation_data['restaurants'] = $this->requestReservationNameService->is_array_empty($itinerary_group_component_type_data, "restaurants");
        $reservation_data['accomendations'] = $this->requestReservationNameService->is_array_empty($itinerary_group_component_type_data, "accomendations");
        $reservation_data['activities'] = $this->requestReservationNameService->is_array_empty($itinerary_group_component_type_data, "activities");
        $reservation_data['transportations'] = $this->requestReservationNameService->is_array_empty($itinerary_group_component_type_data, "transportations");
        $reservation_data['guides'] = $this->requestReservationNameService->is_array_empty($itinerary_group_component_type_data, "guides");

        $reservation_data_after = $this->requestReservationNameService->get_reservation_data($reservation_data);
        return $reservation_data_after;
    }

    public function pass_to_python(Request $request)
    {
        $filter = json_decode($request->getContent(), true);
        /* 包裝公司資料
        飯店名稱 飯店聯絡人 飯店電話 飯店匯款資訊 住房總天數 飯店傳真 住房日期 房型 床數 間數 報價（每房） 費用總計
        */

        //取得所有公司資料
        $data['user'] = auth()->user();
        $company_id = auth()->user()->company_id;
        $data["company"] = Company::find($company_id);

        //取得團行程所有資料
        $itinerary_group = $this->requestService->get_one('itinerary_group', $filter['itinerary_group_id']);
        $itinerary_group_data = json_decode($itinerary_group->content(), true);
        //取得訂單所有資料
        $order = $this->requestService->get_one('cus_orders', $filter['order_id']);
        $order_data = json_decode($order->content(), true);

        // 包裝公司資料
        $travel_agency['reservation_data'] = $filter;
        $travel_agency['agency_data'] = $this->requestReservationNameService->get_travel_agency($data);
        /* $travel_agency['guides'] = $this->requestReservationNameService->get_itinerary_group_guides($itinerary_group_data);
        $travel_agency['order'] = $this->requestReservationNameService->get_order_data($order_data); */

        $result_html = $this->requestService->get_data($travel_agency);

        return $result_html;

    }

}
