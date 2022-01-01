<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestService;
use App\Services\ItineraryService;
use Validator;

class ItineraryController extends Controller
{
    private $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->rule = [
            'name' => 'required|string|max:30',
            'summary' => 'nullable|string|max:150',
            'code' => 'nullable|string|max:20',
            'total_day' => 'required|integer|max:7',
            'areas' => 'nullable|array',
            'people_threshold' => 'required|integer|min:1',
            'people_full' => 'required|integer|max:100',
            'sub_categories' => 'nullable|array',
            'itinerary_content' => 'required|array|min:1',
            'guides' => 'required|array',
            'transportations' => 'required|array',
            'misc' => 'required|array',
            'accounting' => 'required|array',
            'include_description' => 'nullable|string|max:150',
            'exclude_description' => 'nullable|string|max:150',
        ];
        $this->edit_rule = array_push($this->rule, ['id'=>'required|string|max:24']) ;
        $this->edit_rule = array_push($this->rule, ['owned_by'=>'required|integer']) ;
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

        // 檢查行程內容
        // try{
        $is = new ItineraryService($validated);
        // } catch (\Exception $e) {
        //     return response()->json(['error' => $e->getMessage()], 400);
        // }

        $itinerary = $this->requestService->insert_one('itineraries', $validated);
        return $itinerary;

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
