<?php

namespace App\Services\Product;

use Illuminate\Support\Collection;

class CategoryService
{
    public function productCategoryHierarchy($category, Collection $hierarchy)
    {
        $hierarchy->push($category);
        if (!empty($category->allParent)) {
            $this->productCategoryHierarchy($category->allParent, $hierarchy);
        }
        
        return $hierarchy->reverse();
    }
}
