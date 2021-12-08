<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestService;
use Validator;

class ItineraryController extends Controller
{
    private $requestService;
    
    public function __construct(RequestService $requestService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
    }

    public function add(Request $request)
    {
        $rule = [
            'name' => 'required|string|max:30',
            'summary' => 'nullable|string|max:150',
            'code' => 'nullable|string|max:20',
            'total_day' => 'required|integer|max:7',
            'areas' => 'nullable|array',
            'people_threshold' => 'required|integer|min:1',
            'people_full' => 'required|integer|max:100',
            'sub_categories' => 'nullable|array',
            'itinerary_content' => 'required|array',
            'accounting' => 'required|array',
            'include_description' => 'nullable|string|max:150',
            'exclude_description' => 'nullable|string|max:150',

        ];
        $data = json_decode($request->getContent(), true);
        $validator = Validator::make($data, $rule);
        
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $company_id = auth()->user()->company_id;
        $validated = $validator->validated();
        $validated['owned_by'] = $company_id;
        $attraction = $this->requestService->insert_one('itineraries', $validated);
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

        if (auth()->user()->company->company_type == 1){
            $filter['owned_by'] = auth()->user()->company_id;
        }
        
        
        $projection = array(
                "_id" => 1,
                "address_city" => 1,
                "address_town" => 1,
                "name" => 1,
            );
        $result = $this->requestService->aggregate_filter('itineraries', $projection, $filter, $page);
        return $result;
    }
}