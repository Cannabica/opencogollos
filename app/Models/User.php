<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Filament\Panel;

use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements FilamentUser, HasTenants, JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if (!$tenant instanceof \App\Models\Tenant) {
            return false;
        }
        
        return $this->tenant_id === $tenant->id
            && $tenant->active;
    }

    public function getTenants(Panel $panel): array|Collection
    {
        return $this->tenant_id == null ? Tenant::all() : [$this->tenant];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'tenant') {
            // Permitir acceso al panel incluso si el tenant está inactivo
            // La lógica de redirección se manejará en un middleware o en el panel mismo
            return $this->tenant !== null;
        }

        if ($panel->getId() === 'superadmin') {
            return $this->tenant == null;
        }

        return true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'usage_type',
        'team_emails',
        'plants_per_cycle',
        'harvest_products',
        'force_password_change',
        'tenant_id',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['plants_per_cycle_description'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'harvest_products' => 'array',
        'force_password_change' => 'boolean',
        'plants_per_cycle' => 'integer',
    ];

    /**
     * Get the plants per cycle description
     */
    public function getPlantsPerCycleDescriptionAttribute(): ?string
    {
        $descriptions = [
            1 => '1-5 plantas',
            2 => '6-10 plantas',
            3 => '11-20 plantas',
            4 => '21-50 plantas',
            5 => 'Más de 50 plantas',
        ];

        return $descriptions[$this->plants_per_cycle] ?? null;
    }
}
