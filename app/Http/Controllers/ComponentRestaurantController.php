<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestPService;
use Illuminate\Validation\Rule;
use App\Services\ComponentLogService;
use Illuminate\Support\Facades\Log;

use Validator;

class ComponentRestaurantController extends Controller
{

    private $requestService;

    public function __construct(RequestPService $requestService, ComponentLogService $componentLogService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->componentLogService = $componentLogService;
        $this->add_rule = [
            'name' => 'required|string|max:30',
            'experience' => 'nullable|string|max:500',
            'website' => 'nullable|string|max:100',
            'tel' => 'required|string|max:20',
            'fax' => 'nullable|string|max:10',
            'email' => 'nullable|string|max:30',
            'address_city' => 'required|string|max:4',
            'address_town' => 'required|string|max:10',
            'address' => 'required|string|max:30',
            'business_time' => 'nullable',
            'categories' => 'array',
            'cost_per_person' => 'nullable',
            'cost_per_person.min_cost_per_person' => 'nullable|integer|min:0',
            'cost_per_person.max_cost_per_person' => 'nullable|integer',
            'has_vegetarian_meal' => 'nullable|boolean',
            'latest_reserve_day' => 'nullable|integer',
            'imgs' => 'required|array',
            'intro_summary' => 'nullable|string',
            'description' => 'nullable|string',
            'stay_time' => 'nullable|integer',
            'lng' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            // 餐型
            'meals' => 'nullable',
            'meals.name' => 'nullable|string|max:30',
            'meals.imgs' => 'nullable|array',//
            'meals.type' => 'nullable|string|max:10',
            'meals.supply_people' => 'nullable|integer',
            'meals.status' => 'nullable|string|max:10',
            'meals.content' => 'nullable|string|max:100',
            'meals.memo' => 'nullable|string|max:50',
            'meals.price' => 'nullable|integer|min:0',
            'refund_rule' => 'nullable|string|max:300',
            'memo' => 'nullable|string|max:300',
            'driver_tour_memo' => 'nullable|string|max:50',
            'is_display' => 'required|boolean',
            'is_enabled' => 'required|boolean',
            'bank_info' => 'array',
            'bank_info.sort' => 'nullable|string|max:20',
            'bank_info.bank_name' => 'nullable|string|max:20',
            'bank_info.bank_code' => 'nullable|string|max:20',
            'bank_info.account_name' => 'nullable|string|max:20',
            'bank_info.account_number' => 'nullable|string|max:20',
        ];
        $this->edit_rule = $this->generate_edit_rule_from_add_rule($this->add_rule);
    }

