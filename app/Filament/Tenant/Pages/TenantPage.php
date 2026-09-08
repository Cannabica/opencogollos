<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Services\TenantTokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\TeamUserActivationNotification;
use Illuminate\Support\Facades\Notification;

class TenantPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static string $view = 'filament.tenant.pages.tenant';
    protected static ?string $navigationLabel = 'Mi Grupo';
    protected static ?string $title = 'Información del grupo';
    protected static ?int $navigationSort = 999;

    protected static ?string $navigationGroup = 'Grupo de trabajo';
    public $tenant;
    public $users;
    public $apiTokens;
    public $newToken;
    public $user_type;
    public $usage_type;
    public $team_emails;
    public $plants_per_cycle;
    public $harvest_products;
    public $activated_at;

    // User management properties
    public $editingUser = null;
    public $userName = '';
    public $userEmail = '';
    public $showUserForm = false;
    public $isOwner = false;

    public function mount()
    {
        $this->tenant = Auth::user()->tenant;
        $this->users = $this->tenant->users()->get();
        $this->apiTokens = $this->tenant->apiTokens()
            ->orderByDesc('created_at')
            ->get();
        
        // Populate registration fields from tenant data
        $this->user_type = $this->tenant->user_type;
        $this->usage_type = $this->tenant->usage_type;
        $this->team_emails = $this->tenant->team_emails;
        $this->plants_per_cycle = $this->tenant->plants_per_cycle;
        $this->harvest_products = $this->tenant->harvest_products;
        $this->activated_at = $this->tenant->activated_at;

        // Check if current user is tenant owner
        $this->isOwner = Auth::user()->isTenantOwner();
        
        // Debug ownership information
        $this->debugOwnershipInfo();
    }

    public $tokenReference;

    public function generateToken()
    {
        $this->validate([
            'tokenReference' => 'nullable|string|max:255'
        ]);
        
        $this->newToken = app(TenantTokenService::class)->generateToken(
            $this->tenant,
            $this->tokenReference
        );
        
        $this->tokenReference = ''; // Clear input after generation
        $this->apiTokens = $this->tenant->apiTokens()
            ->orderByDesc('created_at')
            ->get();
    }

    public function revokeToken($tokenId)
    {
        $this->tenant->apiTokens()->where('id', $tokenId)->delete();
        $this->apiTokens = $this->tenant->apiTokens()
            ->orderByDesc('created_at')
            ->get();
    }

    public function renewToken($tokenId)
    {
        $token = $this->tenant->apiTokens()->find($tokenId);
        if ($token && $token->renew_count < 10) {
            $this->newToken = app(TenantTokenService::class)->renewToken($token->token_hash);
            if ($this->newToken) {
                $this->apiTokens = $this->tenant->apiTokens()
                    ->orderByDesc('created_at')
                    ->get();
                $this->dispatch('token-renewed');
            }
        }
    }

    // User management methods
    public function addUser()
    {
        if (!$this->isOwner) {
            return;
        }

        $this->validate([
            'userName' => 'required|string|max:255',
            'userEmail' => 'required|email|unique:users,email'
        ]);

        // Generate temporary password
        $tempPassword = Str::random(12);

        $user = \App\Models\User::create([
            'name' => $this->userName,
            'email' => $this->userEmail,
            'password' => Hash::make($tempPassword),
            'tenant_id' => $this->tenant->id,
            'force_password_change' => true
        ]);

        // Send activation notification
        Notification::send($user, new TeamUserActivationNotification($tempPassword));

        $this->resetUserForm();
        $this->users = $this->tenant->users()->get();
    }

    public function editUser($userId)
    {
        if (!$this->isOwner) {
            return;
        }

        $user = \App\Models\User::find($userId);
        if ($user && $user->canBeManagedBy(Auth::user())) {
            $this->editingUser = $user;
            $this->userName = $user->name;
            $this->userEmail = $user->email;
            $this->showUserForm = true;
        }
    }

    public function updateUser()
    {
        if (!$this->isOwner || !$this->editingUser) {
            return;
        }

        $this->validate([
            'userName' => 'required|string|max:255',
            'userEmail' => 'required|email|unique:users,email,' . $this->editingUser->id
        ]);

        if ($this->editingUser->canBeManagedBy(Auth::user())) {
            $this->editingUser->update([
                'name' => $this->userName,
                'email' => $this->userEmail
            ]);
        }

        $this->resetUserForm();
        $this->users = $this->tenant->users()->get();
    }

    public function resetPassword($userId)
    {
        if (!$this->isOwner) {
            return;
        }

        $user = \App\Models\User::find($userId);
        if ($user && $user->canBeManagedBy(Auth::user())) {
            // Generate new temporary password
            $tempPassword = Str::random(12);
            
            $user->update([
                'password' => Hash::make($tempPassword),
                'force_password_change' => true
            ]);

            // Send password reset notification
            Notification::send($user, new TeamUserActivationNotification($tempPassword));
        }

        $this->users = $this->tenant->users()->get();
    }

    public function forcePasswordChange($userId)
    {
        if (!$this->isOwner) {
            return;
        }

        $user = \App\Models\User::find($userId);
        if ($user && $user->canBeManagedBy(Auth::user())) {
            $user->update([
                'force_password_change' => true
            ]);
        }

        $this->users = $this->tenant->users()->get();
    }

    public function removeUser($userId)
    {
        if (!$this->isOwner) {
            return;
        }

        $user = \App\Models\User::find($userId);
        if ($user && $user->canBeManagedBy(Auth::user())) {
            $user->delete();
        }

        $this->users = $this->tenant->users()->get();
    }

    public function resetUserForm()
    {
        $this->editingUser = null;
        $this->userName = '';
        $this->userEmail = '';
        $this->showUserForm = false;
    }

    public function cancelEdit()
    {
        $this->resetUserForm();
    }

    /**
     * Debug method to display ownership information
     * Helps diagnose why user management interface might not be showing
     */
    public function debugOwnershipInfo()
    {
        $user = Auth::user();
        $tenant = $this->tenant;
        
        $debugInfo = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'tenant_id' => $tenant->id,
            'tenant_email' => $tenant->email,
            'email_comparison' => $user->email === $tenant->email,
            'isOwner' => $this->isOwner,
            'isOwner_method_result' => $user->isTenantOwner(),
            'tenant_owner_email' => $tenant->owner ? $tenant->owner->email : 'No owner set',
            'tenant_active' => $tenant->active,
            'tenant_users_count' => $tenant->users()->count(),
            'current_time' => now()->toISOString()
        ];

        // Log debug information
        \Log::debug('TenantPage Ownership Debug', $debugInfo);

    }

}