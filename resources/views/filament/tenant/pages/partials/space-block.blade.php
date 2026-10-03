@php
    $indoor = $space['indoor'];
    $plantas = $space['plantas'];
    $estados = $space['estados'];
    $riego = $space['riego'];
    $senales = $space['riego_plantas'];
    $acciones = $space['acciones'];
@endphp

<div class="bloque-lugar">
    {{-- El lugar, con su señal de riego a la vista --}}
    <div class="carta carta--lugar">
        <div class="carta-cabeza">
            <h3>{{ $indoor->name }}</h3>
            <span class="contador">{{ $plantas->count() }}
                {{ \Illuminate\Support\Str::plural('planta', $plantas->count()) }}</span>
        </div>
        <div class="carta-cuerpo">
            <div class="chips">
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

            <div class="estados">
                @forelse ($estados as $estado => $grupo)
                    <span class="state-badge state-badge-{{ strtolower(str_replace(['Etapa de ', 'Etapa '], '', $estado)) }}">
                        {{ str_replace(['Etapa de ', 'Etapa '], '', $estado) }}: {{ $grupo->count() }}
                    </span>
                @empty
                    <span class="chip chip--apagado">Sin plantas cargadas</span>
                @endforelse
            </div>

            <div class="chips">
                @if (!empty($indoor->lamps) && is_array($indoor->lamps))
                    @foreach ($indoor->lamps as $lamp)
                        <span class="chip">
                            <x-icon name="heroicon-o-light-bulb" class="w-3.5 h-3.5" />
                            {{ $lamp['power'] }}W {{ $lamp['technology'] ?? '' }}
                        </span>
                    @endforeach
                @else
                    <span class="chip chip--apagado">Sin lámparas cargadas</span>
                @endif
                <span class="chip chip--apagado">
                    {{ $indoor->width ?? '—' }} × {{ $indoor->large ?? '—' }} cm
                </span>
            </div>
        </div>
    </div>

    {{-- Una tarjeta por planta, con su último riego cuando se sale de lo esperado --}}
    <div class="plantas-grid">
        @foreach ($plantas as $plant)
            @php $senal = $senales[$plant->id] ?? null; @endphp

            <div class="carta carta--planta">
                <div class="carta-cuerpo">
                    <div class="planta-cabeza">
                        <h4>{{ $plant->name }}</h4>
                        <span class="state-badge state-badge-{{ strtolower(str_replace(['Etapa de ', 'Etapa '], '', $plant->state)) }}">
                            {{ str_replace(['Etapa de ', 'Etapa '], '', $plant->state) }}
                        </span>
                    </div>

                    <p class="planta-semilla">
                        @if ($plant->seedType)
                            {{ $plant->seedType->name }} ({{ $plant->seedType->seed_type }})
                        @else
                            Sin semilla cargada
                        @endif
                    </p>

                    <div class="planta-datos">
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

                    {{-- Sólo se marca la excepción: si la planta comparte el estado del lugar,
                         ya está dicho arriba y repetirlo en cada tarjeta satura la pantalla. --}}
                    @if ($senal && $senal['estado'] !== $riego['estado'])
                        <span class="riego-badge riego-badge--{{ $senal['estado'] }}">
                            {{ $senal['etiqueta'] }}
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Lo último que pasó en el lugar --}}
    <div class="carta carta--acciones">
        <div class="carta-cabeza">
            <h3>Últimas acciones</h3>
        </div>
        <div class="carta-cuerpo">
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
