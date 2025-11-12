@extends('errors::minimal')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message')
<div class="text-lg font-medium text-gray-700 dark:text-gray-300">
{{ $exception->getMessage() ?: 'Acceso Denegado' }}
</div>

@php
        $isSuperAdminPath = request()->is('superadmin*');
        $isTenantPath = request()->is('tenant*');
        $superAdminLogoutRoute = 'filament.superadmin.auth.logout';
        $tenantLogoutRoute = 'filament.tenant.auth.logout';
    @endphp

    <div style="display: flex; gap: 15px; justify-content: center; margin-top: 25px;">

        @if(Auth::check())
            <p style="width: 100%; text-align: center; margin-bottom: 10px;">
                Parece que estás logueado con una cuenta que no tiene acceso aquí.
            </p>
        @endif

        @if($isSuperAdminPath && Route::has($superAdminLogoutRoute))
            <a href="{{ route($superAdminLogoutRoute) }}"
               onclick="event.preventDefault(); document.getElementById('logout-form-superadmin').submit();"
               style="font-weight: bold; text-decoration: none; padding: 10px 15px; border-radius: 8px; background-color: #ef4444; color: white;">
                Cerrar Sesión
            </a>
            <form id="logout-form-superadmin" action="{{ route($superAdminLogoutRoute) }}" method="POST" style="display: none;">@csrf</form>

        @elseif($isTenantPath && Route::has($tenantLogoutRoute))
            <a href="{{ route($tenantLogoutRoute) }}"
               onclick="event.preventDefault(); document.getElementById('logout-form-tenant').submit();"
               style="font-weight: bold; text-decoration: none; padding: 10px 15px; border-radius: 8px; background-color: #ef4444; color: white;">
                Cerrar Sesión
            </a>
            <form id="logout-form-tenant" action="{{ route($tenantLogoutRoute) }}" method="POST" style="display: none;">@csrf</form>
        @endif

        @if($isSuperAdminPath && Route::has('filament.tenant.path'))
            <a href="{{ route('filament.tenant.path') }}"
               style="font-weight: bold; text-decoration: none; padding: 10px 15px; border-radius: 8px; background-color: #3b82f6; color: white;">
                Ir al Panel
            </a>
        @elseif($isTenantPath && Route::has('filament.superadmin.path'))
            <a href="{{ route('filament.superadmin.path') }}"
               style="font-weight: bold; text-decoration: none; padding: 10px 15px; border-radius: 8px; background-color: #3b82f6; color: white;">
                Ir al Panel SuperAdmin
            </a>
        @else
             <a href="{{ url('/') }}"
               style="font-weight: bold; text-decoration: none; padding: 10px 15px; border-radius: 8px; background-color: #3b82f6; color: white;">
                Ir al Home
            </a>
        @endif

    </div>
@endsection