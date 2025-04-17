<x-filament::page x-data="{}" x-on:token-renewed.window="$dispatch('notify', { message: 'Token renewed successfully', type: 'success' })">
    <x-filament::card>
        <div class="space-y-4">
            <h2 class="text-xl font-bold">Información del grupo</h2>
            
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
        </div>
    </x-filament::card>

    <x-filament::card>
        <div class="space-y-4">
            <h2 class="text-xl font-bold">Usuarios del grupo</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($users as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $user->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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
                            Generar nuevo token
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
                <div class="p-4 bg-green-50 rounded-lg" x-data="{ showToken: false }">
                    <p class="font-medium">Nuevo token generado:</p>
                    <div class="mt-2 flex items-center gap-2">
                        <input
                            x-bind:type="showToken ? 'text' : 'password'"
                            value="{{ $newToken }}"
                            class="flex-1 p-3 bg-white rounded border border-gray-200 break-all"
                            readonly
                        >
                        <button
                            @click="showToken = !showToken"
                            type="button"
                            class="px-3 py-2 rounded"
                        >
                            <span x-text="showToken ? 'Ocultar' : 'Mostrar'"></span>
                        </button>
                    </div>
                    <div class="mt-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-600 rounded-lg" style="padding-left: 50px">
                        <p class="font-medium text-yellow-800 dark:text-yellow-200">¡Advertencia de seguridad!</p>
                        <ul class="mt-2 space-y-1 text-sm text-yellow-700 dark:text-yellow-300 list-disc pl-5">
                            <li><strong>No se puede volver a visualizar este token</strong></li>
                            <li>Este token proporciona acceso completo a la API</li>
                            <li>Guárdelo en un lugar seguro y no lo comparta</li>
                            <li>Si se pierde o compromete, revóquelo inmediatamente</li>
                        </ul>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">Este token expirará en 7 días y puede renovarse hasta 10 veces.</p>
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
                                       {{ $token->last_renewed_at->format('d/m/Y H:i') }}
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
