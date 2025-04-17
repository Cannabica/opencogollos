<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Services\TenantTokenService;

class TenantPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static string $view = 'filament.tenant.pages.tenant';
    protected static ?string $navigationLabel = 'Mi Grupo';
    protected static ?string $title = 'Información del grupo';

    public $tenant;
    public $users;
    public $apiTokens;
    public $newToken;

    public function mount()
    {
        $this->tenant = Auth::user()->tenant;
        $this->users = $this->tenant->users()->get();
        $this->apiTokens = $this->tenant->apiTokens()
            ->orderByDesc('created_at')
            ->get();
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
}