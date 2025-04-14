<x-filament::page>
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
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Estado</p>
                    <p>
                        <span class="px-2 py-1 text-xs rounded-full {{ $tenant->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $tenant->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </p>
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
</x-filament::page>