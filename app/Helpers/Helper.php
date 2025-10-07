<?php

use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

if (!function_exists('paginateArray')) {
    function paginateArray($array, $perPage, $currentPage = null, $options = [])
    {
        
        $currentPage = $currentPage ?: (Paginator::resolveCurrentPage() ?: 1);
        $options['path'] = $options['path'] ?? Paginator::resolveCurrentPath();
        
        if(is_object($array)){
            $to_array = json_decode(json_encode($array), true);
        }else{
            $to_array = $array;
        }
        $paginator = new LengthAwarePaginator(
            array_slice($to_array, ($currentPage - 1) * $perPage, $perPage),
            count($to_array),
            $perPage,
            $currentPage,
            $options
        );

        return $paginator;
    }
}


