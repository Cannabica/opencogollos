<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\SeedResource\Pages;
use App\Filament\Tenant\Resources\SeedResource\RelationManagers;
use App\Models\Seed;
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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;


class SeedResource extends Resource
{
    protected static ?string $model = Seed::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Plantas';


    public static function getPluralLabel(): string
    {
        return __('Seeds');
    }
    public static function getLabel(): string
    {
        return __('Seed');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información básica')
                    ->description('Datos principales de la semilla')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->columnSpan(2),

                        Select::make('seed_type')
                            ->label(__('Tipo de semilla'))
                            ->options([
                                'Fotoperiodica feminizada' => 'Fotoperiodica feminizada',
                                'Fotoperiodica regular' => 'Fotoperiodica regular',
                                'Automatica' => 'Automatica'
                            ])
                            ->required()
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Section::make('Características técnicas')
                    ->description('Propiedades y composición de la semilla')
                    ->schema([
                        TextInput::make('flowering_time')
                            ->label(__('Tiempo de floración'))
                            ->numeric()
                            ->suffix(label: __('weeks'))
                            ->nullable(),

                        TextInput::make('ratio_thc')
                            ->label(__('Ratio THC'))
                            ->numeric()
                            ->suffix(label: '%')
                            ->required(),

                        TextInput::make('ratio_cbd')
                            ->label(__('Ratio CBD'))
                            ->numeric()
                            ->suffix(label: '%')
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Certificaciones')
                    ->description('Información sobre aprobaciones y certificaciones oficiales')
                    ->schema([
                        Forms\Components\Toggle::make('aprobado_inase')
                            ->label('Aprobado INASE')
                            ->helperText('Indica si la semilla está aprobada por el Instituto Nacional de Semillas')
                            ->default(false)
                            ->onColor('success')
                            ->offColor('danger')
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                    Tables\Actions\Action::make('showGlobal')
                        ->label('Globales')
                        ->icon('heroicon-o-globe-alt')
                        ->color('gray')
                        ->extraAttributes(['class' => 'filter-button'])
                        ->action(function ($livewire) {
                            $livewire->tableFilters['origen']['value'] = 'global';
                        }),
                    Tables\Actions\Action::make('showLocal')
                        ->label('Locales')
                        ->icon('heroicon-o-home')
                        ->color('primary')
                        ->extraAttributes(['class' => 'filter-button'])
                        ->action(function ($livewire) {
                            $livewire->tableFilters['origen']['value'] = 'local';
                        }),
                    Tables\Actions\Action::make('showAll')
                        ->label('Todas')
                        ->icon('heroicon-o-list-bullet')
                        ->color('gray')
                        ->extraAttributes(['class' => 'filter-button'])
                        ->action(function ($livewire) {
                            $livewire->tableFilters['origen']['value'] = null;
                        }),
            ])
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label(__('Name'))
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false),

                BadgeColumn::make('seed_type')
                    ->wrap()
                    ->label(__('Seed Type'))
                    ->columnSpan(1)
                    ->alignment('center') // Alineación horizontal
                    ->verticalAlignment('center') // Alineación vertical                        
                    ->colors(colors: [
                        'secondary' => static fn($record): bool => $record->seed_type === 'Fotoperiodica feminizada',
                        'tertiary' => static fn($record): bool => $record->seed_type === 'Fotoperiodica regular',
                        'dark' => static fn($record): bool => $record->seed_type === 'Automatica',
                    ]),

                TextColumn::make('flowering_time')
                    ->searchable()
                    ->label(__('Flowering Time'))
                    ->suffix(' ' . __('weeks'))
                    ->label(__('Flowering Time')),

                TextColumn::make('ratio_thc')
                    ->searchable()
                    ->suffix('%')
                    ->label(__('Ratio THC')),
                TextColumn::make('ratio_cbd')
                    ->searchable()
                    ->suffix('%')
                    ->label(__('Ratio CBD')),

                IconColumn::make('aprobado_inase')
                    ->boolean()
                    ->label('INASE'),

                TextColumn::make('tenant_id')
                    ->label('Origen')
                    ->formatStateUsing(fn ($record) => $record->isGlobal() ? __('Global') : __('Local'))
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('seed_type')
                    ->label(__('Tipo de semilla'))
                    ->options([
                        'Fotoperiodica feminizada' => 'Fotoperiodica feminizada',
                        'Fotoperiodica regular' => 'Fotoperiodica regular',
                        'Automatica' => 'Automatica'
                    ]),
                
                Tables\Filters\TernaryFilter::make('aprobado_inase')
                    ->label('Aprobado INASE')
                    ->trueLabel('Aprobado')
                    ->falseLabel('No aprobado')
                    ->placeholder('Todos'),

                Tables\Filters\SelectFilter::make('origen')
                    ->label('Origen')
                    ->options([
                        'global' => 'Global',
                        'local' => 'Local'
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'global') {
                            return $query->whereNull(columns: 'tenant_id');
                        }
                        if ($data['value'] === 'local') {
                            return $query->whereNotNull('tenant_id');
                        }
                    }),

                Tables\Filters\Filter::make('ratio_thc')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('ratio_thc_min')
                                    ->label('THC Mínimo')
                                    ->numeric()
                                    ->suffix('%'),
                                Forms\Components\TextInput::make('ratio_thc_max')
                                    ->label('THC Máximo')
                                    ->numeric()
                                    ->suffix('%'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['ratio_thc_min'],
                                fn (Builder $query, $min): Builder => $query->where('ratio_thc', '>=', $min),
                            )
                            ->when(
                                $data['ratio_thc_max'],
                                fn (Builder $query, $max): Builder => $query->where('ratio_thc', '<=', $max),
                            );
                    }),

                Tables\Filters\Filter::make('ratio_cbd')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('ratio_cbd_min')
                                    ->label('CBD Mínimo')
                                    ->numeric()
                                    ->suffix('%'),
                                Forms\Components\TextInput::make('ratio_cbd_max')
                                    ->label('CBD Máximo')
                                    ->numeric()
                                    ->suffix('%'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['ratio_cbd_min'],
                                fn (Builder $query, $min): Builder => $query->where('ratio_cbd', '>=', $min),
                            )
                            ->when(
                                $data['ratio_cbd_max'],
                                fn (Builder $query, $max): Builder => $query->where('ratio_cbd', '<=', $max),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isGlobal())
                    ->before(function ($record) {
                        if ($record->isGlobal() && auth()->user()->tenant_id !== null) {
                            \Log::warning('Intento de edición de semilla global:', [
                                'user_id' => auth()->id(),
                                'tenant_id' => auth()->user()->tenant_id,
                                'seed_id' => $record->id
                            ]);
                            Notification::make()
                                ->warning()
                                ->title('Acción no permitida')
                                ->body('No tienes permiso para editar semillas globales. Puedes crear una copia para tu catálogo.')
                                ->send();
                            
                            return false;
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !$record->isGlobal())
                    ->before(function ($record) {
                        // Verificar si hay plantas asociadas
                        $plantCount = \App\Models\Plant::where('seed_id', $record->id)->count();
                        if ($plantCount > 0) {
                            Notification::make()
                                ->warning()
                                ->title('Acción no permitida')
                                ->body("No se puede eliminar la semilla '{$record->name}' porque tiene {$plantCount} plantas asociadas.")
                                ->send();
                            
                            return false;
                        }

                        if ($record->isGlobal() && auth()->user()->tenant_id !== null) {
                            \Log::warning('Intento de eliminación de semilla global:', [
                                'user_id' => auth()->id(),
                                'tenant_id' => auth()->user()->tenant_id,
                                'seed_id' => $record->id
                            ]);
                            
                            Notification::make()
                                ->warning()
                                ->title('Acción no permitida')
                                ->body('No tienes permiso para eliminar semillas globales.')
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
                                    ->body('Esta semilla está siendo utilizada por una o más plantas y no puede ser eliminada.')
                                    ->send();
                                return false;
                            }
                            throw $e;
                        }
                    }),
                Tables\Actions\Action::make('copy')
                    ->label('Copiar a mi catálogo')
                    ->icon('heroicon-o-document-duplicate')
                    ->visible(fn ($record) => $record->isGlobal())
                    ->action(function ($record) {
                        $newName = str_replace(' - Copia', '', $record->name) . ' - Copia';
                        
                        // Verificar si ya existe una copia con ese nombre
                        $copyNumber = 1;
                        $originalName = $newName;
                        while (\App\Models\Seed::where('name', $newName)
                            ->where('tenant_id', auth()->user()->tenant_id)
                            ->exists()) {
                            $copyNumber++;
                            $newName = $originalName . ' (' . $copyNumber . ')';
                        }

                        $newSeed = $record->replicate();
                        $newSeed->tenant_id = auth()->user()->tenant_id;
                        $newSeed->name = $newName;
                        $newSeed->save();

                        Notification::make()
                            ->success()
                            ->title('Semilla copiada exitosamente')
                            ->body("Se ha creado una copia local de la semilla '{$record->name}' con el nombre '{$newName}'.")
                            ->send();
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            // Verificar si hay plantas asociadas a alguna de las semillas
                            foreach ($records as $seed) {
                                $plantCount = \App\Models\Plant::where('seed_id', $seed->id)->count();
                                if ($plantCount > 0) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Acción no permitida')
                                        ->body("No se puede eliminar la semilla '{$seed->name}' porque tiene {$plantCount} plantas asociadas.")
                                        ->send();
                                    
                                    return false;
                                }
                            }

                            // Verificar si hay semillas globales
                            $hasGlobalSeeds = $records->contains(fn ($record) => 
                                $record->isGlobal()
                            );

                            if ($hasGlobalSeeds && auth()->user()->tenant_id !== null) {
                                \Log::warning('Intento de eliminación de semillas globales bloqueado:', [
                                    'user_id' => auth()->id(),
                                    'tenant_id' => auth()->user()->tenant_id
                                ]);

                                Notification::make()
                                    ->warning()
                                    ->title('Acción no permitida')
                                    ->body('No tienes permiso para eliminar semillas globales. Deselecciona las semillas globales para continuar.')
                                    ->send();
                                
                                return false;
                            }
                        })
                        ->using(function (Collection $records) {
                            $success = true;
                            $failedSeeds = [];

                            foreach ($records as $record) {
                                try {
                                    $record->delete();
                                } catch (\PDOException $e) {
                                    if ($e->getCode() === '23000') {
                                        $success = false;
                                        $failedSeeds[] = $record->name;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }

                            if (!$success) {
                                Notification::make()
                                    ->warning()
                                    ->title('Algunas semillas no pudieron ser eliminadas')
                                    ->body('Las siguientes semillas están siendo utilizadas por plantas y no pueden ser eliminadas: ' . implode(', ', $failedSeeds))
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
            'index' => Pages\ListSeeds::route('/'),
            'create' => Pages\CreateSeed::route('/create'),
            'view' => Pages\ViewSeed::route('/{record}'),
            'edit' => Pages\EditSeed::route('/{record}/edit'),
        ];
    }
}
