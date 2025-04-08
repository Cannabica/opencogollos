@php
    $record = $getRecord();
    $data = $getState();
@endphp

<div class="plant-content">
    <div class="plant-header">
        <div class="plant-state-icon">
            <x-icon name="{{ $data['stateIcon'] }}" class="w-6 h-6" />
        </div>
        <div class="flex flex-col items-start">
            <h3 class="text-lg font-bold truncate w-full">{{ $record->name }}</h3>
            <span class="state-badge state-badge-{{ $data['stateBadgeClass'] }}">
                {{ $record->state }}
            </span>
        </div>
    </div>

    <div class="plant-info">
        <div class="plant-info-item seed-info">
            <x-icon name="heroicon-o-beaker" class="w-4 h-4 shrink-0" />
            <span>{{ $record->seedType?->name }} ({{ $record->seedType?->seed_type }})</span>
        </div>

        <div class="plant-info-item">
            <x-icon name="heroicon-o-calendar" class="w-4 h-4 shrink-0" />
            <span>{{ __('days_of_life') }}: {{ $data['totalDays'] }} días</span>
        </div>

        <div class="plant-info-item">
            <x-icon name="heroicon-o-clock" class="w-4 h-4 shrink-0" />
            <span>{{ $data['daysInState'] }} días en la etapa actual</span>
        </div>

        @if($record->seedType?->ratio_thc !== null && $record->seedType?->ratio_cbd !== null)
            <div class="plant-info-item">
                <x-icon name="heroicon-o-chart-bar" class="w-4 h-4 shrink-0" />
                <span>THC: {{ $record->seedType->ratio_thc }}% - CBD: {{ $record->seedType->ratio_cbd }}%</span>
            </div>
        @endif

        <div class="plant-info-item">
            <x-icon name="heroicon-o-home" class="w-4 h-4 shrink-0" />
            <span class="truncate">{{ __('indoor_name') }} {{ $record->indoor?->name }}</span>
        </div>

        <div class="plant-info-item">
            <x-icon name="heroicon-o-cube" class="w-4 h-4 shrink-0" />
            <span>{{ __('recipient') }}: {{ $record->flowerpot }} {{ $record->capacity }}L</span>
        </div>
    </div>

    <div class="plant-metrics">
        <div class="metric-item">
            <div class="metric-value">{{ $data['actionsCount'] }}</div>
            <div class="text-sm">Acciones</div>
        </div>
        <div class="metric-item">
            <div class="metric-value">{{ $data['pruningsCount'] }}</div>
            <div class="text-sm">Podas</div>
        </div>
    </div>
</div> 