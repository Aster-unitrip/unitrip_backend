<?php
namespace App\Services;

use App\Models\ComponentCategory;

class ComponentCategoryService
{
    public function __construct(ComponentCategory $componentCategory)
    {
        $this->componentCategory = $componentCategory;
    }

    public function getParentCategories()
    {
        return $this->componentCategory->select('parent_category')->distinct()->get();
    }

    public function getChildCategories($parentCategory)
    {
        return $this->componentCategory->where('parent_category', $parentCategory)->get();
    }
}