<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Services\RequestPService;
use App\Services\RequestStatesService;
use App\Services\RequestCostService;


use App\Services\ItineraryGroupService;
use Validator;

class ItineraryGroupController extends Controller
{
    private $requestService;
    private $requestStatesService;
    private $requestCostService;


    public function __construct(RequestPService $requestService, RequestStatesService $requestStatesService, RequestCostService $requestCostService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->requestStatesService = $requestStatesService;
        $this->requestCostService = $requestCostService;


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
            '_id'=>'string|max:24', //required
            'owned_by'=>'required|integer',
            'order_id' => 'required|string',
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
            'itinerary_group_note' => 'string|max:150'
        ];
        $this->get_id_rule = [
            'user_company_id'=>'required',
            'itinerary_group_id'=>'string',
            'order_id'=>'required|string|max:24',
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

        // TODO:需要是旅行社才可以新增團行程
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

        // 1-2 限制只能同公司員工作修正 -> 關聯 get_id
        if($user_company_id !== $validated['owned_by']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }
        //  1.使用者公司必須是旅行社
        //  2.此訂單必須是該旅行社的
        // TODO : 3.1(新增團行程) - 沒有id -> 新增團行程格式，參考add => 須確定一推欄位何時該有
        // TODO : 3.2(編輯團行程) - 有id -> 確定團行程為該旅行社
        // TODO : 同間旅行社 [行程代碼] 必須唯一
        // TODO : 如果_id、own_by 沒有設為 required ，會有甚麼問題


        // 修改前，判斷行程代碼是否重複 : 同公司不存在相同行程代碼，為空則不理
        if(array_key_exists('code', $validated)){
            $filter["code"] = $validated['code'];
            $filter["owned_by"] = $validated['owned_by'];
            $result_code = $this->requestService->aggregate_search('itinerary_group', null, $filter, $page=0);
            $result_code_data = json_decode($result_code->getContent(), true);
        }else $validated['code'] = null;

        if(!array_key_exists('_id', $validated)){
            // 3.1(新增團行程)
            // code 新建時 同公司不可有
            if($result_code_data["count"] > 0){
                return response()->json(['error' => '同間公司不可有重複的行程代碼'], 400);
            }
            // 處理時間
            $validated['travel_start'] = $validated['travel_start']."T00:00:00";
            $validated['travel_end'] = $validated['travel_end']."T23:59:59";
            // travel_end 不可小於 travel_end
            if(strtotime($validated['travel_end']) - strtotime($validated['travel_start']) <= 0){
                return response()->json(['error' => '旅行結束時間不可早於旅行開始時間'], 400);
            }

            // 處理分割項目
            if(array_key_exists('itinerary_content', $validated)){
                for($i = 0; $i < count($validated['itinerary_content']); $i++){
                    $validated['itinerary_content'][$i]['sort'] = $i+1;
                    $validated['itinerary_content'][$i]['date'] = date("Y-m-d H:i:s", strtotime($validated['travel_start'].$i."day"));
                    if(array_key_exists('components', $validated['itinerary_content'][$i])){
                        for($j = 0; $j < count($validated['itinerary_content'][$i]['components']); $j++){
                            $validated['itinerary_content'][$i]['components'][$j]['date'] =$validated['itinerary_content'][$i]['date'];
                            $validated['itinerary_content'][$i]['components'][$j]['operator_note'] = null;
                            $validated['itinerary_content'][$i]['components'][$j]['pay_deposit'] = false;
                            $validated['itinerary_content'][$i]['components'][$j]['booking_status'] = "未預訂";
                            $validated['itinerary_content'][$i]['components'][$j]['payment_status'] = "未付款";
                            $validated['itinerary_content'][$i]['components'][$j]['deposit'] = 0;
                            $validated['itinerary_content'][$i]['components'][$j]['balance'] = $validated['itinerary_content'][$i]['components'][$j]['sum'];
                            $validated['itinerary_content'][$i]['components'][$j]['date'] = $validated['itinerary_content'][$i]['date'];
                        }
                    }
                }
            }
            if(array_key_exists('guides', $validated)){
                for($i = 0; $i < count($validated['guides']); $i++){
                    $validated['guides'][$i]['sort'] = $i+1;
                    $validated['guides'][$i]['operator_note'] = null;
                    $validated['guides'][$i]['pay_deposit'] = false;
                    $validated['guides'][$i]['booking_status'] = "未預訂"; //預定狀態
                    $validated['guides'][$i]['payment_status'] = "未付款";
                    $validated['guides'][$i]['deposit'] = 0;
                    $validated['guides'][$i]['balance'] = $validated['guides'][$i]['subtotal'];
                    $validated['guides'][$i]['date_start'] = $validated['guides'][$i]['date_start']."T00:00:00";
                    $validated['guides'][$i]['date_end'] = $validated['guides'][$i]['date_end']."T23:59:59";
                    if(strtotime($validated['guides'][$i]['date_end']) - strtotime($validated['guides'][$i]['date_start']) <= 0){
                        return response()->json(['error' => '(導遊)結束時間不可早於開始時間'], 400);
                    }
                    if(strtotime($validated['guides'][$i]['date_end']) - strtotime($validated['travel_end']) <= 0){
                        return response()->json(['error' => '導遊結束時間不可晚於旅程期間'], 400);
                    }
                    if(strtotime($validated['guides'][$i]['date_start']) - strtotime($validated['travel_start']) <=0){
                        return response()->json(['error' => '導遊開始時間不可早於旅程期間'], 400);
                    }
                }
            }
            if(array_key_exists('transportations', $validated)){
                for($i = 0; $i < count($validated['transportations']); $i++){
                    $validated['transportations'][$i]['sort'] = $i+1;
                    $validated['transportations'][$i]['operator_note'] = null;
                    $validated['transportations'][$i]['pay_deposit'] = false;
                    $validated['transportations'][$i]['booking_status'] = "未預訂"; //預定狀態
                    $validated['transportations'][$i]['payment_status'] = "未付款";
                    $validated['transportations'][$i]['deposit'] = 0;
                    $validated['transportations'][$i]['balance'] = $validated['transportations'][$i]['sum'];
                    $validated['transportations'][$i]['date_start'] = $validated['transportations'][$i]['date_start']."T00:00:00";
                    $validated['transportations'][$i]['date_end'] = $validated['transportations'][$i]['date_end']."T23:59:59";
                    if(strtotime($validated['transportations'][$i]['date_end']) - strtotime($validated['transportations'][$i]['date_start']) <= 0){
                        return response()->json(['error' => '(交通工具)結束時間不可早於開始時間'], 400);
                    }
                    if(strtotime($validated['transportations'][$i]['date_end']) - strtotime($validated['travel_end']) <= 0){
                        return response()->json(['error' => '交通工具結束時間不可晚於旅程期間'], 400);
                    }
                    if(strtotime($validated['transportations'][$i]['date_start']) - strtotime($validated['travel_start']) <=0){
                        return response()->json(['error' => '交通工具開始時間不可早於旅程期間'], 400);
                    }
                }
            }
            if(array_key_exists('misc', $validated)){
                for($i = 0; $i < count($validated['misc']); $i++){
                    $validated['transportations'][$i]['sort'] = $i+1;
                }
            }
            $validated['operator_note']= null;
            if(!array_key_exists('itinerary_group_note', $validated)){
                $validated['itinerary_group_note'] = null;
            }
            $itinerary_group_new = $this->requestService->insert_one('itinerary_group', $validated);
            $result_data = json_decode($itinerary_group_new->getContent(), true);

            // 找出團行程的 order_id，去修改 order itinerary_group_id、cus_group_code
            $itinerary_group = $this->requestService->get_one('itinerary_group', $result_data['inserted_id']);
            $itinerary_group_data = json_decode($itinerary_group->getContent(), true);
            $order = $this->requestService->get_one('cus_orders', $validated["order_id"]);
            $order_data = json_decode($order->getContent(), true);

            //處理created_at:2022-03-09T17:52:30 -> 20220309_
            $created_at_date = substr($order_data["created_at"], 0, 10);
            $created_at_time = substr($order_data["created_at"], 11);
            $created_at_date = preg_replace('/-/', "", $created_at_date);
            $created_at_time = preg_replace('/:/', "", $created_at_time);

            //CUS_"行程代碼"_"旅行社員工id"_"客製團訂單日期"_"客製團訂單時間"_"行程天數"_"第幾團"
            if(array_key_exists('code', $itinerary_group_data)){
                $fixed["cus_group_code"] = "CUS_".$itinerary_group_data['code']."_".$order_data["own_by_id"]."_".$created_at_date."_".$created_at_time."_".$itinerary_group_data['total_day']."_1";
            }else if(!array_key_exists('code', $itinerary_group_data) && $validated['code'] !== null){
                $fixed["cus_group_code"] = "CUS_".$order_data["own_by_id"]."_".$created_at_date."_".$created_at_time."_".$itinerary_group_data['total_day']."_1";
            }

            $fixed["_id"] = $order_data["_id"];
            $result = $this->requestService->update_one('cus_orders', $fixed);
            return $result;

        }else if(array_key_exists('_id', $validated)){
            //3.2(編輯團行程)
            // code
            if(($result_code_data["docs"][0]['_id'] !== $validated['_id'] && $result_code_data["count"] >= 1)){
                return response()->json(['error' => '同間公司不可有重複的行程代碼'], 400);
            }
            // travel_end 不可小於 travel_end
            if(strtotime($validated['travel_end']) - strtotime($validated['travel_start']) <= 0){
                return response()->json(['error' => '旅行結束時間不可早於旅行開始時間'], 400);
            }
            $itinerary_group = $this->requestService->update('itinerary_group', $validated);
            $result_data = json_decode($itinerary_group->getContent(), true);
            return response()->json(['success' => 'update successfully.'], 200);
        }


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
        //目標 取得團行程資訊

        /*
        user_company_id ->從 order_id
        $id ->order_id
        itinerary_group_id -> order_id
        */

        // 1-1 使用者公司必須是旅行社
        $user_company_id = auth()->user()->company_id;
        $contact_name = auth()->user()->contact_name;
        $company_data = Company::find($user_company_id);
        $company_type = $company_data['company_type'];
        if ($company_type !== 2){
            return response()->json(['error' => 'company_type must be 2'], 400);
        }

        $cus_order = $this->requestService->get_one('cus_orders', $id);
        $cus_order_data =  json_decode($cus_order->content(), true);

        //cus_order_data['user_company_id']
        // cus_order_data['itinerary_group_id']


        // 1-2 限制只能同公司員工作修正
        // 找團行程的company_id和使用者company_id
        if($user_company_id !== $cus_order_data['user_company_id']){
            return response()->json(['error' => 'you are not an employee of this company.'], 400);
        }


        if($cus_order_data['itinerary_group_id'] !== null){ //old
            $itinerary_group = $this->requestService->get_one('itinerary_group', $cus_order_data['itinerary_group_id']);
            $itinerary_group_data =  json_decode($itinerary_group->content(), true);
            return $itinerary_group_data;
        }elseif($cus_order_data['itinerary_group_id'] === null){ //new

            // TODO 這部分感覺可以優化
            $itinerary_group_data_new['order_id'] = $cus_order_data['_id'];
            $itinerary_group_data_new['name'] = "";
            $itinerary_group_data_new['summary'] = "";
            $itinerary_group_data_new['code'] = "";
            $itinerary_group_data_new['travel_start'] = "";
            $itinerary_group_data_new['travel_end'] = "";
            $itinerary_group_data_new['total_day'] = 1;
            $itinerary_group_data_new['areas'] = array("");
            $itinerary_group_data_new['sub_categories'] = array("");
            $itinerary_content_new['type'] = "";
            $itinerary_content_new['name'] = "";
            $itinerary_content_new['gather_time'] = "";
            $itinerary_content_new['gather_location'] = "";
            $itinerary_content_new['date'] = "";
            $itinerary_content_new['day_summary'] = "";
            $itinerary_content_new['components'] = array("");
            $itinerary_group_data_new['itinerary_content'] = array($itinerary_content_new);
            $itinerary_group_data_new['people_threshold'] = 1;
            $itinerary_group_data_new['people_full'] = 10;
            $itinerary_group_data_new['guides'] = array("");
            $itinerary_group_data_new['transportations'] = array("");
            $itinerary_group_data_new['misc'] = array("");
            $account_array['cost'] = 0;
            $account_array['estimation_price'] = 0;
            $account['adult'] = array($account_array);
            $account['child'] = array($account_array);
            $itinerary_group_data_new['accounting'] = array($account);
            $itinerary_group_data_new['itinerary_group_cost'] = 0;
            $itinerary_group_data_new['itinerary_group_price'] = 0;
            $itinerary_group_data_new['include_description'] = "";
            $itinerary_group_data_new['exclude_description'] = "";
            $itinerary_group_data_new['last_updated_on'] = $contact_name;
            $itinerary_group_data_new['itinerary_group_note'] = "";
            $itinerary_group_data_new['own_by'] = $user_company_id;
            return $itinerary_group_data_new;

        }





/*         $result = $this->requestService->get_one('itineraries', $id);
        $content =  json_decode($result->content(), true);
        if (array_key_exists('imgs', $content)){
            foreach ($content['imgs'] as $value){
                $n = 0;
                $split_url = explode('/', $value['url']);
                $content['imgs'][$n]['filename'] = end($split_url);
            }
        } */

    }


