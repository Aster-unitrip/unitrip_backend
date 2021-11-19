<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ComponentCategoryService;

class ComponentCategoryController extends Controller
{
    private $componentCategoryService;

    public function __construct(ComponentCategoryService $componentCategoryService)
    {
        $this->middleware('auth');
        $this->componentCategoryService = $componentCategoryService;
    }

    public function parentCategories()
    {
        return $this->componentCategoryService->getParentCategories();
    }

    public function childCategories(Request $request)
    {
        if ($request->has('parent_category')) {
            return $this->componentCategoryService->getChildCategories($request->input('parent_category')); 
        }
    }

}
