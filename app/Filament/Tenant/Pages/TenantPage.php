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
    /*
    |--------------------------------------------------------------------------
    | Datos del GRUPO (edición inline, sólo el owner)
    |--------------------------------------------------------------------------
    |
    | El nombre y el email del grupo son la identidad del grupo de trabajo, no un dato personal: los
    | edita **sólo el owner** (misma regla que la gestión de usuarios de esta página). El email del
    | grupo NO es credencial de login, así que no pide contraseña ni doble opt-in — pero sí queda en
    | la trazabilidad de seguridad, porque cambia el canal de contacto del grupo.
    */
    public $editingTenant = false;

    public $tenantName = '';

    public $tenantEmail = '';

    public function editTenant(): void
    {
        if (! $this->isOwner) {
            return;
        }

        $this->editingTenant = true;
        $this->tenantName = $this->tenant->name;
        $this->tenantEmail = $this->tenant->email;
    }

    public function cancelTenantEdit(): void
    {
        $this->editingTenant = false;
        $this->tenantName = '';
        $this->tenantEmail = '';
    }

    public function updateTenant(): void
    {
        if (! $this->isOwner) {
            return;
        }

        $this->validate([
            'tenantName' => 'required|string|max:255',
        ]);

        // El email del grupo NO se edita desde acá a propósito: `isTenantOwner()` se resuelve comparando
        // `users.email` con `tenants.email`, así que cambiarlo le sacaría el panel al owner (no hay otra
        // señal de ownership desde que se eliminó `owner_id`). Se avisa en vez de fallar en silencio.
        if ($this->tenantEmail !== $this->tenant->email) {
            $this->addError(
                'tenantEmail',
                'El email del grupo no se puede cambiar desde acá: es lo que te identifica como owner. Si necesitás cambiarlo, escribinos.'
            );

            return;
        }

        $this->tenant->update([
            'name' => $this->tenantName,
        ]);

        $this->editingTenant = false;

        \Filament\Notifications\Notification::make()
            ->title('Datos del grupo actualizados')
            ->success()
            ->send();
    }

    /**
     * Modalidad del alta (revisión de Frankie, 2026-09-27):
     *   - 'password': se le manda una contraseña segura por mail (y el primer ingreso le pide cambiarla).
     *   - 'self':     sin clave: recibe un link para definirla en su primer ingreso.
     */
    public $inviteMode = 'password';

    public function addUser()
    {
        if (!$this->isOwner) {
            return;
        }

        $this->validate([
            'userName' => 'required|string|max:255',
            'userEmail' => 'required|email|unique:users,email',
            'inviteMode' => 'required|in:password,self',
        ]);

        $esInvitacion = $this->inviteMode === 'self';

        // En la modalidad "la define en su primer ingreso" se guarda una clave aleatoria que NUNCA se
        // comunica: la cuenta no tiene acceso hasta que use el link del mail.
        $passwordInicial = $esInvitacion
            ? Str::random(40)
            : \App\Support\PasswordRequirements::generate();

        $user = \App\Models\User::create([
            'name' => $this->userName,
            'email' => $this->userEmail,
            'password' => Hash::make($passwordInicial),
            'tenant_id' => $this->tenant->id,
            'force_password_change' => true
        ]);

        if ($esInvitacion) {
            // Token del broker `users` (vive 48 h, ver config/auth.php) apuntando al formulario de
            // contraseña, que ya trae la validación visual en vivo.
            $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);

            // ⚠️ La URL va FIRMADA: Filament exige firma en la ruta de reset
            // (`Panel/Concerns/HasAuth.php` usa `URL::signedRoute`) y con `route()` la ruta responde
            // 403 por el middleware `signed` -- medido con curl: firmada 200, sin firma 403.
            Notification::send($user, new \App\Notifications\TeamUserInvitationNotification(
                $this->tenant->name,
                $user->name,
                \Illuminate\Support\Facades\URL::signedRoute(
                    'filament.tenant.auth.password-reset.reset',
                    ['token' => $token, 'email' => $user->email],
                ),
            ));
        } else {
            // Clave generada con la política (la misma clase que dibuja la validación visual). Antes era
            // `Str::random(12)`, que no la garantizaba: se le mandaba al usuario una clave que el sistema
            // le rechazaba al cambiarla.
            Notification::send($user, new TeamUserActivationNotification($passwordInicial));
        }

        // Trazabilidad: quién sumó a quién y con qué modalidad.
        \App\Models\SecurityEvent::record(
            Auth::user(),
            \App\Models\SecurityEvent::TEAM_USER_INVITED,
            $esInvitacion ? 'self' : 'password'
        );

        $this->resetUserForm();
        $this->inviteMode = 'password';
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