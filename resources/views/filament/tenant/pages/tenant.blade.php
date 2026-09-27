<x-filament::page x-data="{}" x-on:token-renewed.window="$dispatch('notify', { message: 'Token renewed successfully', type: 'success' })">
    <x-filament::card>
        <div class="space-y-4">            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Nombre</p>
                    <p>{{ $tenant->name }}</p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Email</p>
                    <p>{{ $tenant->email }}</p>
                </div>
                
            </div>

            {{-- Datos del GRUPO: edición inline y sólo para el owner (misma regla que la gestión de
                 usuarios de abajo; la validación real está en TenantPage::updateTenant). --}}
            {{-- Acciones en UN solo bloque (revisión de Frankie, 2026-09-27). Dos grupos, mismo lugar:
                 las del GRUPO las ve sólo el owner; las de la PERSONA las ve cualquiera, porque son sus
                 propios datos (antes esto estaba en dos bloques separados y parecía repetido). --}}
            <div class="flex flex-wrap items-center gap-3 pt-4 mt-2 border-t border-gray-100 dark:border-white/10">
                @if($isOwner && ! $editingTenant)
                    <x-filament::button
                        wire:click="editTenant"
                        color="gray"
                        size="sm"
                        icon="heroicon-o-pencil-square"
                    >
                        Editar datos del grupo
                    </x-filament::button>

                    <x-filament::button
                        wire:click="$set('showUserForm', true)"
                        color="primary"
                        size="sm"
                        icon="heroicon-o-user-plus"
                    >
                        Agregar persona al grupo
                    </x-filament::button>

                    <span class="hidden sm:block w-px h-6 bg-gray-200 dark:bg-white/10"></span>
                @endif

                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Tenant\Pages\Cuenta::getUrl()"
                    color="gray"
                    size="sm"
                    icon="heroicon-o-user-circle"
                >
                    Mis datos personales
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Tenant\Pages\CambiarPassword::getUrl()"
                    color="gray"
                    size="sm"
                    icon="heroicon-o-key"
                >
                    Cambiar mi contraseña
                </x-filament::button>
            </div>

            @if($editingTenant)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <x-filament::input wire:model="tenantName" placeholder="Nombre del grupo" required />

                    {{-- El email del grupo es su canal de contacto: se puede editar (ya no arrastra
                         permisos: el ownership se resuelve por `tenants.owner_user_id`). Va con
                         `wire:model.live` para que el casillero de abajo reaccione al escribir. --}}
                    <div>
                        <x-filament::input wire:model.live="tenantEmail" type="email" placeholder="Email de contacto" required />
                    </div>
                </div>

                {{-- Sólo aparece si el email CAMBIÓ y esa dirección no tiene usuario: si es la misma de
                     siempre, o ya hay alguien con esa dirección, no hay nada que crear. --}}
                @if($this->puedeCrearPersonaParaElEmail())
                    <label class="flex items-start gap-3 cursor-pointer" wire:key="casillero-crear-persona">
                        <x-filament::input.checkbox wire:model="createUserForEmail" />
                        <span>
                            <span class="font-medium">Crear una persona con esta dirección</span>
                            <span class="block text-sm text-gray-500 dark:text-gray-400">
                                Le mandamos un link para que elija su contraseña.
                            </span>
                        </span>
                    </label>
                @endif

                <div class="flex gap-2">
                    <x-filament::button wire:click="updateTenant" color="primary" size="sm">
                        Guardar
                    </x-filament::button>
                    <x-filament::button wire:click="cancelTenantEdit" color="gray" size="sm">
                        Cancelar
                    </x-filament::button>
                </div>
            @endif

        </div>
    </x-filament::card>

    <x-filament::card>
        <div class="space-y-4">
            <h2 class="text-xl font-bold">Información de registro</h2>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Tipo de usuario</p>
                    @php
                        $userTypeLabels = [
                            'cultivador_hogareño' => 'Cultivador Hogareño / Cultivador solidario',
                            'growshop' => 'Growshop - Club de cultivo / Comercio',
                            'cooperativa' => 'Cooperativa',
                            'otro' => 'Otro',
                        ];
                        $displayValue = $user_type ? ($userTypeLabels[$user_type] ?? $user_type) : 'No especificado';
                    @endphp
                    <p>{{ $displayValue }}</p>
                </div>
                                
                <div>
                    <p class="text-sm font-medium text-gray-500">Plantas por ciclo</p>
                    @php
                        $plantsOptions = [
                            1 => '1-5 plantas',
                            2 => '6-10 plantas',
                            3 => '11-20 plantas',
                            4 => '21-50 plantas',
                            5 => 'Más de 50 plantas',
                        ];
                    @endphp
                    <p>{{ $plants_per_cycle ? ($plantsOptions[$plants_per_cycle] ?? 'No especificado') : 'No especificado' }}</p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Productos de cosecha</p>
                    @if($harvest_products)
                        @php
                            $products = json_decode($harvest_products, true);
                        @endphp
                            @if(is_array($products) && count($products) > 0)
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach($products as $product)
                                        <p class="capitalize">{{ $product }}</p>
                                    @endforeach
                                </ul>
                            @else
                                <p>No especificado</p>
                            @endif
                    @else
                        <p>No especificado</p>
                    @endif
                </div>
                
            </div>
        </div>
    </x-filament::card>

    <x-filament::card>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold">Usuarios del grupo</h2>
                {{-- El alta vive en el bloque de acciones del grupo, arriba (revisión de Frankie,
                     2026-09-27): tener "Agregar usuario" acá también duplicaba la acción. --}}
            </div>
            
            @if($showUserForm)
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h3 class="text-lg font-medium mb-4">
                        {{ $editingUser ? 'Editar usuario' : 'Agregar nuevo usuario' }}
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <x-filament::input
                            wire:model="userName"
                            placeholder="Nombre completo"
                            required
                        />
                        <x-filament::input
                            wire:model="userEmail"
                            type="email"
                            placeholder="Email"
                            required
                        />
                    </div>

                    @if(! $editingUser)
                        {{-- Modalidad del alta (revisión de Frankie, 2026-09-27). Al editar un usuario ya
                             existente no aplica: la clave ya la tiene. --}}
                        <div class="mb-4 space-y-2">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="radio" wire:model="inviteMode" value="password" class="mt-1" />
                                <span>
                                    <span class="font-medium">Le mandamos una contraseña segura</span>
                                    <span class="block text-sm text-gray-500 dark:text-gray-400">
                                        La recibe por mail; en el primer ingreso se la pide cambiar.
                                    </span>
                                </span>
                            </label>

                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="radio" wire:model="inviteMode" value="self" class="mt-1" />
                                <span>
                                    <span class="font-medium">La define en su primer ingreso</span>
                                    <span class="block text-sm text-gray-500 dark:text-gray-400">
                                        Sin clave: recibe un link por mail (vence en 48 h) para elegir la suya.
                                    </span>
                                </span>
                            </label>
                        </div>
                    @endif

                    <div class="flex gap-2">
                        <x-filament::button
                            wire:click="{{ $editingUser ? 'updateUser' : 'addUser' }}"
                            color="primary"
                            size="sm"
                        >
                            {{ $editingUser ? 'Actualizar' : 'Agregar' }}
                        </x-filament::button>
                        <x-filament::button
                            wire:click="cancelEdit"
                            color="gray"
                            size="sm"
                        >
                            Cancelar
                        </x-filament::button>
                    </div>
                </div>
            @endif
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Email</th>
                            @if($isOwner)
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($users as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $user->name }}
                                {{-- Identificadores en línea, chicos y del mismo formato (revisión de
                                     Frankie, 2026-09-27). Se usa el badge de Filament --que ya trae los
                                     colores del tema y el modo oscuro-- con `!inline-flex`, porque el
                                     badge por defecto es `flex` y caía a un renglón propio. Las clases
                                     sueltas (`bg-*-100`) no se veían: no están en el CSS compilado.
                                     Quién administra sale de `tenants.owner_user_id`. --}}
                                @if($user->isTenantOwner())
                                    <x-filament::badge color="warning" size="xs" style="display: inline-flex; padding: 3px 10px; vertical-align: middle;" class="ml-2">
                                        ★ Administrador
                                    </x-filament::badge>
                                @endif
                                @if($user->id === auth()->id())
                                    {{-- `info` en vez de `gray`: en modo oscuro el gris quedaba con
                                         contraste bajo (el panel se usa en dark). --}}
                                    <x-filament::badge color="info" size="xs" style="display: inline-flex; padding: 3px 10px; vertical-align: middle;" class="ml-2">
                                        Vos
                                    </x-filament::badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
                            @if($isOwner)
                            
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($user->force_password_change)
                                        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">Cambio requerido</span>
                                    @else
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Activo</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex gap-2">
                                        @if($user->id !== auth()->id())
                                            <x-filament::button
                                                wire:click="editUser({{ $user->id }})"
                                                color="warning"
                                                size="sm"
                                            >
                                                Editar
                                            </x-filament::button>
                                            <x-filament::button
                                                wire:click="resetPassword({{ $user->id }})"
                                                color="danger"
                                                size="sm"
                                                wire:confirm="¿Estás seguro de que quieres blanquear la contraseña de este usuario? Se enviará una nueva contraseña temporal por email."
                                            >
                                                Blanquear contraseña
                                            </x-filament::button>
                                            <x-filament::button
                                                wire:click="forcePasswordChange({{ $user->id }})"
                                                color="warning"
                                                size="sm"
                                                wire:confirm="¿Forzar cambio de contraseña en el próximo inicio de sesión?"
                                            >
                                                Forzar cambio
                                            </x-filament::button>

                                            {{-- Transferir la administración del grupo. El aviso es
                                                 explícito porque quien lo hace deja de ser admin. --}}
                                            @if(! $user->isTenantOwner())
                                                <x-filament::button
                                                    wire:click="makeOwner({{ $user->id }})"
                                                    color="primary"
                                                    size="sm"
                                                    wire:confirm="¿{{ $user->name }} pasa a administrar el grupo? Vos dejarás de ser administrador."
                                                >
                                                    Hacer administrador
                                                </x-filament::button>
                                            @endif
                                            <x-filament::button
                                                wire:click="removeUser({{ $user->id }})"
                                                color="danger"
                                                size="sm"
                                                wire:confirm="¿Estás seguro de que quieres eliminar este usuario del grupo?"
                                            >
                                                Eliminar
                                            </x-filament::button>
                                        @else
                                            <span class="text-gray-400 text-sm">Acciones no disponibles</span>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($isOwner)
                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="font-medium text-blue-800 dark:text-blue-200">Información para propietarios</h4>
                    <ul class="mt-2 space-y-1 text-sm text-blue-700 dark:text-blue-300 list-disc pl-5">
                        <li>Puedes agregar, editar y eliminar usuarios del grupo</li>
                        <li>Al blanquear una contraseña, se genera una nueva temporal y se notifica al usuario</li>
                        <li>Forzar cambio de contraseña obliga al usuario a cambiar su contraseña en el próximo inicio</li>
                        <li>No puedes eliminar tu propio usuario</li>
                    </ul>
                </div>
            @endif
        </div>
    </x-filament::card>

    <x-filament::card>
        <div class="space-y-4">
            <div class="space-y-4 mb-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold">Tokens API</h2>
                    <div class="flex items-center gap-2">
                        <x-filament::input
                            wire:model="tokenReference"
                            placeholder="Referencia (opcional)"
                            class="w-64"
                        />
                        <x-filament::button
                            wire:click="generateToken"
                            :disabled="$apiTokens->count() >= 5"
                            color="primary"
                        >
                            Nuevo token
                        </x-filament::button>
                    </div>
                </div>
                @if($apiTokens->count() >= 5)
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                        <p class="text-sm text-red-600 dark:text-red-400 font-medium">
                            ⚠️ Límite alcanzado: máximo 5 tokens activos permitidos
                        </p>
                    </div>
                @endif
            </div>

            @if($newToken)
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800/30" x-data="{ showToken: false }">
                    <p class="font-medium text-green-900 dark:text-green-300">Nuevo token generado:</p>
                    <div class="mt-2 flex items-center gap-2">
                        <input
                            x-bind:type="showToken ? 'text' : 'password'"
                            value="{{ $newToken }}"
                            class="flex-1 p-3 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700 dark:text-white break-all"
                            readonly
                        >
                        <button
                            @click="showToken = !showToken"
                            type="button"
                            class="px-3 py-2 rounded text-gray-700 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-green-800/50"
                        >
                            <span x-text="showToken ? 'Ocultar' : 'Mostrar'"></span>
                        </button>
                        <button
                            @click="navigator.clipboard.writeText('{{ $newToken }}'); $tooltip('Copiado!', { timeout: 2000 })"
                            type="button"
                            class="px-3 py-2 rounded text-gray-700 dark:text-gray-300 hover:bg-green-100 dark:hover:bg-green-800/50"
                            x-tooltip="'Copiar al portapapeles'"
                        >
                            Copiar
                        </button>
                    </div>
                    <div class="mt-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-600/50 rounded-lg" style="padding-left: 50px">
                        <p class="font-medium text-yellow-800 dark:text-yellow-300">¡Advertencia de seguridad!</p>
                        <ul class="mt-2 space-y-1 text-sm text-yellow-700 dark:text-yellow-400 list-disc pl-5">
                            <li><strong>No se puede volver a visualizar este token</strong></li>
                            <li>Este token proporciona acceso completo a la API y al bot de telegram</li>
                            <li>Guárdelo en un lugar seguro y no lo comparta</li>
                            <li>Si se pierde o compromete, revóquelo inmediatamente</li>
                        </ul>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Este token expirará en 7 días y puede renovarse hasta 10 veces.</p>
                </div>
            @endif

            @if($apiTokens->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                               <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Referencia</th>
                               <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Creado</th>
                               <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Expira</th>
                               <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Renovaciones</th>
                               <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Última Renovación</th>
                               <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Acciones</th>
                           </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($apiTokens as $token)
                            <tr>
                               <td class="px-6 py-4 whitespace-nowrap">
                                   {{ $token->reference ?: 'Sin referencia' }}
                               </td>
                               <td class="px-6 py-4 whitespace-nowrap">{{ $token->created_at->format('d/m/Y H:i') }}</td>
                               <td class="px-6 py-4 whitespace-nowrap">{{ $token->expires_at->format('d/m/Y H:i') }}</td>
                               <td class="px-6 py-4 whitespace-nowrap">{{ $token->renew_count }}</td>
                               <td class="px-6 py-4 whitespace-nowrap">
                                   @if($token->last_renewed_at)
                                       {{ \Carbon\Carbon::parse($token->last_renewed_at)->format('d/m/Y H:i') }}
                                   @else
                                       <span class="text-gray-400 italic">Nunca</span>
                                   @endif
                               </td>
                               <td class="px-6 py-4 whitespace-nowrap">
                                   <div class="flex gap-2">
                                       <x-filament::button
                                           color="warning"
                                           size="sm"
                                           wire:click="renewToken('{{ $token->id }}')"
                                           :disabled="$token->renew_count >= 10"
                                       >
                                           Renovar
                                       </x-filament::button>
                                       <x-filament::button
                                           color="danger"
                                           size="sm"
                                           wire:click="revokeToken({{ $token->id }})"
                                       >
                                           Revocar
                                       </x-filament::button>
                                   </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500">No hay tokens generados</p>
            @endif
        </div>
    </x-filament::card>

</x-filament::page>
