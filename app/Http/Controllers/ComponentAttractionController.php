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
            'org_id' => 'required|string|max:20',
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
            'org_id' => 'required|string|max:20',
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

        ];
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $rule);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $validated = $validator->validated();
        $attraction = $this->requestService->insert_one($validated);
        return response()->json($attraction);

    }

    public function list(Request $request)
    {
        $address_city = $request->address_city;
        $address_town = $request->address_town;
        $name = $request->name;
        $categories = $request->categories;
        $is_display = $request->is_display;

        $result = $this->requestService->get_all();
        return response()->json($result);
    }

    public function get_by_id($id)
    {
        $result = $this->requestService->get_one($id);
        return response()->json($result);
    }

    public function edit(Request $request)
    {
        $rule = [
            '_id' => 'required|string|max:24',
            'name' => 'required|string|max:30',
            'website' => 'nullable|string|max:100',
            'tel' => 'required|string|max:20',
            'historic_level' => 'nullable|string|max:6',
            'org_id' => 'required|string|max:20',
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
        $attraction = $this->requestService->update($validated);
        return response()->json($attraction);

    }
}
