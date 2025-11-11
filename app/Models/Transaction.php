<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Observers\TransactionObserver;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_id',
        'type',
        'montant',
        'libelle',
        'description',
        'numero_destinataire',
        'code_marchand',
        'reference',
        'date_transaction',
        'statut',
    ];

    protected $casts = [
        'id' => 'string',
        'compte_id' => 'string',
        'montant' => 'decimal:2',
        'date_transaction' => 'datetime',
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

    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    protected static function booted(): void
    {
        static::observe(TransactionObserver::class);
    }
}
