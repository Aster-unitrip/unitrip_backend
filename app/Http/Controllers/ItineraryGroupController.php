<?php

namespace App\Http\Controllers;

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
            'created_at'=>'required|date',
        ];
        $this->operator_rule = [
            '_id'=>'required|string|max:24',
            'itinerary_content' => 'required|array|min:1',
            'guides' => 'present|array',
            'transportations' => 'present|array',
            'misc' => 'present|array'
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

        $result = $this->requestService->insert_one('itinerary_group', $validated); // 回傳是否建立成功


        // 團行程建立成功，需更改 order itinerary_group_id、cus_group_code
        $order = $this->requestService->get_one('cus_orders', $validated['order_id']); //找到要加入itinerary_group_id、cus_group_code 的 order 資料

        //撈出團行程
/*         $order_add = [];
        $order_add["itinerary_group_id"] = $itinerary_group['id']; */


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
        $itinerary = $this->requestService->update('itineraries', $validated);
        return $itinerary;

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
    public function operator(Request $request)
    {
        //傳團行程
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->operator_rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        // 叫出該_id資料
        $id = $validated['_id'];
        $data_before = $this->requestService->find_one('itinerary_group', $id, null, null);
        if($data_before===false){
            return response()->json(['error' => '此id沒有資料。'], 400);
        }
        $data_before = $data_before['document'];

        // TODO 供應商預定狀態判斷
        // 如何抓到所有元件
        //某元件 --- 客製化預定狀態: 0.未預定 -> 1 / 1.已預定 ->2 / 2.待確認退訂 ->3 / 3.已退訂 -> X
        if(array_key_exists('order_status',$validated) && $data_before['order_status'] !== $validated['order_status']){

        }

        // TODO 供應商付款狀態判斷

        // TODO 供應商待退/退款


    }
}
