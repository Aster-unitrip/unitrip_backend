<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestPService;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ComponentGuideController extends Controller
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

        // Handle "can_drive" field
        if (array_key_exists('can_drive', $filter)) {
            $pos = array("1", 1, "true", "True", true);
            $neg = array("0", 0, "false", "False", false);
            if (in_array($filter['can_drive'], $pos)){
                $filter['can_drive'] = true;
            }
            else if (in_array($filter['can_drive'], $neg)){
                $filter['can_drive'] = false;
            }
        }

        // Handle "language" field
        if (array_key_exists('languages', $filter)){
            $languages = $filter['languages'];
            $filter['languages'] = array('$in' => $languages);
        }


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
        }
        else{
            return response()->json(['error' => 'company_type must be 1 or 2'], 400);
        }

        // Handle projection content
        $projection = array(
            "_id" => 1,
            "gender" => 1,
            "name" => 1,
            "categories" => 1,
            "languages" => 1,
            "imgs" => 1,
            "fee" => 1,
            "cell_phone" => 1,
            "private" => 1,
            "created_at" => 1
        );
        $result = $this->requestService->aggregate_facet('guides', $projection, $filter, $page);
        return $result;
    }

}
