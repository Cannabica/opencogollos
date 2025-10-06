<?php

namespace App\Models;

use App\Notifications\TenantActivationNotification;
use App\Notifications\TenantDeactivationNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'active',
        'user_type',
        'usage_type',
        'team_emails',
        'plants_per_cycle',
        'harvest_products',
        'activated_at',
        'owner_id'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function indoors()
    {
        return $this->hasMany(Indoor::class);
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::saved(function ($tenant) {
            static::handleTenantActivationChanges($tenant);
        });
    }

    /**
     * Handle tenant activation/deactivation changes
     */
    protected static function handleTenantActivationChanges($tenant)
    {
        // Only proceed if the active attribute was changed
        if (!$tenant->isDirty('active')) {
            return;
        }

        $originalActive = $tenant->getOriginal('active');
        $newActive = $tenant->active;

        Log::debug('Tenant activation change detected in model', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'original_active' => $originalActive,
            'new_active' => $newActive,
            'tenant_email' => $tenant->email
        ]);

        // Check if tenant was just activated (from inactive to active)
        if (!$originalActive && $newActive) {
            Log::debug('Tenant activated - sending notification', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name
            ]);

            // Get the main user - try owner first, then first user
            $mainUser = $tenant->owner;
            if (!$mainUser) {
                $mainUser = $tenant->users()->first();
            }

            Log::debug('Main user search for activation in model', [
                'tenant_id' => $tenant->id,
                'tenant_email' => $tenant->email,
                'main_user_found' => !is_null($mainUser),
                'main_user_id' => $mainUser?->id,
                'main_user_email' => $mainUser?->email,
                'user_type' => $mainUser ? ($mainUser->id === $tenant->owner_id ? 'owner' : 'first_user') : 'none'
            ]);

            if ($mainUser) {
                try {
                    Log::debug('Attempting to send tenant activation notification from model', [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'user_email' => $mainUser->email
                    ]);
                    
                    $mainUser->notify(new TenantActivationNotification($tenant->name));
                    Log::info('Tenant activation notification sent from model', [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'user_email' => $mainUser->email
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send tenant activation notification from model: ' . $e->getMessage(), [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'exception' => $e->getTraceAsString()
                    ]);
                }
            } else {
                Log::warning('Main user not found for tenant activation notification in model', [
                    'tenant_id' => $tenant->id,
                    'tenant_email' => $tenant->email,
                    'available_users' => $tenant->users()->pluck('email')->toArray()
                ]);
            }
        }

        // Check if tenant was just deactivated (from active to inactive)
        if ($originalActive && !$newActive) {
            Log::debug('Tenant deactivated - sending notification', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name
            ]);

            // Get the main user - try owner first, then first user
            $mainUser = $tenant->owner;
            if (!$mainUser) {
                $mainUser = $tenant->users()->first();
            }

            Log::debug('Main user search for deactivation in model', [
                'tenant_id' => $tenant->id,
                'tenant_email' => $tenant->email,
                'main_user_found' => !is_null($mainUser),
                'main_user_id' => $mainUser?->id,
                'main_user_email' => $mainUser?->email,
                'user_type' => $mainUser ? ($mainUser->id === $tenant->owner_id ? 'owner' : 'first_user') : 'none'
            ]);

            if ($mainUser) {
                try {
                    Log::debug('Attempting to send tenant deactivation notification from model', [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'user_email' => $mainUser->email
                    ]);
                    
                    $mainUser->notify(new TenantDeactivationNotification($tenant->name));
                    Log::info('Tenant deactivation notification sent from model', [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'user_email' => $mainUser->email
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send tenant deactivation notification from model: ' . $e->getMessage(), [
                        'tenant_id' => $tenant->id,
                        'user_id' => $mainUser->id,
                        'exception' => $e->getTraceAsString()
                    ]);
                }
            } else {
                Log::warning('Main user not found for tenant deactivation notification in model', [
                    'tenant_id' => $tenant->id,
                    'tenant_email' => $tenant->email,
                    'available_users' => $tenant->users()->pluck('email')->toArray()
                ]);
            }
        }
    }
}
