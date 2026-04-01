<x-filament-panels::page>
    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Hero Image --}}
        @if($image)
            <div class="w-full rounded-xl overflow-hidden shadow-sm relative group" style="aspect-ratio: 2/1;">
                <img src="{{ $image }}" alt="{{ $this->getTitle() }}"
                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-end">
                    <h1 class="text-3xl md:text-4xl font-bold text-white p-6 drop-shadow-md">
                        {{ $this->getTitle() }}
                    </h1>
                </div>
            </div>
        @endif

        {{-- Content --}}
        <div
            class="prose prose-lg dark:prose-invert max-w-none bg-white dark:bg-gray-900 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            {!! $content !!}
        </div>
    </div>
</x-filament-panels::page>