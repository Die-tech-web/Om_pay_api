<?php

namespace App\Http\Traits;

trait ApiResponseTrait
{
    protected function successResponse($data = [], $message = 'Succès', $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse($message = 'Erreur', $code = 400, $errors = [])
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Formate la pagination dans le format souhaité
     */
    protected function formatPagination($paginator)
    {
        return [
            'pagination' => [
                'total_items' => $paginator->total(),
                'items_per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'has_previous' => $paginator->hasPreviousPage(),
                'has_next' => $paginator->hasNextPage(),
                'links' => [
                    'first' => $paginator->url(1),
                    'previous' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                    'last' => $paginator->url($paginator->lastPage())
                ]
            ]
        ];
    }
}
