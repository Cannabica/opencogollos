@php
    $indoor = $space['indoor'];
    $plantas = $space['plantas'];
    $estados = $space['estados'];
    $riego = $space['riego'];
    $senales = $space['riego_plantas'];
    $acciones = $space['acciones'];
@endphp

<div class="space-block space-block--{{ $modo }}{{ $plantas->count() <= 2 ? ' space-block--chico' : '' }}">
    {{-- El espacio, con su señal de riego a la vista --}}
    <div class="muro-card muro-card--espacio">
        <div class="muro-card-head">
            <h3>{{ $indoor->name }}</h3>
            <span class="muro-count">{{ $plantas->count() }}
                {{ \Illuminate\Support\Str::plural('planta', $plantas->count()) }}</span>
        </div>
        <div class="muro-card-body">
            <div class="muro-chips">
                <span class="riego-badge riego-badge--{{ $riego['estado'] }}">
                    <x-icon name="heroicon-o-beaker" class="w-3.5 h-3.5" />
                    {{ $riego['etiqueta'] }}
                </span>
                @if ($space['plantas_sin_riego'] > 0)
                    <span class="riego-badge riego-badge--sin_riego">
                        {{ $space['plantas_sin_riego'] }}
                        {{ \Illuminate\Support\Str::plural('planta', $space['plantas_sin_riego']) }} sin riego
                    </span>
                @endif
            </div>

            <div class="muro-states">
                @forelse ($estados as $estado => $grupo)
                    <span class="state-badge state-badge-{{ strtolower(str_replace(['Etapa de ', 'Etapa '], '', $estado)) }}">
                        {{ str_replace(['Etapa de ', 'Etapa '], '', $estado) }}: {{ $grupo->count() }}
                    </span>
                @empty
                    <span class="muro-chip muro-chip--muted">Sin plantas cargadas</span>
                @endforelse
            </div>

            <div class="muro-chips">
                @if (!empty($indoor->lamps) && is_array($indoor->lamps))
                    @foreach ($indoor->lamps as $lamp)
                        <span class="muro-chip">
                            <x-icon name="heroicon-o-light-bulb" class="w-3.5 h-3.5" />
                            {{ $lamp['power'] }}W {{ $lamp['technology'] ?? '' }}
                        </span>
                    @endforeach
                @else
                    <span class="muro-chip muro-chip--muted">Sin lámparas cargadas</span>
                @endif
                <span class="muro-chip muro-chip--muted">
                    {{ $indoor->width ?? '—' }} × {{ $indoor->large ?? '—' }} cm
                </span>
            </div>
        </div>
    </div>

    {{-- Una tarjeta por planta, cada una con su último riego --}}
    <div class="plantas-grid">
        @foreach ($plantas as $plant)
            @php $senal = $senales[$plant->id] ?? null; @endphp

            <div class="muro-card muro-card--planta">
                <div class="muro-card-body">
                    <div class="muro-plant-top">
                        <h4>{{ $plant->name }}</h4>
                        <span class="state-badge state-badge-{{ strtolower(str_replace(['Etapa de ', 'Etapa '], '', $plant->state)) }}">
                            {{ str_replace(['Etapa de ', 'Etapa '], '', $plant->state) }}
                        </span>
                    </div>

                    <p class="muro-plant-seed">
                        @if ($plant->seedType)
                            {{ $plant->seedType->name }} ({{ $plant->seedType->seed_type }})
                        @else
                            Sin semilla cargada
                        @endif
                    </p>

                    <div class="plant-meta">
                        @if ($plant->germination_date)
                            <span>
                                <x-icon name="heroicon-o-clock" class="w-3.5 h-3.5" />
                                {{ now()->diffInDays($plant->germination_date) }} días
                            </span>
                        @endif
                        <span>
                            <x-icon name="heroicon-o-beaker" class="w-3.5 h-3.5" />
                            {{ 'Maceta ' . $plant->flowerpot ?? 'N/A' }}: {{ $plant->capacity ?? '00' }}L
                        </span>
                    </div>

                    @if ($senal)
                        <span class="riego-badge riego-badge--{{ $senal['estado'] }}">
                            {{ $senal['etiqueta'] }}
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Lo último que pasó en el espacio --}}
    <div class="muro-card muro-card--acciones">
        <div class="muro-card-head">
            <h3>Últimas acciones</h3>
        </div>
        <div class="muro-card-body">
            @if ($acciones->isNotEmpty())
                <div class="action-list">
                    @foreach ($acciones as $action)
                        <div class="action-item">
                            <span class="action-date">
                                {{ \Carbon\Carbon::parse($action->action_date)->format('d/m/y') }}
                            </span>
                            <div>
                                <span class="font-medium">{{ $action->action_type->name ?? 'Acción desconocida' }}</span>
                                <p class="text-sm text-gray-500">{{ $action->getDetalleAccionAttribute() ?? 'Sin detalles' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No hay acciones recientes.</p>
            @endif
        </div>
    </div>
</div>