    public function get_component_type($id)
    {
        // 非旅行社及該旅行社人員不可修改訂單
        $data_before = $this->requestService->find_one('itinerary_group', $id, null, null);
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

        $result = $this->requestService->get_one('itinerary_group_groupby_component_type', $id);

        return $result;



    }


    public function save_to_itinerary(Request $request)
    { //將團行程存回行程範本
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

        // TODO381 : 確認是否有這筆團行程
        $itinerary_group_past = $this->requestService->find_one('itinerary_group', $validated['_id'], null, null);
        if(!$itinerary_group_past) return response()->json(['error' => "沒有這筆團行程"], 400);
        $itinerary_group_past_data = $itinerary_group_past['document'];


        // order 找 order_status ?== 已成團
        $itinerary_group_order_data = $this->requestService->find_one('cus_orders', null, 'itinerary_group_id', $validated['_id']);
        if($itinerary_group_order_data['document']['order_status'] !== "已成團"){
            return response()->json(['error' => "訂單狀態不是已成團不可更改付款狀態"], 400);
        }


        // 必須是已成團後才可以修改付款狀態
        // 設定修改內容名稱
        if(array_key_exists("date", $validated) && array_key_exists("sort", $validated)){
            $find_day = floor((strtotime($validated["date"]) - strtotime($validated['travel_start'])) / (60*60*24)); //將 date 做轉換成第幾天
            $find_sort = $validated["sort"]-1; // sort比原來少1
            if(array_key_exists("type", $validated)){
                if($validated["type"] === "attractions" || $validated["type"] === "accomendations"|| $validated["type"] === "activities" || $validated["type"] === "restaurants"){
                    $find_type = 'itinerary_content';
                    $find_name = $find_type.".".$find_day.".components.".$find_sort.".";
                    $find_name_no_dot = $find_type.".".$find_day.".components.".$find_sort;
                    if($itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort] === null){
                        return response()->json(['error' => "找不到該筆元件資訊"], 400);
                    }

                }else if($validated["type"] === "transportations" || $validated["type"] === "guides"){
                    $find_type =$validated["type"];
                    $find_name = $find_type.".".$find_sort.".";
                    $find_name_no_dot = $find_type.".".$find_sort;
                    if($itinerary_group_past_data[$find_type][$find_sort] !== null){
                        return response()->json(['error' => "找不到該筆元件資訊"], 400);
                    }
                }
            }else{
                return response()->json(['error' => '沒有傳回修改項目名稱'], 400);
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

        // 先確定該欄位是否有值



        //確認付款狀態及預訂狀態
        if($find_type === "itinerary_content"){
            $result_payment = $this->requestStatesService->payment_status($validated, $itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort]);
            if($result_payment) return $result_payment;
            $result_booking = $this->requestStatesService->booking_status($validated, $itinerary_group_past_data[$find_type][$find_day]['components'][$find_sort]);
            if($result_booking) return $result_booking;

        }else if(($find_type === "transportations" || $find_type === "guides")){
            $result_payment = $this->requestStatesService->payment_status($validated, $itinerary_group_past_data[$find_type][$find_sort]);
            if($result_payment) return $result_payment;
            $result_booking = $this->requestStatesService->payment_status($validated, $itinerary_group_past_data[$find_type][$find_sort]);
            if($result_booking) return $result_booking;
        }

        // 確定沒錯後存入團行程中
        $this->requestService->update_one('itinerary_group', $fixed);

        // 取得存後資料
        $operator_data = $this->requestService->get_one('itinerary_group', $validated['_id']);
        $operator_data = json_decode($operator_data->getContent(), true);


        // 判斷該筆資料type，欲處理待退訂項目
        if($find_type === "itinerary_content"){
            $to_deleted = $operator_data[$find_type][$find_day]['components'][$find_sort];
        }else if($find_type === "transportations" || $find_type === "guides"){
            $to_deleted = $operator_data[$find_type][$find_sort];
        }

        // 當狀態須加上刪除判斷
        // 如果待退已退則刪除該obj放入刪除DB中
        if($to_deleted['booking_status'] === "待退訂"){

            // 1.拉出這筆團行程元件資料
            $to_deleted['to_be_deleted'] = date('Y-m-d H:i:s');
            $to_deleted['deleted_at'] = null;
            $to_deleted['deleted_reason'] = null;
            $to_deleted['order_id'] = $operator_data['order_id'];
            $to_deleted['itinerary_group_id'] = $operator_data['_id'];
            $to_deleted['component_id'] = $to_deleted['_id'];
            unset($to_deleted['_id']);

            // 2.加入刪除資料庫中
            $deleted_result = $this->requestService->insert_one('cus_delete_components', $to_deleted);

            // 3.刪除團行程該元件
            $to_deleted_itinerary['_id'] = $validated['_id']; // 需要要刪除欄位_id
            $to_deleted_itinerary[$find_name_no_dot] = null; // $find_name_no_dot 刪除欄位
            $deleted_result = $this->requestService->delete_field('itinerary_group', $to_deleted_itinerary);

            // TODO: 處理團行程中計算_不同物件要計算的方式不同
            // 保留運算項目
            // $to_deleted['pricing_detail']
            // $to_deleted['subtotal']

            // 找以下兩筆位置
            // 甚麼元件
            // accounting
            // itinerary_group_cost 直接扣
            return $to_deleted;
            $this->requestCostService->after_delete_component_cost($to_deleted);

            // 團行程價錢修改
            // 訂單 amount 修改
            // TODO: 若該筆資料已經不存在 不可以重複刪除








            return $deleted_result;


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
