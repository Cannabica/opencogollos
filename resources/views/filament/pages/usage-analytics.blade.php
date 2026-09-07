<x-filament::page>
    @php($summary = \App\Support\UsageStats::summary())
    @php($funnel = \App\Support\UsageStats::funnel())
    @php($modules = \App\Support\UsageStats::topModules())
    @php($modulesTotal = \App\Support\UsageStats::totalTenantPageviews())
    @php($heat = \App\Support\UsageStats::activityMatrix())

    {{-- Resumen --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Páginas vistas (7d)</div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $summary['pageviews7d'] }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">En paneles tenant y superadmin</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Usuarios con actividad (7d)</div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $summary['users7d'] }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Usuarios únicos que navegaron</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Tenants activos (7d)</div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $summary['tenants7d'] }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Cultivadores que usaron la plataforma</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Acciones de cultivo (7d)</div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $summary['actions7d'] }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Seguimientos registrados por los tenants</div>
        </x-filament::section>
    </div>

    {{-- Embudo + módulos --}}
    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Embudo de activación</x-slot>
            <x-slot name="description">Cuántos tenants llegan a cada paso del ciclo</x-slot>

            <div class="space-y-3">
                @foreach ($funnel as $step)
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 dark:text-gray-300">{{ $step['label'] }}</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ $step['count'] }} <span class="text-xs font-normal text-gray-500">({{ $step['pct'] }}%)</span>
                            </span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-primary-500"
                                 style="width: {{ max($step['pct'], 1) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Módulos más usados (30 días)</x-slot>
            <x-slot name="description">Páginas vistas del panel tenant por módulo</x-slot>

            @if (count($modules) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Todavía no hay datos de telemetría. La captura arranca con la primera navegación de un tenant.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                <th class="py-2 pr-4">Módulo</th>
                                <th class="py-2 pr-4 text-right">Páginas</th>
                                <th class="py-2 text-right">Tenants</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($modules as $module)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 pr-4 text-gray-900 dark:text-white">{{ $module['label'] }}</td>
                                    <td class="py-2 pr-4 text-right font-semibold text-gray-900 dark:text-white">
                                        {{ $module['pageviews'] }}
                                        <span class="text-xs font-normal text-gray-500">
                                            ({{ $modulesTotal > 0 ? round(($module['pageviews'] / $modulesTotal) * 100) : 0 }}%)
                                        </span>
                                    </td>
                                    <td class="py-2 text-right text-gray-600 dark:text-gray-300">{{ $module['tenants'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- Heatmap de actividad (día x hora) --}}
    <div class="mt-4">
        <x-filament::section>
            <x-slot name="heading">Actividad de la plataforma (últimos 30 días)</x-slot>
            <x-slot name="description">Páginas vistas del panel tenant por día de semana y hora</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-center text-xs">
                    <thead>
                        <tr>
                            <th class="p-1"></th>
                            @foreach ($heat['labels'] as $day)
                                <th class="p-1 font-medium text-gray-500 dark:text-gray-400">{{ $day }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($heat['hours'] as $hour)
                            <tr>
                                <td class="p-1 pr-2 text-right text-gray-500 dark:text-gray-400">{{ sprintf('%02d:00', $hour) }}</td>
                                @foreach (range(0, 6) as $day)
                                    @php($v = $heat['data'][$hour][$day])
                                    <td class="p-1">
                                        <div class="mx-auto flex h-5 w-full min-w-6 items-center justify-center rounded"
                                             style="background-color: {{ \App\Support\UsageStats::heatColor($v) }}"
                                             title="{{ $heat['labels'][$day] }} {{ sprintf('%02d:00', $hour) }}: {{ $v }} páginas">
                                            @if ($v > 0)
                                                <span class="text-[10px] font-semibold text-gray-900">{{ $v }}</span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
