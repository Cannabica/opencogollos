<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Plant;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Indoor;

class PlantList extends BaseWidget
{

    use InteractsWithPageFilters;

    protected function getTableHeading(): string
    {
        return __('Plant List'); // Título traducido
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Plant::query()
                    ->whereHas('indoor', function ($query) {
                        $query->where('tenant_id', auth()->user()->tenant_id);
                    })
                    ->when(
                        $this->filters['indoor'] ?? null,
                        fn (Builder $query, $indoorId) => $query->where('indoor_id', $indoorId)
                    )
                    ->latest()
            )
            ->columns([
                Stack::make([
                    ViewColumn::make('plant_card')
                        ->view('filament.tables.columns.plant-card')
                        ->alignCenter()
                        ->state(function ($record) {
                            $stateIcons = [
                                'Etapa de Germinación' => 'heroicon-o-sparkles',
                                'Etapa de Plantula' => 'heroicon-o-arrow-up-circle',
                                'Etapa Vegetativa' => 'heroicon-o-sun',
                                'Etapa Floracion' => 'heroicon-o-star',
                                'Muerta' => 'heroicon-o-x-circle',
                            ];

                            $stateBadgeClass = strtolower(str_replace(['Etapa de ', 'Etapa '], '', $record->state));
                            $stateBadgeClass = str_replace(' ', '-', $stateBadgeClass);

                            return [
                                'stateIcon' => $stateIcons[$record->state] ?? 'heroicon-o-question-mark-circle',
                                'stateBadgeClass' => $stateBadgeClass,
                                'daysInState' => now()->diffInDays($record->actions()
                                    ->where('action_type_id', 7)
                                    ->orderByDesc('action_date')
                                    ->first()?->action_date ?? $record->germination_date),
                                'totalDays' => now()->diffInDays($record->germination_date),
                                'actionsCount' => $record->actions()->count(),
                                'pruningsCount' => $record->actions()->where('action_type_id', 2)->count(),
                            ];
                        })
                ])
                ->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->tooltip('Ver detalles')
                    ->button()
                    ->size('sm')
                    ->extraAttributes([
                        'class' => 'rounded-full',
                    ]),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->tooltip('Editar planta')
                    ->button()
                    ->size('sm')
                    ->extraAttributes([
                        'class' => 'rounded-full',
                    ]),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->tooltip('Eliminar planta')
                    ->button()
                    ->size('sm')
                    ->extraAttributes([
                        'class' => 'rounded-full',
                    ]),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label(__('Plant State'))
                    ->options([
                        'Etapa de Germinación' => 'Etapa de Germinación',
                        'Etapa de Plantula' => 'Etapa de Plantula',
                        'Etapa Vegetativa' => 'Etapa Vegetativa',
                        'Etapa Floracion' => 'Etapa Floracion',
                    ]),
                SelectFilter::make('indoor_id')
                    ->label(__('Indoor'))
                    ->options(function () {
                        return Indoor::pluck('name', 'id');
                    }),
            ]);
    }
}
