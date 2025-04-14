<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class TenantPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static string $view = 'filament.tenant.pages.tenant';
    protected static ?string $navigationLabel = 'Mi Grupo';
    protected static ?string $title = 'Información del grupo';

    public $tenant;
    public $users;

    public function mount()
    {
        $this->tenant = Auth::user()->tenant;
        $this->users = $this->tenant->users()->get();
    }
}