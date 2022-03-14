<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Services\RequestStatesService;

use App\Services\ItineraryGroupService;
use Validator;

class ItineraryGroupController extends Controller
{
    private $requestService;
    private $requestStatesService;


    public function __construct(RequestPService $requestService, RequestStatesService $requestStatesService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->requestStatesService = $requestStatesService;

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

        // 2.處理前端傳來的資料
        /*
        update_one
        ({_id:ObjectId('62297152ee702c753257eb19')}, {'$set':{'itinerary_content.0.components.0.payment_status':30}})
        */

        // TODO381 : 比較狀態
        $itinerary_group_past_data = $this->requestService->get_one('itinerary_group', $validated['_id']);
        $itinerary_group_past_data = json_decode($itinerary_group_past_data->getContent(), true);


        //經由 itinerary_group id 去 order 找 order_status ?== 已成團
        $itinerary_group_order_data = $this->requestService->find_one('cus_orders', null, 'itinerary_group_id', $validated['_id']);
        if($itinerary_group_order_data['document']['order_status'] !== "已成團") return response()->json(['error' => "訂單狀態不是已成團不可更改付款狀態"], 400);


        // 必須是已成團後才可以修改付款狀態


        // 設定修改內容名稱
        if(array_key_exists("date", $validated) && array_key_exists("sort", $validated)){
            $find_day = floor((strtotime($validated["date"]) - strtotime($validated['travel_start'])) / (60*60*24)); //將 date 做轉換成第幾天
            $find_sort = $validated["sort"]-1; // sort比原來少1
            if(array_key_exists("type", $validated)){
                if($validated["type"] === "attractions" || $validated["type"] === "accomendations"|| $validated["type"] === "activities" || $validated["type"] === "restaurants"){
                    $find_type = 'itinerary_content';
                    $find_name = $find_type.".".$find_day.".components.".$find_sort.".";

                }else if($validated["type"] === "transportations" || $validated["type"] === "guides"){
                    $find_type =$validated["type"];
                    $find_name = $find_type.".".$find_sort.".";
                }
            }else{
                return response()->json(['error' => '沒有傳回修改項目名稱(name)'], 400);
            }
        }else{
            return response()->json(['error' => 'date, sort are not defined.'], 400);
        }


        //抓到更新欄位
        $fixed['_id'] = $validated['_id'];
        $fixed[$find_name.'pay_deposit'] = $validated['pay_deposit'];
        $fixed[$find_name.'booking_status'] = $validated['booking_status'];
        $fixed[$find_name.'payment_status'] = $validated['payment_status'];
        $fixed[$find_name.'deposit'] = $validated['deposit'];
        $fixed[$find_name.'balance'] = $validated['balance'];
        $fixed[$find_name.'operator_note'] = $validated['operator_note'];

        //如果是itinerary
        if($find_type === "itinerary_content"){
            $result = $this->requestStatesService->payment_status($validated, $itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort]);
            if($result) return $result;

        }else if($find_type === "transportations" || $find_type === "guides"){
            $result = $this->requestStatesService->payment_status($validated, $itinerary_group_past_data[$find_type][$find_sort]);
            if($result) return $result;

        }

        //存資料
        $result = $this->requestService->update_one('itinerary_group', $fixed);

        //取得存後資料
        $operator_data = $this->requestService->get_one('itinerary_group', $validated['_id']);
        $operator_data = json_decode($operator_data->getContent(), true);


        // 判斷該筆資料type
        if($find_type === "itinerary_content"){
            $to_deleted = $operator_data[$find_type][$find_day]['components'][$find_sort];
        }else if($find_type === "transportations" || $find_type === "guides"){
            $to_deleted = $operator_data[$find_type][$find_sort];
        }


        // 當狀態須加上刪除判斷
        // 如果待退已退則刪除該obj放入刪除DB中
        if($to_deleted['booking_status'] === "待確認退訂"){
            /*
                _id
                order_id 訂單
                itinerary_id 團行程
                component_id 元件
                (圖元件)
                to_be_deleted(待刪除為新增時間)
                deleted_at(刪除)
            */
            // TODO: 建立資料庫元件(cus_delete_components)
            // 1. 拉出這筆團行程元件資料
            $to_deleted['to_be_deleted'] = date('Y-m-d H:i:s');
            $to_deleted['deleted_at'] = null;
            $to_deleted['deleted_reason'] = null; // TODO: 待刪除理由
            $to_deleted['order_id'] = $operator_data['order_id'];
            $to_deleted['itinerary_group_id'] = $operator_data['_id'];
            $to_deleted['component_id'] = $to_deleted['_id'];
            unset($to_deleted['_id']);

            return $to_deleted;


            // 2. 拉出筆訂單id、團行程id

        }
        if($to_deleted['booking_status'] === "已退訂"){
        }
        if($to_deleted['booking_status'] === "未預訂" || $to_deleted['booking_status'] === "已預訂"){
        }

        //TODO　訂金尾款驗算
        // 團行程定價和總值售價要跟著修正
        // 一旦 待退=>總值售價和成本會減少~
        // TODO 確定可以一次修改兩筆不同資料

    }
}
