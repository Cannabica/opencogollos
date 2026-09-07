<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Módulos más usados (30 días)</x-slot>
        <x-slot name="description">Páginas vistas del panel tenant por módulo</x-slot>

        @php($modules = $this->modules())
        @php($total = $this->totalPageviews30d())

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
                                        ({{ $total > 0 ? round(($module['pageviews'] / $total) * 100) : 0 }}%)
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
</x-filament-widgets::widget>
