<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestPService;
use Illuminate\Support\Facades\Log;

use Validator;

class ComponentAccomendationController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->add_rule = [
            'name' => 'required|string|max:30',
            'website' => 'nullable|string|max:100',
            'tel' => 'required|string|max:20',
            'address_city' => 'required|string|max:4',
            'address_town' => 'required|string|max:10',
            'address' => 'required|string|max:30',
            'room' => 'nullable',
            'star' => 'nullable|string|max:2',
            'total_rooms' => 'nullable|integer',
            'category' => 'nullable|string|max:10',
            'fax' => 'nullable|string|max:12',
            'lng' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'map_url' => 'nullable|string',
            'imgs' => 'nullable',
            'intro_summary' => 'nullable|string|max:150',
            'refund_rule' => 'nullable|string|max:300',
            'memo' => 'nullable|string|max:4096',
            'check_in' => 'nullable|string|max:10',
            'check_out' => 'nullable|string|max:10',
            'foc' => 'nullable|string|max:200',
            'service_content' => 'nullable|string|max:200',
            'facility' => 'nullable|string|max:200',
            'is_display' => 'required|boolean',
            'is_enabled' => 'required|boolean',
            'source' => 'nullable|string|max:10',
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
        Log::info('User add accomendation', ['user' => auth()->user()->email, 'request' => $request->all()]);
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->add_rule);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->all(),
            ], 400);
        }
        $company_id = auth()->user()->company_id;
        $validated = $validator->validated();
        $validated['owned_by'] = $company_id;
        $accomendation = $this->requestService->insert_one('accomendations', $validated);
        return $accomendation;
        
    }

    // 旅行社搜尋母槽：is_display == true
    // 旅行社搜尋子槽：is_display == false & owned_by == 自己公司 id & is_enabled == true
    // 旅行社搜尋母槽＆子槽：is_display == true or (owned_by == 自己公司 id & is_enabled == true)
    // 供應商只能得到母槽自己的元件
    public function list(Request $request)
    {
        Log::info('User list accomendation', ['user' => auth()->user()->email, 'request' => $request->all()]);
        if (auth()->payload()->get('company_type') == 1) {
            $filter = array(
                'is_display' => true,
                'owned_by' => auth()->user()->company_id
            );
            $page = 1;
        } else if (auth()->payload()->get('company_type') == 2) {
            
            $travel_agency_query = $this->travel_agency_search($request);
            $filter = $travel_agency_query['filter'];
            $page = $travel_agency_query['page'];
        } else if (auth()->payload()->get('company_type') == 3) {
            
        } else {
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access accomendation list', ['request' => $request->all(), 'user_id' => auth()->user()->id]);
            return response()->json(['error' => 'Wrong identity.'], 400);
        }

        // Handle projection content
        $projection = array(
            "_id" => 1,
            "address_city" => 1,
            "address_town" => 1,
            "address" => 1,
            "name" => 1,
            "tel" => 1,
            "category" => 1,
            "meals" => 1,
            "imgs" => 1,
            "room" => 1,
            "star" => 1,
            "private" => 1,
            "is_display" => 1,
            'updated_at' => 1,
            'created_at' => 1,
            "intro_summary" => 1,
            "description" => 1,
        );
        $result = $this->requestService->aggregate_facet('accomendations', $projection, $filter, $page);
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
        Log::info('User get accomendation by id', ['user' => auth()->user()->email, 'component_id' => $id]);
        $company_id = auth()->user()->company_id;
        $result = $this->requestService->get_one('accomendations', $id);
        $content =  json_decode($result->content(), true);
        if (auth()->payload()->get('company_type') == 1) {
            if ($content['is_display'] == false) {
                return response()->json(['error' => 'You can not access this accomendation'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 2) {
            if ($content['is_display'] == false && $content['owned_by'] != $company_id) {
                return response()->json(['error' => 'You can not access this accomendation'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 3) {

        } else {
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access accomendation list', ['user_id' => auth()->user()->id]);
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
    // 除了系統商以外的使用者都不能主動修改 is_display, owned_by
    // 若需母子槽異動，要使用 move_from_private_to_public 或 move_from_public_to_private
    public function edit(Request $request)
    {
        Log::info('User edit accomendation', ['user' => auth()->user()->email, 'request' => $request->all()]);
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $this->edit_rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();
        $company_id = auth()->user()->company_id;
        // Override owned_by
        $validated['owned_by'] = $company_id;
        $record = $this->requestService->get_one('accomendations', $validated['_id']);
        $content =  json_decode($record->content(), true);
        // Supplier
        if (auth()->payload()->get('company_type') == 1) {
            if ($content['is_display'] == true && $content['owned_by'] == $company_id) {
                $accomendation = $this->requestService->update('accomendations', $validated);
            } else {
                return response()->json(['error' => 'You can not access this accomendation'], 400);
            }
        // Travel agency
        } else if (auth()->payload()->get('company_type') == 2) {
            if ($content['is_display'] == false && $content['owned_by'] == $company_id) {
                $accomendation = $this->requestService->update('accomendations', $validated);
            } else if ($content['is_display'] == true && $content['owned_by'] == $company_id) {
                $accomendation = $this->requestService->update('accomendations', $validated);
            } else {
                return response()->json(['error' => 'You can not access this accomendation'], 400);
            }
        // System admin
        } else if (auth()->payload()->get('company_type') == 3) {
            $accomendation = $this->requestService->update('accomendations', $validated);
        } else {
            return response()->json(['error' => 'Wrong identity.'], 400);
        }
        return $accomendation;

    }

    protected function travel_agency_search(Request $request){
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
        // Handle star level
        if (array_key_exists('star', $filter)) {
            $star = $filter['star'];
            $filter['star'] = array("\$in" => $star);
        }
        // Handle room type
        if (array_key_exists('room_type', $filter)) {
            $room_type = $filter['room_type'];
            $filter['room'] = array('$elemMatch' => array('room_type' => array('$in' => $room_type)));
            unset($filter['room_type']);
        }

        if(array_key_exists('with_meals', $filter)){
            $with_meals = $filter['with_meals'];
            $filter['room'] = array('$elemMatch' => array('with_meals' => $with_meals));
            unset($filter['with_meals']);
        }

        // Handle accommendation category
        if (array_key_exists('category', $filter)) {
            $category = $filter['category'];
            $filter['category'] = array('$in' => $category);
        }


        // Handle room prices
        if (array_key_exists('fee', $filter)) {
            if ($filter['fee'] == array()) {
                unset($filter['fee']);
            }

            $price_range = array();
            if (array_key_exists('price_max', $filter['fee'])){
                $price_range['$lte'] = $filter['fee']['price_max'];
            }
            if (array_key_exists('price_min', $filter['fee'])){
                $price_range['$gte'] = $filter['fee']['price_min'];
            }
            if (!empty($price_range)){
                $filter['room'] = array('$all' => array(
                    array('$elemMatch' => array('holiday_price' => $price_range))
                ));
            }
        }
        unset($filter['fee']);

        if (array_key_exists('search_location', $filter)) {
            if ($filter['search_location'] == 'public') {
                $filter['is_display'] = true;
            } else if ($filter['search_location'] == 'private') {
                $filter['is_display'] = false;
                $filter['is_enabled'] = true;
                $filter['owned_by'] = auth()->user()->company_id;
                
            } else if ($filter['search_location'] == 'both') {
                $filter['$or'] = array(
                    array('is_display' => true),
                    array('is_enabled' => true, 'owned_by' => auth()->user()->company_id)
                );
            } else {
                return response()->json(['error' => 'search_location must be public, private or both'], 400);
            }
        } else if (!array_key_exists('search_location', $filter)) {
            $filter['$or'] = array(
                array('is_display' => true),
                array('is_enabled' => true, 'owned_by' => auth()->user()->company_id)
            );
        }

        return array('page'=>$page, 'filter'=>$filter);
    }

    // 刪除不必要的 key，避免回傳不該傳的資料
    public function ensure_query_key($filter) {
        $fields = ['address_city', 'address_town', 'name', 'category', 'star', 'room_type', 'with_meals', 'page', 'search_location', 'fee', 'is_display'];
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
        unset($rule['is_display']);
        return $rule;
    }
}
