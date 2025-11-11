<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Champs remplissables en masse.
     */
    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'email',
        'role',
    ];

    /**
     * Champs cachés lors du retour JSON.
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * Conversions automatiques de types.
     */
    protected $casts = [
        'id' => 'string',
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

    /**
     * Relations
     */
    public function comptes()
    {
        return $this->hasMany(Compte::class, 'id_client');
    }
}
