{{--
    Requisitos de contraseña en vivo.
    Se usa debajo del campo de contraseña nueva: cada requisito se marca al tipear (el campo tiene
    que ser `->live()` para que el form se re-renderice).

    La lista sale de App\Support\PasswordRequirements, la misma clase que arma la regla de validación:
    no hay dos verdades sobre qué pide la contraseña.
--}}
@php($requisitos = \App\Support\PasswordRequirements::checks($password ?? null))

<ul class="mt-2 space-y-1 text-sm">
    @foreach($requisitos as $requisito)
        <li class="flex items-center gap-2 {{ $requisito['ok'] ? 'text-success-600 dark:text-success-400' : 'text-gray-500 dark:text-gray-400' }}">
            @if($requisito['ok'])
                <x-heroicon-s-check-circle class="w-4 h-4 shrink-0" />
            @else
                <x-heroicon-o-x-circle class="w-4 h-4 shrink-0" />
            @endif
            <span>{{ $requisito['label'] }}</span>
        </li>
    @endforeach
</ul>
