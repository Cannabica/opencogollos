<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\ActionsResource\Pages;
use App\Filament\Tenant\Resources\ActionsResource\RelationManagers;
use App\Models\Action;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\PlantState;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\DateFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Carbon\Carbon;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;

class ActionsResource extends Resource
{
    protected static ?string $model = Action::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getPluralLabel(): string
    {
        return __('Actions');
    }
    public static function getLabel(): string
    {
        return __('Action');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('action_date')
                    ->label(__('Action Date'))
                    ->default(Carbon::now())
                    ->required(),

                ToggleButtons::make('action_type_id')
                    ->label(__('Action Type'))
                    ->options(
                        ActionType::pluck('name', 'id')->toArray()
                    )
                    ->reactive()
                    ->inline()
                    ->required()
                    ->disabled(fn ($record) => $record !== null), // Desactiva el campo si el registro ya existe 

                Placeholder::make('Disclaimer')
                    ->content(function(Get $get) {
                        if($get('action_type_id') != null) {
                            $actionClass = new (ActionType::find($get('action_type_id'))->action_class);
                            return $actionClass->disclaimer() ? new HtmlString(
                                '<div style="width: 100%;padding:15px;background: #caca00; color: #5a5a00;border: 1px solid #5a5a00;border-radius: 10px;">' .
                                $actionClass->disclaimer() .
                                '</div>'
                            ) : '';
                        }
                        return '';
                    })
                    ->visible(function(Get $get) {
                        if($get('action_type_id') != null) {
                            $actionClass = new (ActionType::find($get('action_type_id'))->action_class);
                            return $actionClass->disclaimer();
                        }
                        return false;
                    }),

                // Select Indoor
                Select::make('indoor_id')
                    ->label(__('Indoor'))
                    ->options(Indoor::where('tenant_id', auth()->user()->tenant_id)
                        ->pluck('name', 'id')
                        ->toArray())
                    ->reactive()
                    ->default(fn () => Indoor::where('tenant_id', auth()->user()->tenant_id)->count() === 1
                        ? Indoor::where('tenant_id', auth()->user()->tenant_id)->value('id')
                        : null)
                    ->required(),

                // Select Plants based on Indoor
                CheckboxList::make('data.plants')
                    ->label(__('Plants'))
                    ->options(function (callable $get) {
                        $indoorId = $get('indoor_id'); // Obtener el valor seleccionado de indoor_id
                        return $indoorId
                            ? Plant::where('indoor_id', $indoorId)->pluck('name', 'id')->toArray()
                            : []; // Retorna las plantas correspondientes o un arreglo vacío si no hay indoor seleccionado
                    })
                    ->columns(2)
                    ->bulkToggleable()
                    ->required(),

                Section::make(__('Irrigation'))
                    ->schema([

                        Select::make('data.irrigation.irrigation_type')
                            ->label(__('Irrigation Type'))
                            ->options([
                                'liters' => 'Fixed liters of water',
                                'timer' => 'Timer time'
                            ])
                            ->reactive(),

                        TextInput::make('data.irrigation.liters')
                            ->label(__('Fixed liters of water'))
                            ->numeric()
                            ->visible(fn ($get) => $get('data.irrigation.irrigation_type') === 'liters'),

                        TextInput::make('data.irrigation.timer')
                            ->label(__('Timer time'))
                            ->numeric()
                            ->visible(fn ($get) => $get('data.irrigation.irrigation_type') === 'timer'),
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 1),

                Section::make(__('Pruning'))
                    ->schema([

                        CheckboxList::make('data.pruning.pruning_type')
                            ->label(__('Pruning Type'))
                            ->options([
                                'excess' => 'Quite excedente de hojas',
                                'dry' => 'Quite hojas amarillentas o secas',
                                'apical' => 'Apical',
                                'topping' => 'Topping',
                                'scrog' => 'Scrog'
                            ]),
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 2),

                Section::make(__('Product Application'))
                    ->schema([

                        Select::make('data.product_application.application_type')
                            ->label(__('Application Type'))
                            ->options([
                                'vege' => 'Aplicacion para vege',
                                'flora' => 'Flora',
                                'plantula' => 'Plantula',
                                'plague' => 'Anti-plaga',
                                'soap' => 'Lavado con jabon potasico',
                                'other' => 'Otro'
                            ])
                            ->required(),

                        Textarea::make('data.product_application.observation')
                            ->label(__('Observations')),

                        Textarea::make('data.product_application.comments')
                            ->label(__('Comments')),
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 3),

                Section::make(__('Transplant'))
                    ->schema([

                        Placeholder::make('current_pot_size')
                            ->label(__('Current Pot Size'))
                            ->content(fn ($record) => $record->data['transplant']['new_pot_size'] ?? __('No pot size available')),

                        Select::make('data.transplant.new_pot_size')
                            ->label(__('Tamaño de la nueva maceta'))
                            ->options([
                                'N10' => 'N10',
                                'N12' => 'N12',
                                'N14' => 'N14',
                                '3L'  => '3L',
                                '5L'  => '5L',
                                '7L'  => '7L',
                                '10L' => '10L',
                                '12L' => '12L',
                                '15L' => '15L',
                                '20L' => '20L',
                                '30L' => '30L',
                                '40L' => '40L',
                                '50L' => '50L',
                                '75L' => '75L',
                            ])
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 4),

                Section::make(__('Observation with photo'))
                    ->schema([

                        FileUpload::make('data.observation.image')
                            ->image()
                            ->imageEditor(),

                        Textarea::make('data.observation.comments')
                            ->label(__('Comments')),
                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 5),

                Section::make(__('Death'))
                    ->label(__('Death'))
                    ->schema([

                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 6),

                Section::make(__('Change of State'))
                    ->schema([

                        Select::make('data.change_state.state')
                            ->label(__('Change State'))
                            ->options([
                                'Etapa de Germinación' => 'Etapa de Germinación',
                                'Etapa de Plantula' => 'Etapa de Plantula',
                                'Etapa Vegetativa' => 'Etapa Vegetativa',
                                'Etapa Floracion' => 'Etapa Floracion',
                            ])
                            ->required(),

                    ])
                    ->visible(fn(Get $get) => $get('action_type_id') == 7),

            ])->columns(1);
    }

    public function afterCreate($record)
    {
        $this->executeActionTrigger($record);
    }

    protected function executeActionTrigger(Action $action)
    {
        $plant = Plant::find($action->plant_id); // Asumiendo que tienes una referencia a la planta

        // Obtener la clase de acción desde el registro de `action_class`
        $actionClass = $action->action_type->action_class;

        // Instanciar la clase de acción y ejecutar su método trigger
        if (class_exists($actionClass)) {
            $actionInstance = new $actionClass(/* pasa aquí parámetros adicionales si es necesario */);
            $actionInstance->trigger($plant);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columna para mostrar la fecha de la acción
                TextColumn::make('created_at')
                    ->label(__('Fecha'))
                    ->dateTime('d/m/Y')
                    ->sortable(), // Permite ordenar por fecha

                // Columna para mostrar el tipo de acción
                TextColumn::make('action_type.name')
                    ->label(__('Tipo de acción'))
                    ->sortable() // Permite ordenar por tipo de acción
                    ->searchable(), // Permite buscar por tipo de acción

                // Columna para mostrar la cantidad de plantas afectadas
                TextColumn::make('plants_count')
                    ->label(__('Cantidad de plantas afectadas'))
                    ->getStateUsing(fn ($record) => $record->plants_count)
                    ->sortable() // Permite ordenar por cantidad de plantas afectadas
                    ->searchable(),

                // Columna para mostrar el detalle de la acción
                TextColumn::make('detalle_accion')
                    ->label(__('Detalle de acción'))
                    ->getStateUsing(fn ($record) => $record->detalle_accion)
                    ->sortable(),


            ])
            ->filters([

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                SelectFilter::make('action_type_id')
                    ->label(__('Tipo de acción'))
                    ->options([
                        1 => __('Irrigación'),
                        2 => __('Poda'),
                        3 => __('Aplicación de Producto'),
                        4 => __('Transplante'),
                        5 => __('Observación'),
                        6 => __('Muerte'),
                        7 => __('Cambio de Estado'),
                        // Agrega más opciones según los tipos de acción que tengas
                    ])

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListActions::route('/'),
            'create' => Pages\CreateActions::route('/create'),
            'edit' => Pages\EditActions::route('/{record}/edit'),
        ];
    }
}
