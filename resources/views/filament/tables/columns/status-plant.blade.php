@php
    $plant = $getRecord();
    $daysOfLife = \Carbon\Carbon::now()->diffInDays($plant->germination_date);
    $daysInCurrentState = optional($plant->actions()
        ->where('type', 'change_state')
        ->orderByDesc('created_at')
        ->first()
    )->created_at 
        ? \Carbon\Carbon::now()->diffInDays($plant->actions()->where('type', 'change_state')->orderByDesc('created_at')->first()->created_at)
        : $daysOfLife;
@endphp

<div class="grid grid-cols-2 gap-4 bg-gray-800 rounded-lg p-6 text-white">

    <!-- Primera fila
    <div class="col text-center">
        <h2 class="text-lg font-bold">{{ $plant->name }}</h2>
        <hr>
    </div>
    <div class="col-span-1 text-right">
        <span class="px-2 py-1 bg-blue-500 rounded-md text-sm font-semibold">{{ $plant->state }}</span>
    </div> -->

    <!-- Segunda fila: Días de vida -->
    <!-- <div class="col-span-2 mt-4">
        <p class="text-center text-xl font-semibold">
            {{ $daysOfLife }} días de vida
        </p>
    </div> -->

    <!-- Tercera fila: Información de la semilla -->
    <!-- <div class="col-span-1">
        <p><strong>Semilla:</strong> {{ $plant->seedType->name . ' (' . $plant->seedType->seed_type . ') '  ?? 'No disponible' }}</p>
    </div>
    <div class="col-span-1">
        <p><strong>Tipo de semilla:</strong> {{ $plant->seedType->seed_type ?? 'N/A' }}</p>
    </div>
    <div class="col-span-1">
        <p><strong>Días en etapa actual:</strong> {{ $daysInCurrentState }} días</p>
    </div> -->

    <!-- Cuarta fila: Detalle de semilla -->
    <div class="col-span-1">
        <p>
            <strong>THC/CBD:</strong> 
            {{ $plant->seedType->ratio_thc ?? '0' }}% / {{ $plant->seedType->ratio_cbd ?? '0' }}%
        </p>
    </div>

    <!-- Quinta fila: Datos adicionales -->
    <!-- <div class="col-span-2 mt-4 border-t border-gray-700 pt-4">
        <h3 class="text-lg font-bold mb-2">Datos de la Maceta</h3>
        <ul class="list-disc pl-5 space-y-2">
            <li><strong>Litros de maceta:</strong> {{ $plant->pot_size ?? 'N/A' }} L</li>
            <li><strong>Cantidad de podas registradas:</strong> {{ $plant->prunings_count ?? 0 }}</li>
            <li><strong>Cantidad de cuidados registrados:</strong> {{ $plant->care_actions_count ?? 0 }}</li>
        </ul>
    </div> -->

</div>
