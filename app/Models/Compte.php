<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_client',
        'numero_compte',
        'code_pin',
        'type',
        'date_creation',
        'statut',
        'metadata',
    ];

    protected $casts = [
        'id' => 'string',
        'id_client' => 'string',
        'date_creation' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Boot du modèle pour générer automatiquement les UUIDs
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->getKey())) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_client');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'compte_id');
    }

    public function getSoldeAttribute(): float
    {
        return $this->transactions()
            ->where('statut', 'validee')
            ->get()
            ->sum(function ($transaction) {
                return match ($transaction->type) {
                    'depot' => $transaction->montant,
                    'retrait', 'paiement', 'transfert', 'frais' => -$transaction->montant,
                    default => 0,
                };
            });
    }
}
