<?php

namespace App\Filament\Tenant\Resources\PlantsResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Action;
use App\Models\ActionType;
use Filament\Forms\Get;

class ActionsRelationManager extends RelationManager
{
    protected static string $relationship = 'actions';

    public static function getPluralLabel(): string
    {
        return __('Action');
    }
    public static function getLabel(): string
    {
        return __('Actions');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('action_type_id')
                    ->label(__('Action Type'))
                    ->options(
                        ActionType::pluck('name', 'id')->toArray()
                    )
                    ->reactive()
                    ->required(),
                Forms\Components\Placeholder::make('Action')
                    ->label(__('Action'))
                    ->content(__('Please select an action type first'))
                    ->visible(fn(Get $get) => $get('action_type_id') == null),
                Forms\Components\Section::make(__('Irrigation'))
                    ->label(__('Irrigation'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('data.irrigation.irrigation_date')
                            ->label(__('Irrigation Date'))
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 1),

                Forms\Components\Section::make(__('Pruning'))
                    ->label(__('Pruning'))
                    ->schema([
                        Forms\Components\Select::make('data.pruning.pruning_type')
                            ->label(__('Pruning Type'))
                            ->options([
                                'topping' => 'Poda apical /topping',
                                'yellowish_tips' => 'Puntas amarillentas secas',
                                'excess' => 'Excedente hojas',
                                'scrog' => 'Realizado SCROG',
                                'lollipoping' => 'Realizado lollipoping',
                                'supercropping' => 'Realizado supercropping',
                            ]),
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 2),

                Forms\Components\Section::make(__('Product Application'))
                    ->label(__('Product Application'))
                    ->schema([
                        Forms\Components\Select::make('data.product_application.application_type')
                            ->label(__('Application Type'))
                            ->options([
                                'vege' => 'Aplicación para vege',
                                'flora' => 'Aplicación para flora',
                                'plantula' => 'Aplicación etapa plantula',
                                'plague' => 'Aplicación anti - plaga',
                                'soap' => 'Lavado de planta con jabon potasico',
                            ]),
                        Forms\Components\Textarea::make('data.product_application.observation')
                            ->label(__('Observation')),
                        Forms\Components\Select::make('data.product_application.iterative_process')
                            ->label(__('¿Es parte de un proceso iterativo?'))
                            ->options([
                                1 => 'Si',
                                0 => 'No',
                            ]),
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 3),

                Forms\Components\Section::make(__('Transplant'))
                    ->label(__('Transplant'))
                    ->schema([
                        Forms\Components\Select::make('data.transplant.new_pot_size')
                            ->label(__('Tamaño de la nueva maceta'))
                            ->options([
                                'N10',
                                'N12',
                                'N14',
                                '3L',
                               ' 5L',
                               ' 7L',
                                '10L',
                                '12L',
                                '15L',
                                '20L',
                                '30L',
                                '40L',
                                '50L',
                                '75L',
                            ])
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 4),

                Forms\Components\Section::make(__('Death'))
                    ->label(__('Death'))
                    ->schema([
                        Forms\Components\Textarea::make('data.death.observation')
                            ->label(__('Observation'))
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 6),
                ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action_type_id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Fecha'))
                    ->dateTime('d/m/Y')
                    ->description(__('Registrado el'))
                    ->sortable()
                    ->searchable()
                    ->size('sm'),

                Tables\Columns\TextColumn::make('detalle_accion')
                    ->label(__('Detalle'))
                    ->formatStateUsing(function ($state, $record) {
                        $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;

                        if ($record->action_type->id == 6) {
                            return "La planta ha muerto";
                        }

                        if (isset($data['irrigation'])) {
                            $irrigation = $data['irrigation'];
                            if (($irrigation['irrigation_type'] ?? '') === 'timer') {
                                return "Riego por " . ($irrigation['timer'] ?? '?') . " minutos";
                            } else {
                                return "Riego " . ($irrigation['liters'] ?? '?') . " lts";
                            }
                        } elseif (isset($data['pruning'])) {
                            $types = $data['pruning']['pruning_type'] ?? [];
                            if (!is_array($types)) {
                                $types = [$types];
                            }
                            $typesText = [];
                            foreach ($types as $type) {
                                $typesText[] = match ($type) {
                                    'scrog' => 'scrog',
                                    'excess' => 'exceso de hojas',
                                    'dry' => 'hojas secas',
                                    'apical' => 'apical',
                                    'topping' => 'topping',
                                    'defoliation' => 'defoliación',
                                    'lollipop' => 'lollipop',
                                    default => $type
                                };
                            }

                            $text = "Poda";
                            if (count($typesText) > 1) {
                                $text .= ' ' . implode(', ', $typesText);
                            } else {
                                $text .= ' ' . $typesText[0];
                            }
                            return $text;
                        } elseif (isset($data['transplant'])) {
                            return "Transplante a " . ($data['transplant']['new_flowerpot'] ?? '?') . " " . ($data['transplant']['new_capacity'] ?? '?') . "L";
                        } elseif (isset($data['change_state'])) {
                            return "Cambio de estado a " . ($data['change_state']['state'] ?? '?');
                        } elseif (isset($data['observation'])) {
                            // tolera observaciones sin foto/comentario (data parcial no rompe)
                            $imageData = $data['observation']['image'] ?? null;
                            $images = $imageData === null ? [] : (is_array($imageData) ? $imageData : [$imageData]);
                            $imageCount = count($images);

                            $comments = $data['observation']['comments'] ?? '';
                            $messageText = '';
                            $messageText .= strlen($comments) > 30
                                ? substr($comments, 0, 30) . '...'
                                : $comments;
                            if ($imageCount > 0) {
                                if ($imageCount > 1) {
                                    $messageText .= " ({$imageCount} fotos)";
                                } else {
                                    $messageText .= " (1 foto)";
                                }
                            }

                            return $messageText;
                        } elseif (isset($data['product_application'])) {
                            $appType = $data['product_application']['application_type'] ?? 'desconocido';
                            $observation = substr($data['product_application']['observation'] ?? '', 0, 20);
                            
                            switch ($appType) {
                                case 'flora':
                                    return "Aplicación de producto para flora" . ($observation ? " - $observation" : '');
                                case 'vege':
                                    return "Aplicación de producto para vegetativo" . ($observation ? " - $observation" : '');
                                case 'plantula':
                                    return "Aplicación de producto para plántula" . ($observation ? " - $observation" : '');
                                case 'plague':
                                    return "Aplicación antiplagas" . ($observation ? " - $observation" : '');
                                default:
                                    return "Aplicación de producto ($appType)";
                            }
                        }
                        return $state;
                    })
                    ->wrap()
                    ->searchable()
                    ->size('sm'),

                Tables\Columns\BadgeColumn::make('action_type')
                    ->label(__('Tipo'))
                    ->formatStateUsing(fn($record) => str_replace('Registrar ', '', $record->action_type->name))
                    ->icon(fn($record) => match ($record->action_type->name) {
                        'Registrar Poda' => 'heroicon-o-scissors',
                        'Registrar Transplante' => 'heroicon-o-arrow-path',
                        'Registrar Riego' => 'heroicon-o-cloud',
                        'Registrar Aplique producto' => 'heroicon-o-beaker',
                        'Registrar Observación con foto' => 'heroicon-o-camera',
                        'Registrar Muerte de la planta' => 'heroicon-o-x-circle',
                        'Registrar Cambio de Estado' => 'heroicon-o-arrow-path-rounded-square',
                        default => null
                    })
                    ->colors([
                        'tertiary' => fn($record) => $record->action_type->name === 'Registrar Poda',
                        'accent' => fn($record) => $record->action_type->name === 'Registrar Transplante',
                        'primary' => fn($record) => $record->action_type->name === 'Registrar Riego',
                        'dark' => fn($record) => $record->action_type->name === 'Registrar Aplique producto',
                        'warning' => fn($record) => $record->action_type->name === 'Registrar Observación con foto',
                        'danger' => fn($record) => $record->action_type->name === 'Registrar Muerte de la planta',
                        'gray' => fn($record) => $record->action_type->name === 'Registrar Cambio de Estado',
                    ])
                    ->size('sm'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action_type_id')
                    ->label(__('Tipo de acción'))
                    ->options([
                        1 => __('Irrigación'),
                        2 => __('Poda'),
                        3 => __('Aplicación de Producto'),
                        4 => __('Transplante'),
                        5 => __('Observación'),
                        6 => __('Muerte'),
                        7 => __('Cambio de Estado'),
                    ])
            ])
            ->actions([])
            ->striped()
            ->paginated([
                'default' => 10,
                'sm' => 10,
                'md' => 15,
                'lg' => 20,
            ])
            ->recordUrl(function ($record) {
                if (!$record || !$record->plant_id || !$record->id) {
                    return null;
                }
                return route('filament.tenant.resources.actions.edit', [
                    'record' => $record->getKey(),
                    'tenant' => \Filament\Facades\Filament::getTenant()?->id,
                    'relatedRecord' => $record->plant_id
                ]) . '" target="_blank';
            })
            ->recordClasses(fn($record) => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800')
            ->defaultSort('actions.created_at', 'desc')
            ->defaultPaginationPageOption(10);
    }
}