    // 旅行社使用者可以新增自己的子槽元件
    // 旅行社使用者可以選擇在同公司成員的搜尋結果裡顯示／隱藏子槽元件(is_enabled)
    // 旅行社使用者可以選擇是否將元件新增至母槽(is_display)
    public function add(Request $request)
    {

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->add_rule);
        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 400);
        }
        $company_id = auth()->user()->company_id;
        $validated = $validator->validated();

        $validated['owned_by'] = $company_id;
        $validated['last_updated_on'] = auth()->user()->contact_name;
        $validated['source'] = "ta"; //旅行社預設為ta

        if(!array_key_exists("position", $validated)){
            $validated['position'] = null;
        }

        $restaurant = $this->requestService->insert_one('restaurants', $validated);
        $restaurant =  json_decode($restaurant->content(), true);


        // 建立 Log
        $restaurant = $this->requestService->get_one('restaurants', $restaurant['inserted_id']);
        $restaurant =  json_decode($restaurant->content(), true);
        $filter = $this->componentLogService->recordCreate('restaurants', $restaurant);
        $create_components_log = $this->requestService->insert_one("components_log", $filter);
        Log::info('User add restaurant', ['user' => auth()->user()->email, 'request' => $request->all()]);
        return $restaurant;

    }

    // 旅行社搜尋母槽：is_display == true
    // 旅行社搜尋子槽：is_display == false & owned_by == 自己公司 id & is_enabled == true
    // 旅行社搜尋母槽＆子槽：is_display == true or (owned_by == 自己公司 id & is_enabled == true)
    // 供應商只能得到母槽自己的元件
    public function list(Request $request)
    {
        if(auth()->payload()->get('company_type') == 1){
            $filter = array(
                'is_display' => true,
                'owned_by' => auth()->user()->company_id
            );
            $page = 1;
        }
        else if(auth()->payload()->get('company_type') == 2){
            $travel_agency_query = $this->travel_agency_search($request);
            $filter = $travel_agency_query['filter'];
            $page = $travel_agency_query['page'];
        }
        else if(auth()->payload()->get('company_type') == 3){

        }
        else{
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access restaurants list. Wrong identity.', ['request' => $request->all(), 'user' => auth()->user()->email]);
            return response()->json(['error' => 'Wrong identity.'], 400);
        }

        // Handle projection content
        $projection = array(
                "_id" => 1,
                "name" => 1,
                "address_city" => 1,
                "address_town" => 1,
                "address" => 1,
                "categories" => 1,
                "tel" => 1,
                "meals" => 1,
                "business_time" => 1,
                "imgs" => 1,
                "private" => 1,
                "intro_summary" => 1,
                "description" => 1,
                "experience" => 1,
                "is_display" => 1,
                "source" => 1,
                "position"=> 1,
                "cost_per_person" => 1,
                "updated_at" => 1,
                "created_at" => 1
            );
        // 餐廳名稱模糊搜尋
        if(array_key_exists('name', $filter)){
            // $filter['name'] = array('$regex' => $filter['name'], '$options' => 'i');
            $filter['name'] = array('$regex' => $filter['name']);
        }
        $result = $this->requestService->aggregate_facet('restaurants', $projection, $filter, $page);

        // 相容舊格式
        $current_data = $result->getData();
        foreach($current_data->docs as $doc){
            $doc->private = array('experience' => '');
        }
        $result->setData($current_data);

        return $result;
    }

    // 供應商(type: 1)只能得到母槽元件
    // 旅行社(type: 2)搜尋無法存取子槽非自己公司的元件
    // 系統商(type: 3)可以得到所有元件
    public function get_by_id($id)
    {
        $company_id = auth()->user()->company_id;
        $result = $this->requestService->get_one('restaurants', $id);
        $content =  json_decode($result->content(), true);
        if (auth()->payload()->get('company_type') == 1) {
            if ($content['is_display'] == false) {
                return response()->json(['error' => 'You can not access this restaurant'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 2) {
            if ($content['is_display'] == false && $content['owned_by'] != $company_id) {
                return response()->json(['error' => 'You can not access this restaurant'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 3) {

        } else {
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access restaurants list. Wrong identity.', ['user' => auth()->user()->email]);
            return response()->json(['error' => 'Wrong identity.'], 400);
        }

        if (array_key_exists('imgs', $content)){
            foreach ($content['imgs'] as $value){
                $n = 0;
                $split_url = explode('/', $value['url']);
                $content['imgs'][$n]['filename'] = end($split_url);
            }
        }

        return $content;
    }

    // 供應商(type: 1)只能編輯自己的元件
    // 旅行社使用者可以編輯自己的子槽元件
    // 旅行社使用者不能編輯母槽元件
    // 系統商可以修改所有元件
    // 除了系統商以外的使用者都不能主動修改 is_display
    // 若需母子槽異動，要使用 move_from_private_to_public 或 move_from_public_to_private
    public function edit(Request $request)
    {

        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->edit_rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $validated = $validator->validated();
        $company_id = auth()->user()->company_id;
        $validated['owned_by'] = $company_id;
        $validated['last_updated_on'] = auth()->user()->contact_name;


        $record = $this->requestService->get_one('restaurants', $validated['_id']);
        $content = json_decode($record->content(), true);

        if(array_key_exists('count', $content) && $content['count'] === 0){
            return response()->json(['error' => 'This _id is not correct.']);
        }

        if(auth()->payload()->get('company_type') == 1){  // 供應商
            if($content['is_display'] == true && $content['owned_by'] == $company_id){ // 母槽
                $restaurant = $this->requestService->update_one('restaurants', $validated);
            }
            else{
                return response()->json(['error' => 'You can not access this restaurant'], 400);
            }
        }
        else if(auth()->payload()->get('company_type') == 2){ // 旅行社
            if($content['is_display'] == false && $content['owned_by'] == $company_id){ // 子槽
                $restaurant = $this->requestService->update_one('restaurants', $validated);
            }
            else if($content['is_display'] == true && $content['owned_by'] == $company_id){ // 母槽
                $restaurant = $this->requestService->update_one('restaurants', $validated);
            }
            else{
                return response()->json(['error' => 'You can not access this restaurant'], 400);
            }
        }
        else if(auth()->payload()->get('company_type') == 3){ // 系統商
            $restaurant = $this->requestService->update_one('restaurants', $validated);
        }
        else{
            return response()->json(['error' => 'Wrong identity.'], 400);
        }

        return $restaurant;

    }

    private function travel_agency_search(Request $request) {
        // Handle filter content
        $filter = json_decode($request->getContent(), true);
        $filter  = $this->ensure_query_key($filter);
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

        // Handle meal types
        if (array_key_exists('meal_type', $filter)) {
            $meal_type = $filter['meal_type'];
            if($meal_type === "合菜" || $meal_type === "套餐"){
                $filter['meals'] = array('$elemMatch' => array('type' => $meal_type));
            }
            else if($meal_type === "全部"){

            }
            unset($filter['meal_type']);
        }

        // Handle cost_per_person range
        if (array_key_exists('cost_per_person', $filter)){
            if(array_key_exists('price_min', $filter['cost_per_person'])){
                $price_range_min['$gte'] = $filter['cost_per_person']['price_min'];
            }
            if(array_key_exists('price_max', $filter['cost_per_person'])){
                $price_range_max['$lte'] = $filter['cost_per_person']['price_max'];

            }
            unset($filter['cost_per_person']);
            if(!empty($price_range_min)){
                $filter['cost_per_person.min_cost_per_person'] = $price_range_min;
            }
            if(!empty($price_range_max)){
                $filter['cost_per_person.max_cost_per_person'] = $price_range_max;
            }
        }

        // Handle search_location
        if(array_key_exists('search_location', $filter)){
            if($filter['search_location'] == 'public'){
                $filter['is_display'] = true;
            }
            else if($filter['search_location'] == 'private'){
                $filter['is_display'] = false;
                $filter['owned_by'] = auth()->user()->company_id;
            }
            else if($filter['search_location'] == 'all'){
                $filter['$or'] = array(
                    array('is_display' => true),
                    array('owned_by' => auth()->user()->company_id)
                );
            }
            else if($filter['search_location'] == 'enabled'){
                $filter['$or'] = array(
                    array('is_display' => true),
                    array('is_enabled' => true, 'owned_by' => auth()->user()->company_id)
                );
            }
            else{
                return response()->json(['error' => 'search_location must be public, private or all'], 400);
            }
        }
        else if(!array_key_exists('search_location', $filter)){
            $filter['$or'] = array(
                array('is_display' => true),
                array('is_enabled' => true, 'owned_by' => auth()->user()->company_id)
            );
        }
        unset($filter['search_location']);

        return array('page'=>$page, 'filter'=>$filter);
    }

    // 刪除不必要的 key，避免回傳不該傳的資料
    public function ensure_query_key($filter) {
        $fields = ['address_city', 'address_town', 'name', 'categories', 'page', 'search_location', 'meal_type', 'cost_per_person'];
        $new_filter = array();
        foreach ($filter as $key => $value) {
            if (in_array($key, $fields)) {
                $new_filter[$key] = $value;
            }
        }
        return $new_filter;
    }

    protected function generate_edit_rule_from_add_rule($rule)
    {
        $add_rule = [
            "_id" => 'required|string'
        ];
        $rule += $add_rule;
        // unset($rule['is_display']);
        return $rule;
    }
}
