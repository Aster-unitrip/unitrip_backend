<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttractionService;
use App\Services\RequestService;

use Validator;

class ComponentAttractionController extends Controller
{
    private $attractionService;
    private $requestService;

    public function __construct(AttractionService $attractionService, RequestService $requestService)
    {
        $this->middleware('auth');
        $this->attractionService = $attractionService;
        $this->requestService = $requestService;
    }

    public function add(Request $request)
    {
        $rule = [
            'name' => 'required|string|max:30',
            'website' => 'nullable|string|max:100',
            'tel' => 'required|string|max:20',
            'historic_level' => 'nullable|string|max:6',
            'org_name' => 'required|string|max:20',
            'categories' => 'required',   // 要拿掉，改為用表來存
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
            'is_display' => 'required|integer',
            'imgs' => 'nullable',

        ];
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();

        $attraction = $this->attractionService->create($validated);

        if (array_key_exists('error', $attraction)) {
            return response()->json(['error' => $attraction['error']], 400);
        }
        else{
            return response()->json(
                [
                    'success' => 'success',
                    'data' => [
                        'id' => $attraction['id'],
                        'name' => $attraction['name']
                    ]
    
                ], 200);
        }


        
    }

    public function add2(Request $request)
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
        $filter['owned_by'] = auth()->user()->company_id;
        
        $projection = array(
                "_id" => 1,
                "address_city" => 1,
                "address_town" => 1,
                "name" => 1,
            );
        $result = $this->requestService->get_all('attractions', $projection, $filter, $page);
        return $result;
    }

    public function get_by_id($id)
    {
        $result = $this->requestService->get_one('attractions', $id);
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

    public function edit(Request $request)
    {
        $rule = [
            '_id' => 'required|string|max:24',
            'name' => 'required|string|max:30',
            'website' => 'nullable|string|max:100',
            'tel' => 'required|string|max:20',
            'historic_level' => 'nullable|string|max:6',
            'org_name' => 'required|string|max:20',
            'categories' => 'required',   // 要拿掉，改為用表來存
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
            'is_display' => 'required|integer',
            'images' => 'nullable',

        ];
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();
        $attraction = $this->requestService->update('attractions', $validated);
        return $attraction;

    }
}
