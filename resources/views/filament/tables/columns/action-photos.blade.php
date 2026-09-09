@php
    $record = $getRecord();
    $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;
    $imageData = $data['observation']['image'] ?? null;
    $paths = $imageData === null ? [] : (is_array($imageData) ? $imageData : [$imageData]);
    $fotos = [];
    foreach ($paths as $p) {
        if ($p && \Illuminate\Support\Facades\Storage::disk('public')->exists($p)) {
            $fotos[] = \Illuminate\Support\Facades\Storage::disk('public')->url($p);
        }
    }
@endphp
@if (count($fotos))
    <div class="flex items-center gap-1.5 flex-wrap">
        @foreach (array_slice($fotos, 0, 4) as $url)
            <a href="{{ $url }}" target="_blank" title="Ver foto">
                <img
                    src="{{ $url }}"
                    loading="lazy"
                    alt="Foto de la observación"
                    class="h-10 w-10 rounded-md object-cover ring-1 ring-gray-200 dark:ring-white/10 hover:opacity-80 transition"
                />
            </a>
        @endforeach
        @if (count($fotos) > 4)
            <span class="text-xs text-gray-400 dark:text-gray-500">+{{ count($fotos) - 4 }}</span>
        @endif
    </div>
@endif
