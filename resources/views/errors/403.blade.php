@extends('errors::minimal')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message')
    <div class="text-lg font-medium text-gray-700 dark:text-gray-300">
        {{ $exception->getMessage() ?: 'Acceso Denegado' }}
    </div>

    <style>
        .gap-4 {
            gap: 1rem;
        }

        .flex-wrap {
            flex-wrap: wrap;
        }

        .rounded-lg {
            border-radius: 0.5rem;
        }

        .font-bold {
            font-weight: 700;
        }

        .text-white {
            color: #fff;
        }

        .transition {
            transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
            transition-duration: 150ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Tenant Primary Colors (approximate match to TenantPanelProvider) */
        .bg-primary-600 {
            background-color: rgb(53, 92, 178);
        }

        .hover\:bg-primary-500:hover {
            background-color: rgb(65, 116, 221);
        }

        .focus\:ring-primary-500:focus {
            --tw-ring-color: rgb(65, 116, 221);
        }

        /* Danger Colors */
        .bg-danger-600 {
            background-color: #dc2626;
        }

        .hover\:bg-danger-500:hover {
            background-color: #ef4444;
        }

        .focus\:ring-danger-500:focus {
            --tw-ring-color: #ef4444;
        }

        .focus\:outline-none:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
        }

        .focus\:ring-2:focus {
            --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
            --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
            box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
        }

        .focus\:ring-offset-2:focus {
            --tw-ring-offset-width: 2px;
        }
    </style>

    @php
        $isSuperAdminPath = request()->is('superadmin*');
        $isTenantPath = request()->is('tenant*');
        $superAdminLogoutRoute = 'filament.superadmin.auth.logout';
        $tenantLogoutRoute = 'filament.tenant.auth.logout';
    @endphp

    <div class="mt-6 text-center">

        @if(Auth::check())
            <p class="mb-4 text-gray-500 dark:text-gray-400">
                Parece que estás logueado con una cuenta que no tiene acceso aquí.
            </p>
        @endif

        <div class="flex flex-wrap justify-center gap-4">
            @if($isSuperAdminPath && Route::has($superAdminLogoutRoute))
                <a href="{{ route($superAdminLogoutRoute) }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form-superadmin').submit();"
                    class="px-4 py-2 font-bold text-white transition rounded-lg bg-danger-600 hover:bg-danger-500 focus:outline-none focus:ring-2 focus:ring-danger-500 focus:ring-offset-2">
                    Cerrar Sesión
                </a>
                <form id="logout-form-superadmin" action="{{ route($superAdminLogoutRoute) }}" method="POST"
                    style="display: none;">@csrf</form>

            @elseif($isTenantPath && Route::has($tenantLogoutRoute))
                <a href="{{ route($tenantLogoutRoute) }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form-tenant').submit();"
                    class="px-4 py-2 font-bold text-white transition rounded-lg bg-danger-600 hover:bg-danger-500 focus:outline-none focus:ring-2 focus:ring-danger-500 focus:ring-offset-2">
                    Cerrar Sesión
                </a>
                <form id="logout-form-tenant" action="{{ route($tenantLogoutRoute) }}" method="POST" style="display: none;">
                    @csrf</form>
            @endif

            @if($isSuperAdminPath && Route::has('filament.tenant.pages.dashboard'))
                <a href="{{ route('filament.tenant.pages.dashboard') }}"
                    class="px-4 py-2 font-bold text-white transition rounded-lg bg-primary-600 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    Ir al inicio
                </a>
            @elseif($isTenantPath && Route::has('filament.superadmin.pages.dashboard'))
                <a href="{{ route('filament.superadmin.pages.dashboard') }}"
                    class="px-4 py-2 font-bold text-white transition rounded-lg bg-primary-600 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    Ir al inicio
                </a>
            @else
                <a href="{{ url('/') }}"
                    class="px-4 py-2 font-bold text-white transition rounded-lg bg-primary-600 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    Ir al Home
                </a>
            @endif
        </div>

    </div>
@endsection