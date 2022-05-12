<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestPService;

use Validator;

class ComponentTransportationController extends Controller
{
    private $requestService;

    public function __construct(RequestPService $requestService)
    {
        $this->middleware('auth');
        $this->requestService = $requestService;
    }

    public function list(Request $request)
    {
        // Handle filter content
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


        // unset($filter['fee']);
        // Company_type: 1, Query public components belong to the company
        // Company_type: 2, Query all public components and private data belong to the company
        $company_type = auth()->payload()->get('company_type');
        $company_id = auth()->payload()->get('company_id');
        if ($company_type == 1){
            $filter['owned_by'] = auth()->user()->company_id;
            $query_private = false;
        }
        else if ($company_type == 2){
            $query_private = true;
            $filter['is_display'] = true;
        }
        else{
            return response()->json(['error' => 'company_type must be 1 or 2'], 400);
        }

        // Handle projection content
        $projection = array(
            "_id" => 1,
            "transportation_rental_agency" => 1,
            "model" => 1,
            "passenger_seats" => 1,
            "fee" => 1,
            "imgs" => 1,
            "tel" => 1,
            "address_city" => 1,    // 可能得從公司資料撈
            "address_town" => 1,    // 可能得從公司資料撈
            "address" => 1,         // 可能得從公司資料撈
            "private" => 1,
            "created_at" => 1
        );
        $result = $this->requestService->aggregate_facet('transportations', $projection, $filter, $page);
        return $result;
    }
}
