@php
    $data = $this->getData();
@endphp

<x-filament::widget class="fi-wi-action-types-line">
    <div class="flex items-center justify-between gap-8">
        <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                {{ $this->getHeading() }}
            </h3>
        </div>
    </div>

    <div class="mt-4">
        <form wire:submit.prevent="filter" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{ $this->form }}
        </form>

        <div class="relative h-[32rem]">
            <canvas
                x-data="{
                    chart: null,
                    init() {
                        const ctx = this.$refs.canvas.getContext('2d');
                        this.chart = new Chart(ctx, {
                            type: '{{ $this->getType() }}',
                            data: @js($data),
                            options: @js($this->getOptions())
                        });

                        $wire.on('updateChart', ({ data, options }) => {
                            this.chart.data = data;
                            this.chart.options = options;
                            this.chart.update();
                        });
                    }
                }"
                x-ref="canvas"
                wire:ignore
            ></canvas>
        </div>
    </div>
</x-filament::widget>