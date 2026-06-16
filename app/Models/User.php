<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Champs autorisés à la saisie en masse */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /** Champs cachés dans les réponses JSON */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Casts automatiques */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // hashage auto
        ];
    }

    // ─── Relations ────────────────────────────────────────
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function charges()
    {
        return $this->hasMany(Charge::class);
    }

    // ─── Helpers ──────────────────────────────────────────
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
