<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Embudo de activación</x-slot>
        <x-slot name="description">Cuántos tenants llegan a cada paso del ciclo</x-slot>

        <div class="space-y-3">
            @foreach ($this->funnel() as $step)
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
</x-filament-widgets::widget>
