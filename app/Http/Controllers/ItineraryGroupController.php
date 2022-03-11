<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Services\ItineraryGroupService;
use Validator;

class ItineraryGroupController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->rule = [
            'order_id' => 'required|string',
            'name' => 'required|string|max:30',
            'summary' => 'nullable|string|max:150',
            'code' => 'nullable|string|max:20',
            'travel_start' => 'required|date',
            'travel_end' => 'required|date',
            'total_day' => 'required|integer|between:1,30',
            'areas' => 'nullable|array',
            'people_threshold' => 'required|integer|min:1',
            'people_full' => 'required|integer|max:100',
            'sub_categories' => 'nullable|array',
            'itinerary_content' => 'required|array|min:1',
            'guides' => 'present|array',
            'transportations' => 'present|array',
            'misc' => 'present|array',
            'accounting' => 'required|array',
            'itinerary_group_cost' => 'required|numeric',
            'itinerary_group_price' => 'required|numeric',
            'include_description' => 'nullable|string|max:150',
            'exclude_description' => 'nullable|string|max:150',
        ];
        $this->edit_rule = [
            'order_id' => 'required|string',
            '_id'=>'required|string|max:24',
            'name' => 'required|string|max:30',
            'summary' => 'nullable|string|max:150',
            'code' => 'nullable|string|max:20',
            'travel_start' => 'required|date',
            'travel_end' => 'required|date',
            'total_day' => 'required|integer|max:7',
            'areas' => 'nullable|array',
            'people_threshold' => 'required|integer|min:1',
            'people_full' => 'required|integer|max:100',
            'sub_categories' => 'nullable|array',
            'itinerary_content' => 'required|array|min:1',
            'guides' => 'present|array',
            'transportations' => 'present|array',
            'misc' => 'present|array',
            'accounting' => 'required|array',
            'include_description' => 'nullable|string|max:150',
            'exclude_description' => 'nullable|string|max:150',
            'itinerary_group_cost' => 'required|numeric',
            'itinerary_group_price' => 'required|numeric',
            'owned_by'=>'required|integer',
        ];
        $this->get_rule = [
            '_id'=>'required|string|max:24',
            'owned_by'=>'required|integer',
        ];
        $this->operator_rule = [
            '_id' => 'required|string|max:24',
            'type' => 'required|string',
            'date' => 'required|date',
            'sort' => 'required|integer',
            'pay_deposit' => 'required|boolean',
            'booking_status' => 'required|string',
            'payment_status' => 'required|string',
            'deposit' => 'required|numeric',
            'balance' => 'required|numeric',
            "operator_note" => 'required|string',
            "travel_start" => 'required|date',
            "owned_by" => 'required|integer',
        ];
    }

    public function add(Request $request)
    {

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $company_id = auth()->user()->company_id;
        $validated = $validator->validated();
        $validated['owned_by'] = $company_id;


        // TODO(US-407) 需要將所有傳入時間(string)改成時間(date)傳入


        // TODO(US-390) 檢查行程內容
/*         try{
            $is = new ItineraryGroupService($validated);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } */

        // 建立前，判斷行程代碼是否重複 : 同公司不存在相同行程代碼，為空則不理
        if(array_key_exists('code', $validated)){
            $filter["code"] = $validated['code'];
            $filter["owned_by"] = $validated['owned_by'];
            $validated['operator_note'] = null;

            $result_code = $this->requestService->aggregate_search('itinerary_group', null, $filter, $page=0);
            $result_code_data = json_decode($result_code->getContent(), true);
            return $result_code_data;

            if($result_code_data["count"] > 0) return response()->json(['error' => '同間公司不可有重複的行程代碼'], 400);
        }else $validated['code'] = null;


        // 建立團行程，並回傳 團行程id
        $result = $this->requestService->insert_one('itinerary_group', $validated); // 回傳是否建立成功
        $result_data = json_decode($result->getContent(), true);

        // 找出團行程的 order_id，去修改 order itinerary_group_id、cus_group_code
        $itinerary_group = $this->requestService->get_one('itinerary_group', $result_data['inserted_id']);
        $itinerary_group_data = json_decode($itinerary_group->getContent(), true);
        $order = $this->requestService->get_one('cus_orders', $itinerary_group_data["order_id"]);
        $order_data = json_decode($order->getContent(), true);

        //處理created_at:2022-03-09T17:52:30 -> 20220309_
        $created_at_date = substr($order_data["created_at"], 0, 10);
        $created_at_time = substr($order_data["created_at"], 11);
        $created_at_date = preg_replace('/-/', "", $created_at_date);
        $created_at_time = preg_replace('/:/', "", $created_at_time);


        $fixed["itinerary_group_id"] = $itinerary_group_data['_id'];


        //CUS_"行程代碼"_"旅行社員工id"_"客製團訂單日期"_"客製團訂單時間"_"行程天數"_"第幾團"
        if(array_key_exists('code', $itinerary_group_data)){
            $fixed["cus_group_code"] = "CUS_".$itinerary_group_data['code']."_".$order_data["own_by_id"]."_".$created_at_date."_".$created_at_time."_".$itinerary_group_data['total_day']."_1";
        }else if(!array_key_exists('code', $itinerary_group_data) && $validated['code'] !== null){
            $fixed["cus_group_code"] = "CUS_".$order_data["own_by_id"]."_".$created_at_date."_".$created_at_time."_".$itinerary_group_data['total_day']."_1";
        }


        $fixed["_id"] = $order_data["_id"];
        $result = $this->requestService->update_one('cus_orders', $fixed);
        return $result;

    }

    public function edit(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->edit_rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        // 1-1 使用者公司必須是旅行社
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        // 1-2 限制只能同公司員工作修正
        // 找團行程的company_id和使用者company_id
        if($user_company_id !== $validated['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }


        // 2.修改前，判斷行程代碼是否重複 : 同公司不存在相同行程代碼，為空則不理
        if(array_key_exists('code', $validated)){
            $filter["code"] = $validated['code'];
            $filter["owned_by"] = $validated['owned_by'];
            $validated['operator_note'] = null;
            $result_code = $this->requestService->aggregate_search('itinerary_group', null, $filter, $page=0);
            $result_code_data = json_decode($result_code->getContent(), true);
            if($result_code_data["count"] > 0) return response()->json(['error' => '同間公司不可有重複的行程代碼'], 400);
        }

        $itinerary = $this->requestService->update('itinerary_group', $validated);
        return $itinerary;

        //TODO 更新 order 版本
        //TODO 應該要擋住控團預警可以使用部分

    }

    // filter: 區域, 行程名稱, 子類別, 天數起訖, 成團人數, 滿團人數, 頁數
    // name, areas, sub_categories, total_day, people_threshold, people_full, page

    // project: ID, 名稱, 子類別, 行程天數, 成團人數, 建立日期

    // TODO: total_day 與 total_day_range 不可同時存在
    public function list(Request $request)
    {
        $filter = json_decode($request->getContent(), true);
        if (array_key_exists('page', $filter)) {
            $page = $filter['page'];
            unset($filter['page']);
            if ($page <= 0) {
                return response()->json(['error' => 'page must be greater than 0'], 400);
            }
            else{
                $page = $page - 1;
            }
        }
        else{
            $page = 0;
        }

        // Handle itinerary sub_categories
        if (array_key_exists('sub_categories', $filter)) {
            $category = $filter['sub_categories'];
            $filter['sub_categories'] = array('$elemMatch' => array('$in' => $category));
        }

        // Handle itinerary areas
        // if (array_key_exists('areas', $filter)) {
        //     $areas = $filter['areas'];
        //     $filter['areas'] = array('$elemMatch' =>array('$in' => $areas));
        // }

        // Handle itinerary totoal_day range query
        if (array_key_exists('total_day_range', $filter)){
            if (array_key_exists('total_day_min', $filter['total_day_range']) && array_key_exists('total_day_max', $filter['total_day_range'])){
                $filter['total_day'] = array('$gte' => $filter['total_day_range']['total_day_min'], '$lte' => $filter['total_day_range']['total_day_max']);
            }
            elseif (array_key_exists('total_day_min', $filter['total_day_range']) && !array_key_exists('total_day_max', $filter['total_day_range'])){
                $filter['total_day'] = array('$gte' => $filter['total_day_range']['total_day_min']);
            }
            elseif (!array_key_exists('total_day_min', $filter['total_day_range']) && array_key_exists('total_day_max', $filter['total_day_range'])){
                $filter['total_day'] = array('$lte' => $filter['total_day_range']['total_day_max']);
            }
        }
        unset($filter['total_day_range']);

        // Handle itinerary area query
        if (array_key_exists('areas', $filter)) {
            $areas = $filter['areas'];
            $filter['areas'] = array('$in' => $areas);
        }


        $company_type = auth()->payload()->get('company_type');
        $company_id = auth()->payload()->get('company_id');
        if ($company_type == 1){

        }
        else if ($company_type == 2){
            $query_private = false;
            $filter['owned_by'] = auth()->user()->company_id;
            // $filter['is_display'] = true;
        }
        else{
            return response()->json(['error' => 'company_type must be 1 or 2'], 400);
        }


        $projection = array(
                // "_id" => 1,
                // "name" => 1,
                // "sub_categories" => 1,
                // "total_day" => 1,
                // "people_threshold" => 1,
                // "accounting" => 1,
                // "imgs" => 1,
                // "areas" => 1,
                // "created_at" => 1
            );
        $result = $this->requestService->aggregate_facet('itineraries', $projection, $company_id, $filter, $page, $query_private);
        return $result;
    }

    public function get_by_id($id)
    {
        $result = $this->requestService->get_one('itineraries', $id);
        $content =  json_decode($result->content(), true);
        if (array_key_exists('imgs', $content)){
            foreach ($content['imgs'] as $value){
                $n = 0;
                $split_url = explode('/', $value['url']);
                $content['imgs'][$n]['filename'] = end($split_url);
            }
        }

        return $content;
    }


    public function get_component_type(Request $request)
    {
        //傳團行程
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->get_rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        // 非旅行社及該旅行社人員不可修改訂單
        $data_before = $this->requestService->find_one('itinerary_group', $validated['_id'], null, null);
        if($data_before===false){
            return response()->json(['error' => '沒有此id資料。'], 400);
        }

        $data_before = $data_before['document'];
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }
        if($user_company_id !== $data_before['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        $result = $this->requestService->get_one('itinerary_group_groupby_component_type', $validated["_id"]);

        return $result;



    }

    public function get_delete_items(Request $request)
    {

    }

    public function save_to_itinerary(Request $request)
    {
        //傳入資訊
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $company_id = auth()->user()->company_id;
        $validated = $validator->validated();
        $validated['owned_by'] = $company_id;

        // TODO 將團行程處理成行程存下來 (等parker)



    }



    public function operator(Request $request)
    {
        //傳團行程
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->operator_rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        // 1-1 使用者公司必須是旅行社
        $user_company_id = auth()->user()->company_id;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        // 1-2 限制只能同公司員工作修正
        // 找團行程的company_id和使用者company_id
        if($user_company_id !== $validated['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }

        // TODO: 2.處理前端傳來的資料
        /*
        {
        "_id": "1222222",
        "type":"itinerary"
        "travel_start" : "2022/03/21 00:00:00",
        "date" : "2022/03/21 00:00:00",
        "sort" : 1,
        "pay_deposit": true,
        "booking_status": "已預訂",
        "payment_status": "未付款",
        "deposit": 8080,
        "balance": 5420,
        "operator_note" : "qqqqqq"
        }
        update_one
        ({_id:ObjectId('62297152ee702c753257eb19')}, {'$set':{'itinerary_content.0.components.0.pricing_detail.0.count':30}})
        IndexI是第幾天 J是天裡面排第幾 K是第幾個選項
        {
            "_id": "1222222",
            "name":"itinerary"
            "indexI": 0,
            "indexJ": 0,
            "indexK": 0,
            "pay_deposit": true,
            "booking_status": "已預訂",
            "payment_status": "未付款",
            "deposit": 8080,
            "balance": 5420
        }
        */
        // TODO381 : 比較狀態
        $itinerary_group_past_data = $this->requestService->get_one('itinerary_group', $validated['_id']);


        //經由 itinerary_group id 去 order 找 order_status ?== 已成團
        $itinerary_group_order_data = $this->requestService->find_one('cus_orders', null, 'itinerary_group_id', $validated['_id']);
        if($itinerary_group_order_data['document']['order_status'] !== "已成團") return response()->json(['error' => "訂單狀態不是已成團不可更改付款狀態"], 400);


        // 必須是已成團後才可以修改付款狀態
        // TODO 必須深入去看細項項目的各類狀態而不是大項
/*         if(array_key_exists("payment_status", $validated) && $itinerary_group_past_data['payment_status'] !== $validated['payment_status']){
            switch($itinerary_group_past_data["payment_status"]){
                case "未付款":
                    if($validated['payment_status'] !== "已付訂金" && $validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，免退款"){
                        return response()->json(['error' => "只可改到狀態1、2、5"], 400);
                    }
                    break;
                case "已付訂金":
                    if($validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，待退款"){
                        return response()->json(['error' => "只可改到狀態2、3"], 400);
                    }
                    break;
                case "已付全額":
                    if($validated['payment_status'] !== "已棄單，待退款"){
                        return response()->json(['error' => "只可改到狀態3"], 400);
                    }
                    break;
                case "已棄單，待退款":
                    if($validated['payment_status'] !== "已棄單，已退款"){
                        return response()->json(['error' => "只可改到狀態4"], 400);
                    }
                    break;
            }
        }

        if(array_key_exists("booking_status", $validated) && $itinerary_group_past_data['payment_status'] !== $validated['payment_status']){
            switch($itinerary_group_past_data["booking_status"]){
                case "未預訂":
                    if($validated['booking_status'] !== "已預訂"){
                        return response()->json(['error' => "只可改到狀態已預訂"], 400);
                    }
                    break;
                case "已預訂":
                    if($validated['booking_status'] !== "待確認退訂"){
                        return response()->json(['error' => "只可改到狀態待確認退訂"], 400);
                    }
                    break;
                case "待確認退訂":
                    if($validated['booking_status'] !== "已退訂"){
                        return response()->json(['error' => "只可改到狀態已退訂"], 400);
                    }
                    break;
            }
        } */

        // 設定修改內容
        if(array_key_exists("type", $validated)){
            if($validated["type"] === "attractions" || $validated["type"] === "accomendations"|| $validated["type"] === "activities" || $validated["type"] === "restaurants"){
                if(array_key_exists("date", $validated) && array_key_exists("sort", $validated)){
                    // TODO: 將 date 做轉換成第幾天

                    $date = substr($validated["date"], 0, 10);

                    // 必須判別是"/", "-"
                    $date = preg_replace('-', "", $date);
                    $travel_start= substr($$validated['travel_start'], 0, 10);
                    $travel_start = preg_replace('/-/', "", $travel_start);
                    return $date;







                    $sort = $validated["sort"]-1; // sort比原來少1
                    $name = "itinerary_content".$day."components".$sort;
                }else return response()->json(['error' => 'date, sort are not defined.'], 400);


            }else if($validated["name"] === "transportations" || $validated["name"] === "guides"){
                $sort = $validated["sort"]-1; // sort比原來少1
                $name = "itinerary_content".$sort;
            }
        }else return response()->json(['error' => '沒有傳回修改項目名稱(name)'], 400);

        $fixed['_id'] = $validated['_id'];
        //抓到更新欄位

        //判斷狀態可否CRUD
        $result = $this->requestService->update_one('itinerary_group', $fixed);
        return $result;


/*         // 叫出該_id資料
        $id = $validated['_id'];
        $data_before = $this->requestService->find_one('itinerary_group', $id, null, null);
        if($data_before===false){
            return response()->json(['error' => '此id沒有資料。'], 400);
        }
        $data_before = $data_before['document'];

        // TODO 供應商付款狀態判斷

        // TODO 供應商待退/退款 */


    }
}
