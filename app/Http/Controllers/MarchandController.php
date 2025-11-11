<?php

namespace App\Http\Controllers;

use App\Models\Marchand;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class MarchandController extends Controller
{
    use ApiResponseTrait;

    /**
     * Récupérer la liste des marchands actifs
     */
    public function index()
    {
        $marchands = Marchand::actifs()
            ->select('id', 'nom', 'code_marchand', 'type_commerce')
            ->orderBy('nom')
            ->get();

        return $this->successResponse($marchands, 'Marchands récupérés avec succès');
    }

    /**
     * Récupérer un marchand spécifique
     */
    public function show($id)
    {
        $marchand = Marchand::find($id);

        if (!$marchand) {
            return $this->errorResponse('Marchand non trouvé', 404);
        }

        return $this->successResponse($marchand, 'Marchand récupéré avec succès');
    }
}
