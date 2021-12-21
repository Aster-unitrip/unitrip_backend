<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RequestService;

use Validator;

class MongoPipelineController extends Controller
{
    private $pipeline;

    public function __construct($filter)
    {   
        $this->filter = $filter;
        $this->page_filter_builder();

    }

    // 處理一般 query，query field name = field name
    // ex. query: {"name":"凱撒飯店"}  db field name: {"name":"凱撒飯店"}
    private function basic_filter_builder()
    {

    }

    private function page_filter_builder()
    {
        if (array_key_exists('page', $this->filter)) 
        {
            $page = $this->filter['page'];
            if ($page <= 0) {
                return response()->json(['error' => 'page must be greater than 0'], 400);
            }
            else{
                $page = $page - 1;
            }
        }
        else
        {
            $page = 0;
        }
        $this->pipeline['page'] = $page;
        unset($this->filter['page']);
    }

    private function fee_filter_builder()
    {
            
    }
}