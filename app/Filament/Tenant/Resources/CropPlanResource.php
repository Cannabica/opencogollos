<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\CropPlanResource\Pages;
use App\Filament\Tenant\Resources\CropPlanResource\RelationManagers;
use App\Models\CropPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class CropPlanResource extends Resource
{
    protected static ?string $model = CropPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationGroup = 'Plantas';

    // Especificar que este recurso pertenece al panel de tenant
    protected static ?string $tenant = 'tenant';

    public static function getPluralLabel(): string
    {
        return __('Crop Plans');
    }
    public static function getLabel(): string
    {
        return __('Crop Plan');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos Generales')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->columnSpan('full')
                            ->required(),

                        TextInput::make('rest_pruning')
                            ->label('Descanso entre podas')
                            ->helperText(__('helper_rest_pruning'))
                            ->numeric(1)
                            ->suffix(__('days'))
                            ->step(1)
                            ->required(),

                        TextInput::make('rest_fert')
                            ->label('Descanso entre fertilizaciones')
                            ->helperText(__('helper_rest_fert'))
                            ->numeric()
                            ->suffix(__('days'))
                            ->step(1)
                            ->required(),

                        TextInput::make('stop_fert')
                            ->label('Dejar de fertilizar antes de fecha de corte')
                            ->helperText(__('helper_stop_fert'))
                            ->numeric()
                            ->suffix(__('days'))
                            ->step(1)
                            ->required(),

                        TextInput::make('irrigation')
                            ->label('Irrigación de maceta sugerida')
                            ->helperText(__('helper_irrigation'))
                            ->suffix('%')
                            ->numeric()
                            ->required(),
                    ]),

                Section::make('Etapa de germinación')
                    ->columns('4')
                    ->schema([
                        Fieldset::make('Período comprendido')
                            ->label('Maximos dias de vida en etapa')
                            ->columnSpan('1')
                            ->schema([
                                TextInput::make('germination_since')
                                    ->label('Primer aviso')
                                    ->suffix('dias')
                                    ->numeric()
                                    ->step(1)
                                    ->columnSpan('2')
                                    ->required(),

                                TextInput::make('germination_until')
                                    ->label('Segundo aviso')
                                    ->numeric()
                                    ->suffix('dias')
                                    ->columnSpan('2')
                                    ->step(1)
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_max_days_stage')),
                            ]),

                        Fieldset::make('Parametros ambiente')
                            ->columnSpan('3')
                            ->columns(columns: '2')
                            ->inlineLabel()
                            ->schema([
                                TextInput::make('germination_light')
                                    ->label('Luz encendida')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('germination_darkness')
                                    ->label('Oscuridad')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('germination_humidity_since')
                                    ->label('Humedad min')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('germination_humidity_until')
                                    ->label('Humedad max')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('germination_temp_since')
                                    ->label('Temp min')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('germination_temp_until')
                                    ->label('Temp max')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_ambience_parameters')),
                            ]),
                    ]),

                Section::make('Etapa Plantula')
                    ->columns('4')
                    ->schema([
                        Fieldset::make('Período comprendido')
                            ->label('Maximos dias de vida en etapa')
                            ->columnSpan('1')
                            ->schema([
                                TextInput::make('plantula_since')
                                    ->label('Primer aviso')
                                    ->suffix('dias')
                                    ->numeric()
                                    ->step(1)
                                    ->columnSpan('2')
                                    ->required(),

                                TextInput::make('plantula_until')
                                    ->label('Segundo aviso')
                                    ->numeric()
                                    ->suffix('dias')
                                    ->columnSpan('2')
                                    ->step(1)
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_max_days_stage')),
                            ]),

                        Fieldset::make('Parametros ambiente')
                            ->columnSpan('3')
                            ->columns('2')
                            ->inlineLabel()
                            ->schema([
                                TextInput::make('plantula_light')
                                    ->label('Luz encendida')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('plantula_darkness')
                                    ->label('Oscuridad')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('plantula_humidity_since')
                                    ->label('Humedad min')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('plantula_humidity_until')
                                    ->label('Humedad max')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('plantula_temp_since')
                                    ->label('Temp min')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('plantula_temp_until')
                                    ->label('Temp max')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_ambience_parameters')),
                            ]),
                    ]),

                Section::make('Etapa Vegetativa')
                    ->columns('4')
                    ->schema([
                        Fieldset::make('Período comprendido')
                            ->label('Maximos dias de vida en etapa')
                            ->columnSpan('1')
                            ->schema([
                                TextInput::make('vegetative_since')
                                    ->label('Primer aviso')
                                    ->suffix('dias')
                                    ->numeric()
                                    ->step(1)
                                    ->columnSpan('2')
                                    ->required(),

                                TextInput::make('vegetative_until')
                                    ->label('Segundo aviso')
                                    ->numeric()
                                    ->suffix('dias')
                                    ->columnSpan('2')
                                    ->step(1)
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_max_days_stage')),
                            ]),

                        Fieldset::make('Parametros ambiente')
                            ->columnSpan('3')
                            ->columns('2')
                            ->inlineLabel()
                            ->schema([
                                TextInput::make('vegetative_light')
                                    ->label('Luz encendida')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('vegetative_darkness')
                                    ->label('Oscuridad')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('vegetative_humidity_since')
                                    ->label('Humedad min')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('vegetative_humidity_until')
                                    ->label('Humedad max')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('vegetative_temp_since')
                                    ->label('Temp min')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('vegetative_temp_until')
                                    ->label('Temp max')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_ambience_parameters')),
                            ]),
                    ]),

                Section::make('Etapa Floración')
                    ->columns('4')
                    ->schema([
                        Fieldset::make('Período comprendido')
                            ->label('Maximos dias de vida en etapa')
                            ->columnSpan('1')
                            ->schema([
                                TextInput::make('flowering_since')
                                    ->label('Primer aviso')
                                    ->suffix('dias')
                                    ->numeric()
                                    ->step(1)
                                    ->columnSpan('2')
                                    ->required(),

                                TextInput::make('flowering_until')
                                    ->label('Segundo aviso')
                                    ->numeric()
                                    ->suffix('dias')
                                    ->columnSpan('2')
                                    ->step(1)
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_max_days_stage')),
                            ]),

                        Fieldset::make('Parametros ambiente')
                            ->columnSpan('3')
                            ->columns('2')
                            ->inlineLabel()
                            ->schema([
                                TextInput::make('flowering_light')
                                    ->label('Luz encendida')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('flowering_darkness')
                                    ->label('Oscuridad')
                                    ->numeric()
                                    ->suffix('hs')
                                    ->required(),

                                TextInput::make('flowering_humidity_since')
                                    ->label('Humedad min')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('flowering_humidity_until')
                                    ->label('Humedad max')
                                    ->suffix('%')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('flowering_temp_since')
                                    ->label('Temp min')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('flowering_temp_until')
                                    ->label('Temp max')
                                    ->suffix('°C')
                                    ->columnSpan('1')
                                    ->numeric()
                                    ->required(),

                                Placeholder::make('documentation')
                                    ->label('')
                                    ->columnSpan('full')
                                    ->helperText(__('helper_ambience_parameters')),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 1, // Una columna en móviles
                'sm' => 1,      // Una columna en tablets pequeñas
                'md' => 1,      // Una columna en tablets
                'lg' => 2,      // Dos columnas en desktop
                'xl' => 2,      // Dos columnas en pantallas grandes
            ])
            ->paginated([6, 12, 24])
            ->defaultPaginationPageOption(6)
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Panel::make([
                        // Título del plan
                        TextColumn::make('name')
                            ->label('Nombre del Plan')
                            ->size(TextColumn\TextColumnSize::Large)
                            ->weight('bold')
                            ->searchable()
                            ->sortable()
                            ->url(fn ($record) => static::getUrl('view', ['record' => $record]))
                            ->alignment('center')
                            ->color('primary')
                            ->extraAttributes(['class' => 'py-2']),

                        // Resumen de días
                        TextColumn::make('stages_summary')
                            ->label('Duración del Ciclo')
                            ->state(function ($record) {
                                $totalDays = $record->germination_since + $record->plantula_since + 
                                           $record->vegetative_since + $record->flowering_since;
                                $totalWeeks = round($totalDays / 7, 1);
                                return "{$totalDays} días totales | {$totalWeeks} semanas";
                            })
                            ->size(TextColumn\TextColumnSize::Small)
                            ->alignment('center')
                            ->extraAttributes(['class' => 'py-1 text-gray-600']),

                        // Información de etapas
                        Tables\Columns\Layout\Grid::make([
                            'default' => 1, // Una columna en móviles
                            'sm' => 1,      // Una columna en tablets pequeñas
                            'md' => 2,      // Dos columnas en tablets y desktop
                        ])
                            ->schema([
                            // Etapas iniciales
                                TextColumn::make('stages_left')
                                    ->label('Etapas Iniciales')
                                    ->state(function ($record) {
                                        return "**GERMINACIÓN**\n" .
                                           "• Duración: **{$record->germination_since}** días (" . round($record->germination_since/7, 1) . " semanas)\n" .
                                           "• Ciclo de horas: **{$record->germination_light}** • Oscuridad: **{$record->germination_darkness}**\n\n" .
                                           "**PLÁNTULA**\n" .
                                           "• Duración: **{$record->plantula_since}** días (" . round($record->plantula_since/7, 1) . " semanas)\n" .
                                           "• Ciclo de horas: **{$record->plantula_light}** • Oscuridad: **{$record->plantula_darkness}**";
                                })
                                ->markdown()
                                ->extraAttributes(['class' => 'p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm']),

                            // Etapas finales
                            TextColumn::make('stages_right')
                                ->label('Etapas Finales')
                                ->state(function ($record) {
                                    return "**VEGETATIVO**\n" .
                                           "• Duración: **{$record->vegetative_since}** días (" . round($record->vegetative_since/7, 1) . " semanas)\n" .
                                           "• Ciclo de horas: **{$record->vegetative_light}** • Oscuridad: **{$record->vegetative_darkness}**\n\n" .
                                           "**FLORACIÓN**\n" .
                                           "• Duración: **{$record->flowering_since}** días (" . round($record->flowering_since/7, 1) . " semanas)\n" .
                                           "• Ciclo de horas: **{$record->flowering_light}** • Oscuridad: **{$record->flowering_darkness}**";
                                })
                                ->markdown()
                                ->extraAttributes(['class' => 'p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm']),
                        ])
                        ->extraAttributes(['class' => 'gap-3 py-2']),

                        // Información ambiental y mantenimiento
                        Tables\Columns\Layout\Grid::make([
                            'default' => 1, // Una columna en móviles
                            'sm' => 1,      // Una columna en tablets pequeñas
                            'md' => 2,      // Dos columnas en tablets y desktop
                        ])
                        ->schema([
                            TextColumn::make('environment')
                                ->label('Condiciones Ambientales')
                                ->state(function ($record) {
                                    return "**TEMPERATURA:** {$record->vegetative_temp_since}°C - {$record->vegetative_temp_until}°C\n" .
                                           "**HUMEDAD:** {$record->vegetative_humidity_since}% - {$record->vegetative_humidity_until}%\n" .
                                           "**RIEGO:** {$record->irrigation}% capacidad";
                                })
                                ->markdown()
                                ->extraAttributes(['class' => 'p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm']),

                            TextColumn::make('maintenance')
                                ->label('Mantenimiento')
                                ->state(function ($record) {
                                    return "**PODAS:** Cada {$record->rest_pruning} días\n" .
                                           "**FERTILIZACIÓN:** Cada {$record->rest_fert} días\n" .
                                           "**DETENER:** {$record->stop_fert} días antes del corte";
                                })
                                ->markdown()
                                ->extraAttributes(['class' => 'p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm']),
                        ])
                        ->extraAttributes(['class' => 'gap-3 py-2']),
                    ])
                    ->collapsible(false),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isGlobal())
                    ->before(function ($record) {
                        if ($record->isGlobal() && auth()->user()->tenant_id !== null) {
                            \Log::warning('Intento de edición de plan de cultivo global:', [
                                'user_id' => auth()->id(),
                                'tenant_id' => auth()->user()->tenant_id,
                                'crop_plan_id' => $record->id
                            ]);
                            Notification::make()
                                ->warning()
                                ->title('Acción no permitida')
                                ->body('No tienes permiso para editar planes de cultivo globales. Puedes crear una copia para tu catálogo.')
                                ->send();
                            
                            return false;
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !$record->isGlobal())
                    ->before(function ($record) {
                        if ($record->isGlobal() && auth()->user()->tenant_id !== null) {
                            \Log::warning('Intento de eliminación de plan de cultivo global:', [
                                'user_id' => auth()->id(),
                                'tenant_id' => auth()->user()->tenant_id,
                                'crop_plan_id' => $record->id
                            ]);
                            
                            Notification::make()
                                ->warning()
                                ->title('Acción no permitida')
                                ->body('No tienes permiso para eliminar planes de cultivo globales.')
                                ->send();
                            
                            return false;
                        }

                        // Verificar si hay indoors usando este plan
                        $indoorCount = \App\Models\Indoor::where('crop_plan_id', $record->id)->count();
                        if ($indoorCount > 0) {
                            Notification::make()
                                ->warning()
                                ->title('Acción no permitida')
                                ->body("No se puede eliminar el plan de cultivo '{$record->name}' porque está siendo usado por {$indoorCount} indoor(s).")
                                ->send();
                            
                            return false;
                        }
                    })
                    ->using(function ($record) {
                        try {
                            $record->delete();
                            return true;
                        } catch (\PDOException $e) {
                            if ($e->getCode() === '23000') {
                                Notification::make()
                                    ->warning()
                                    ->title('No se puede eliminar')
                                    ->body('Este plan de cultivo está siendo utilizado y no puede ser eliminado.')
                                    ->send();
                                return false;
                            }
                            throw $e;
                        }
                    }),
                Tables\Actions\Action::make('duplicate')
                    ->label('Copiar a mi catálogo')
                    ->icon('heroicon-o-document-duplicate')
                    ->visible(fn ($record) => $record->isGlobal())
                    ->action(function ($record) {
                        $newName = str_replace(' - Copia', '', $record->name) . ' - Copia';
                        
                        // Verificar si ya existe una copia con ese nombre
                        $copyNumber = 1;
                        $originalName = $newName;
                        while (\App\Models\CropPlan::where('name', $newName)
                            ->where('tenant_id', auth()->user()->tenant_id)
                            ->exists()) {
                            $copyNumber++;
                            $newName = $originalName . ' (' . $copyNumber . ')';
                        }

                        $newPlan = $record->replicate();
                        $newPlan->tenant_id = auth()->user()->tenant_id;
                        $newPlan->name = $newName;
                        $newPlan->save();

                        Notification::make()
                            ->success()
                            ->title('Plan de cultivo copiado exitosamente')
                            ->body("Se ha creado una copia local del plan '{$record->name}' con el nombre '{$newName}'.")
                            ->send();
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            // Verificar si hay planes globales
                            $hasGlobalPlans = $records->contains(fn ($record) => 
                                $record->isGlobal()
                            );

                            if ($hasGlobalPlans && auth()->user()->tenant_id !== null) {
                                \Log::warning('Intento de eliminación de planes de cultivo globales bloqueado:', [
                                    'user_id' => auth()->id(),
                                    'tenant_id' => auth()->user()->tenant_id
                                ]);

                                Notification::make()
                                    ->warning()
                                    ->title('Acción no permitida')
                                    ->body('No tienes permiso para eliminar planes de cultivo globales. Deselecciona los planes globales para continuar.')
                                    ->send();
                                
                                return false;
                            }

                            // Verificar si hay indoors usando alguno de los planes
                            foreach ($records as $plan) {
                                $indoorCount = \App\Models\Indoor::where('crop_plan_id', $plan->id)->count();
                                if ($indoorCount > 0) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Acción no permitida')
                                        ->body("No se puede eliminar el plan de cultivo '{$plan->name}' porque está siendo usado por {$indoorCount} indoor(s).")
                                        ->send();
                                    
                                    return false;
                                }
                            }
                        })
                        ->using(function (Collection $records) {
                            $success = true;
                            $failedPlans = [];

                            foreach ($records as $record) {
                                try {
                                    $record->delete();
                                } catch (\PDOException $e) {
                                    if ($e->getCode() === '23000') {
                                        $success = false;
                                        $failedPlans[] = $record->name;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }

                            if (!$success) {
                                Notification::make()
                                    ->warning()
                                    ->title('Algunos planes no pudieron ser eliminados')
                                    ->body('Los siguientes planes están siendo utilizados y no pueden ser eliminados: ' . implode(', ', $failedPlans))
                                    ->send();
                                return false;
                            }

                            return true;
                        })
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCropPlans::route('/'),
            'create' => Pages\CreateCropPlan::route('/create'),
            'view' => Pages\ViewCropPlan::route('/{record}'),
            'edit' => Pages\EditCropPlan::route('/{record}/edit'),
        ];
    }
}

