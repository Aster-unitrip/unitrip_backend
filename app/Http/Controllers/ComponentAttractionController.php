<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttractionService;
use App\Services\RequestPService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

use Validator;

class ComponentAttractionController extends Controller
{
    private $attractionService;
    private $requestService;

    public function __construct(AttractionService $attractionService, RequestPService $requestPService)
    {
        $this->middleware('auth');
        $this->attractionService = $attractionService;
        $this->requestService = $requestPService;
    }

    // 旅行社使用者可以新增自己的子槽元件
    // 旅行社使用者可以選擇在同公司成員的搜尋結果裡顯示／隱藏子槽元件(is_enabled)
    // 旅行社使用者可以選擇是否將元件新增至母槽(is_display)
    public function add(Request $request)
    {
        $rule = [
            'name' => 'required|string|max:30',
            'website' => 'nullable|string|max:100',
            'tel' => 'required|string|max:20',
            'historic_level' => 'nullable|string|max:6',
            'org_name' => 'string|max:20',
            'categories' => 'required',
            'address_city' => 'required|string|max:4',
            'address_town' => 'required|string|max:10',
            'address' => 'required|string|max:30',
            'lng' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'bussiness_time' => 'nullable',
            'stay_time' => 'nullable|integer',
            'intro_summary' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:300',
            'ticket' => 'nullable',
            'memo' => 'nullable|string|max:4096',
            'parking' => 'nullable|string|max:500',
            'attention' => 'nullable|string|max:500',
            'experience' => 'nullable|string|max:500',
            'is_display' => 'required|boolean',
            'is_enabled' => 'required|boolean',
            'imgs' => 'nullable',
            'source' => 'nullable|string|max:10',
            'bank_info' => 'nullable',
            'bank_info.bank_name' => 'nullable|string|max:20',
            'bank_info.bank_code' => 'nullable|string|max:20',
            'bank_info.account_name' => 'nullable|string|max:20',
            'bank_info.account_number' => 'nullable|string|max:20',
        ];
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $rule);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $company_id = auth()->user()->company_id;
        $validated = $validator->validated();
        $validated['owned_by'] = $company_id;
        $attraction = $this->requestService->insert_one('attractions', $validated);
        return $attraction;

    }

    // 旅行社搜尋母槽：is_display == true
    // 旅行社搜尋子槽：is_display == false & owned_by == 自己公司 id & is_enabled == true
    // 旅行社搜尋母槽＆子槽：is_display == true or (owned_by == 自己公司 id & is_enabled == true)
    // 供應商只能得到母槽自己的元件
    public function list(Request $request)
    {
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
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access attractions list. Wrong identity.', ['request' => $request->all(), 'user' => auth()->user()->email]);
            return response()->json(['error' => 'Wrong identity.'], 400);
        }

        // Handle projection content
        $projection = array(
                "_id" => 1,
                "address_city" => 1,
                "address_town" => 1,
                "address" => 1,
                "categories" => 1,
                "name" => 1,
                "categories" => 1,
                "tel" => 1,
                "ticket" => 1,
                "imgs" => 1,
                "private" => 1,
                "intro_summary" => 1,
                "description" => 1,
                "experience" => 1,
                "is_display" => 1,
                "source" => 1,
                "updated_at" => 1,
                "created_at" => 1
            );
        $result = $this->requestService->aggregate_facet('attractions', $projection, $filter, $page);
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
        $result = $this->requestService->get_one('attractions', $id);
        $content =  json_decode($result->content(), true);
        if (auth()->payload()->get('company_type') == 1) {
            if ($content['is_display'] == false) {
                return response()->json(['error' => 'You can not access this attraction'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 2) {
            if ($content['is_display'] == false && $content['owned_by'] != $company_id) {
                return response()->json(['error' => 'You can not access this attraction'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 3) {

        } else {
            Log::warning('Suspicious activity: ' . auth()->user()->email . ' tried to access attractions list. Wrong identity.', ['user' => auth()->user()->email]);
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
        $rule = [
            '_id' => 'required|string|max:24',
            'name' => 'required|string|max:30',
            'website' => 'nullable|string|max:100',
            'tel' => 'required|string|max:20',
            'historic_level' => 'nullable|string|max:6',
            'org_name' => 'string|max:20',
            'categories' => 'required',
            'address_city' => 'required|string|max:4',
            'address_town' => 'required|string|max:10',
            'address' => 'required|string|max:30',
            'lng' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'bussiness_time' => 'nullable',
            'stay_time' => 'nullable|integer',
            'intro_summary' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:300',
            'ticket' => 'nullable',
            'memo' => 'nullable|string|max:4096',
            'parking' => 'nullable|string|max:500',
            'attention' => 'nullable|string|max:500',
            'experience' => 'nullable|st ring|max:500',
            'is_enabled' => 'required|boolean',
            'imgs' => 'nullable',
            'source' => ['required', Rule::in(['unitrip', 'supplier', 'gov', 'kkday', 'ota', 'ta'])],
            'bank_info' => 'nullable',
            'bank_info.bank_name' => 'nullable|string|max:20',
            'bank_info.bank_code' => 'nullable|string|max:20',
            'bank_info.account_name' => 'nullable|string|max:20',
            'bank_info.account_number' => 'nullable|string|max:20',
        ];
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();
        $company_id = auth()->user()->company_id;
        $validated['owned_by'] = $company_id;
        $record = $this->requestService->get_one('attractions', $validated['_id']);
        $content =  json_decode($record->content(), true);
        if (auth()->payload()->get('company_type') == 1) {
            if ($content['is_display'] == true && $content['owned_by'] == $company_id) {
                $attraction = $this->requestService->update('attractions', $validated);
            } else {
                return response()->json(['error' => 'You can not access this attraction'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 2) {
            if ($content['is_display'] == false && $content['owned_by'] == $company_id) {
                $attraction = $this->requestService->update('attractions', $validated);
            } else if ($content['is_display'] == true && $content['owned_by'] == $company_id) {
                $attraction = $this->requestService->update('attractions', $validated);
            } else {
                return response()->json(['error' => 'You can not access this attraction'], 400);
            }
        } else if (auth()->payload()->get('company_type') == 3) {
            $attraction = $this->requestService->update('attractions', $validated);
        } else {
            return response()->json(['error' => 'Wrong identity.'], 400);
        }
        // $attraction = $this->requestService->update('attractions', $validated);
        return $attraction;

    }

    // 把元件從子槽複製到母槽，要排除 experience, ticket 欄位
    // 母槽元件 ticket 只顯示票種不要票價
    // 先確認此元件屬不屬於他自己，而且必須是子槽資料
    public function copy_from_private_to_public(Request $request) {
        $query = json_decode($request->getContent(), true);
        $component = $this->requestService->get_one('attractions', $query['_id']);
        if ($component['is_display'] == false && $component['owned_by'] == auth()->user()->company_id) {
            $component['is_display'] == true;
            foreach($component as $key => $value){
                
            }
            unset($component['ticket']);
            unset($component['experience']);
            $attraction = $this->requestService->insert_one('attractions', $component);
            return response()->json([
                'message' => 'Successfully copied component to public',
                'id' => $query['_id']
            ]);
        } else {
            return response()->json(['error' => 'You can not access this component'], 400);
        }
    }

    // 把元件從母槽複製子槽
    public function copy_from_public_to_private(Request $request) {

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
        // Handle ticket prices
        if (array_key_exists('fee', $filter)) {
            if ($filter['fee']['free'] == true){
                $filter['ticket.free'] = true;
            }else if ($filter['fee']['free'] == false){
                $filter['ticket.free'] = false;
                $price_range = array();
                if (array_key_exists('price_max', $filter['fee'])){
                    $price_range['$lte'] = $filter['fee']['price_max'];
                }
                if (array_key_exists('price_min', $filter['fee'])){
                    $price_range['$gte'] = $filter['fee']['price_min'];
                }
                if (count($price_range) > 0){
                    $filter['ticket.full_ticket.price'] = $price_range;
                }
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
        $fields = ['address_city', 'address_town', 'name', 'categories', 'page', 'search_locatio', 'fee'];
        $new_filter = array();
        foreach ($filter as $key => $value) {
            if (in_array($key, $fields)) {
                $new_filter[$key] = $value;
            }
        }
        return $new_filter;
    }
}
