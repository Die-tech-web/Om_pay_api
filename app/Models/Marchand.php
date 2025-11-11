<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marchand extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'code_marchand',
        'adresse',
        'telephone',
        'email',
        'type_commerce',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    /**
     * Scope pour récupérer seulement les marchands actifs
     */
    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }
}
