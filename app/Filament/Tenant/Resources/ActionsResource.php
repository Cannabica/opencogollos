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
use Filament\Forms\Components\Actions;
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
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Carbon\Carbon;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;
use Filament\Facades\Filament;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Split;
use Schema;
use Filament\Notifications\Notification;
use App\Jobs\SendDelayedProductNotification;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Filament\Tenant\Resources\Actions\Components\PlantSelector;
use App\Filament\Tenant\Resources\Actions\Rules\ActionValidationRules;
use App\Filament\Tenant\Resources\Actions\Services\ActionRecordService;
use App\Filament\Tenant\Resources\Actions\Traits\HandlesActionTypes;


class ActionsResource extends Resource
{
    use HandlesActionTypes;

    protected static ?string $model = Action::class;
    protected ActionRecordService $actionService;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Seguimiento de cultivos';


    protected static ?string $tenantOwnershipRelationshipName = 'indoor';

    public static function getPluralLabel(): string
    {
        return __('Actions');
    }
    public static function getLabel(): string
    {
        return __('Action');
    }

    public function __construct()
    {
        $this->actionService = new ActionRecordService();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Section::make(__('action_basic_data'))
                        ->description(__('action_basic_description'))
                        ->schema([
                            Placeholder::make('warning')
                                ->label(__(''))
                                ->content(fn($record) => $record !== null
                                    ? new HtmlString('<div class="text-warning-600">' . __('Los datos básicos no se pueden modificar una vez creada la acción') . '</div>')
                                    : null)
                                ->visible(fn($record) => $record !== null),

                            DatePicker::make('action_date')
                                ->label(__('Action Date'))
                                ->default(Carbon::now())
                                ->required()
                                ->disabled(fn($record) => $record !== null),



                            // Select Indoor
                            Select::make('indoor_id')
                                ->label(__('Indoor'))
                                ->options(Indoor::where('tenant_id', auth()->user()->tenant_id)
                                    ->pluck('name', 'id')
                                    ->toArray())
                                ->helperText(__('indoor_helper'))
                                ->reactive()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    // Limpiar la selección de plantas cuando cambia el indoor
                                    $set('plants', []);
                                    // Forzar actualización de la vista previa del transplante
                                    $set('data.transplant.preview_key', uniqid());
                                })
                                ->default(function () {
                                    return request()->get('indoor_id');
                                })
                                ->required()
                                ->disabled(fn($record) => $record !== null),

                            // Selector de plantas modularizado
                            PlantSelector::make(),

                            // Campo oculto para mantener el estado
                            Hidden::make('_plants_state'),
                        ])
                    ,
                    Section::make(__('action_action_type_data'))
                        ->disabled(fn($record) => $record !== null)
                        ->description(__('action_action_type_description'))
                        ->schema([
                            Placeholder::make('warning')
                                ->label(__(''))
                                ->content(fn($record) => $record !== null
                                    ? new HtmlString('<div class="text-warning-600">' . __('El tipo de acción no se puede modificar una vez guardada la acción') . '</div>')
                                    : null)
                                ->visible(fn($record) => $record !== null),

                            ToggleButtons::make(name: 'action_type_id')
                                ->label(__('Action Type'))
                                ->options(ActionType::pluck('name', 'id')->toArray())
                                ->icons([
                                    1 => 'heroicon-o-cloud',        // Riego
                                    2 => 'heroicon-o-scissors',     // Poda
                                    3 => 'heroicon-o-beaker',       // Aplique producto
                                    4 => 'heroicon-o-arrow-path',   // Transplante
                                    5 => 'heroicon-o-camera',       // Observación con foto
                                    6 => 'heroicon-o-x-circle',     // Muerte de la planta
                                    7 => 'heroicon-o-arrow-path-rounded-square', // Cambio de estado
                                ])
                                ->reactive()
                                ->columns(2)
                                ->gridDirection('row')
                                ->required()
                                ->default(function () {
                                    return request()->get('action_type_id');
                                }),
                        ])
                        ->grow(),
                ])->from('md'),

                Section::make(__('action_data'))
                    ->description(__('action_data_description'))
                    ->schema([
                        Placeholder::make('Disclaimer')
                            ->content(function (Get $get) {
                                if ($get('action_type_id') != null) {
                                    $actionClass = new (ActionType::find($get('action_type_id'))->action_class);
                                    return $actionClass->disclaimer() ? new HtmlString(
                                        '<div style="width: 100%;padding:15px;background: #caca00; color: #5a5a00;border: 1px solid #5a5a00;border-radius: 10px;">' .
                                        $actionClass->disclaimer() .
                                        '</div>'
                                    ) : '';
                                }
                                return '';
                            })
                            ->visible(function (Get $get) {
                                if ($get('action_type_id') != null) {
                                    $actionClass = new (ActionType::find($get('action_type_id'))->action_class);
                                    return $actionClass->disclaimer();
                                }
                                return false;
                            }),

                        Section::make(__('Irrigation'))
                            ->description(__('Irrigation_type_description'))
                            ->schema([
                                Select::make('data.irrigation.irrigation_type')
                                    ->label(__('Irrigation Type'))
                                    ->options([
                                        'liters' => __('fixed_liters'),
                                        'timer' => __("timer_irrigation")
                                    ])
                                    ->default(function () {
                                        return request()->get('irrigation_type');
                                    })
                                    ->reactive(),

                                TextInput::make('data.irrigation.liters')
                                    ->label(__(__('fixed_liters')))
                                    ->numeric()
                                    ->suffix(label: 'lts')
                                    ->default(function () {
                                        return request()->get('irrigation_type') === 'liters' 
                                            ? request()->get('irrigation_value') 
                                            : null;
                                    })
                                    ->helperText(new HtmlString(__('fixed_liters_helper')))
                                    ->rules(['gt:0'])
                                    ->visible(fn($get) => $get('data.irrigation.irrigation_type') === 'liters'),

                                TextInput::make('data.irrigation.timer')
                                    ->label(__('timer_irrigation'))
                                    ->helperText(new HtmlString(__('timed_irrigation_helper')))
                                    ->numeric()
                                    ->default(function () {
                                        return request()->get('irrigation_type') === 'timer' 
                                            ? request()->get('irrigation_value') 
                                            : null;
                                    })
                                    ->suffix(label: 'min')
                                    ->visible(fn($get) => $get('data.irrigation.irrigation_type') === 'timer'),
                            ])
                            ->visible(fn(Get $get) => $get('action_type_id') == 1),

                        Section::make(__('Pruning'))
                            ->schema([
                                CheckboxList::make('data.pruning.pruning_type')
                                    ->label(__('Pruning Type'))
                                    ->options([
                                        'excess' => 'Excedente de hojas',
                                        'dry' => 'Hojas amarillentas o secas',
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
                                        'vege' => 'Producto para etapa vegetativa',
                                        'flora' => 'Producto para etapa de floracion',
                                        'plantula' => 'Producto para etapa de plantula',
                                        'plague' => 'Anti-plaga',
                                        'other' => 'Otro'
                                    ])
                                    ->required(),

                                Select::make('data.product_application.reminder_time')
                                    ->label('Recordatorio adicional')
                                    ->options([
                                        '5s' => 'En 5 segundos',
                                        '1m' => 'En 1 minuto',
                                        '1d' => 'En 1 día'
                                    ])
                                    ->default('none'),
                                Textarea::make('data.product_application.observation')
                                    ->label(__('Observations'))
                                    ->placeholder(__('Observations_product_application')),

                                Textarea::make('data.product_application.comments')
                                    ->label(__('Comments')),
                            ])
                            ->visible(condition: fn(Get $get) => $get('action_type_id') == 3),

                        Section::make(__('Transplant'))
                            ->schema([
                                FormsGrid::make(2)
                                    ->schema([
                                        Select::make('data.transplant.new_flowerpot')
                                            ->label(__('Nuevo Tipo de Maceta'))
                                            ->options([
                                                'Geotextiles' => 'Geotextiles',
                                                'Plásticas' => 'Plásticas',
                                                'Bolsones' => 'Bolsones',
                                            ])
                                            ->required()
                                            ->live(),

                                        Select::make('data.transplant.new_capacity')
                                            ->label(__('Nueva Capacidad'))
                                    ->options([
                                                3 => '3L',
                                                5 => '5L',
                                                7 => '7L',
                                                10 => '10L',
                                                12 => '12L',
                                                15 => '15L',
                                                20 => '20L',
                                                30 => '30L',
                                                40 => '40L',
                                                50 => '50L',
                                                75 => '75L',
                                            ])
                                            ->required()
                                            ->live(),

                                        Hidden::make('data.transplant.preview_key')
                                            ->default(uniqid()),

                                        Placeholder::make('transplant_preview')
                                            ->label(__('Vista Previa del Transplante'))
                                            ->content(function ($get) {
                                                // Forzar actualización usando preview_key
                                                $previewKey = $get('data.transplant.preview_key');
                                                
                                                $plants = Plant::whereIn('id', $get('plants'))->get();
                                                if ($plants->isEmpty()) {
                                                    return new HtmlString('
                                                        <div class="p-4 rounded-lg border border-gray-200 text-gray-500 text-center">
                                                            Selecciona plantas para ver la vista previa
                                                        </div>
                                                    ');
                                                }

                                                $newFlowerpot = $get('data.transplant.new_flowerpot');
                                                $newCapacity = $get('data.transplant.new_capacity');

                                                $html = "<div class='space-y-4 p-4 rounded-lg border border-gray-200'>";
                                                $html .= "<div class='text-lg font-medium border-b pb-2 mb-3'>Resumen de Cambios</div>";
                                                $html .= "<div class='container-flex justify-to-center'>";
                                                
                                                foreach ($plants as $plant) {
                                                    $html .= "
                                                        <div class='card-tutorial p-3 bg-white dark:bg-gray-800'>
                                                            <div class='font-medium text-lg mb-2'>{$plant->name}</div>
                                                            <div class='text-sm mb-2'>
                                                                <span class='text-gray-600 dark:text-gray-400'>Actual:</span><br/>
                                                                <span class='font-medium'>{$plant->flowerpot} ({$plant->capacity}L)</span>
                                                            </div>";
                                                    
                                                    if ($newFlowerpot && $newCapacity) {
                                                        $html .= "
                                                            <div class='text-sm'>
                                                                <span class='text-gray-600 dark:text-gray-400'>Nueva:</span><br/>
                                                                <span class='font-medium text-primary-600 dark:text-primary-400'>
                                                                    {$newFlowerpot} ({$newCapacity}L)
                                                                </span>
                                                            </div>";
                                                    }
                                                    
                                                    $html .= "</div>";
                                                }
                                                
                                                $html .= "</div></div>";
                                                return new HtmlString($html);
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->visible(fn(Get $get) => $get('action_type_id') == 4),

                        Section::make(__('Observation with photo'))
                            ->schema([
                                FileUpload::make('data.observation.image')
                                    ->image()
                                    ->multiple()
                                    ->maxFiles(5)
                                    ->directory('actions')
                                    ->disk('public')
                                    ->acceptedFileTypes([
                                        'image/jpeg',
                                        'image/png',
                                        'image/gif',
                                        'image/webp'
                                    ])
                                    ->storeFileNamesIn('data.observation.original_filenames')
                                    ->storeFiles(true)
                                    ->downloadable()
                                    ->openable()
                                    ->previewable()
                                    ->reorderable()
                                    ->appendFiles()
                                    ->panelLayout('grid')
                                    ->imagePreviewHeight('150')
                                    ->rules(['nullable', 'array'])
                                    ->maxSize(5120)
                                    ->uploadingMessage('Subiendo imágenes...')
                                    ->loadingIndicatorPosition('left')
                                    ->removeUploadedFileButtonPosition('right')
                                    ->afterStateHydrated(function ($state, $component) {
                                        if (is_null($state)) {
                                            $component->state([]);
                                            return;
                                        }
                                        
                                        if (is_string($state)) {
                                            $component->state([$state]);
                                            return;
                                        }
                                        
                                        if (is_array($state)) {
                                            $component->state(array_values(array_filter($state)));
                                        }
                                    }),

                                Textarea::make('data.observation.comments')
                                    ->label(__('Comments')),
                            ])
                            ->visible(fn(Get $get) => $get('action_type_id') == 5),

                        Section::make(__('Death'))
                            ->label(__('Death'))
                            ->schema([
                                Placeholder::make('')
                                    ->content(__('Death_message'))
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
                    ])
            ])->columns(1)
            ->statePath('data');
    }

    public function afterCreate(): void
    {
        parent::afterCreate();
        
        // Recargar el registro con sus relaciones
        $this->record->load(['plants']);
        
        Log::debug('Después de crear acción', [
            'action_id' => $this->record->id,
            'tiene_plantas' => $this->record->plants()->exists(),
            'plantas_count' => $this->record->plants()->count(),
            'plantas_ids' => $this->record->plants()->pluck('id')
        ]);
    }

    protected function executeActionTrigger(Action $action)
    {
        $actionClass = $action->action_type->action_class;

        if (class_exists($actionClass)) {
            $actionInstance = new $actionClass();

            foreach ($action->plants as $plant) {
                $actionInstance->trigger($plant);
            }

            // Si es una aplicación de producto y tiene recordatorio
            if (
                $action->action_type_id === 3 &&
                isset($action->data['product_application']['reminder_time']) &&
                $action->data['product_application']['reminder_time'] !== 'none'
            ) {

                $delay = match ($action->data['product_application']['reminder_time']) {
                    '5s' => 5,
                    '1m' => 60,
                    '1d' => 86400,
                    default => 0
                };

                if ($delay > 0) {
                    dispatch(new SendDelayedProductNotification(
                        $action->data['product_application']['application_type'],
                        $action->plants()->count(),
                        $action->tenant_id,
                        url(ActionsResource::getUrl('edit', ['record' => $action->id]))  // URL de edición de la acción
                    ))->delay(now()->addSeconds($delay));
                }
            }
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with('plants')->withCount('plants'))
            ->searchPlaceholder(__('actions_searchable_placeholder'))
            ->columns(components: [
                Grid::make([
                    'default' => 1,
                    'sm' => 3,
                    'xl' => 6,
                    '2xl' => 8,
                ])
                    ->schema([

                        // Columna para mostrar la fecha de la acción
                        TextColumn::make('action_date')
                            ->label(__('Fecha'))
                            ->dateTime('d/m/Y')
                            ->description(description: __('Registrado el'), position: 'above')
                            ->grow(false)
                            ->columnSpan(1)
                            ->sortable(), // Permite ordenar por fecha

                        // Columna para mostrar la cantidad de plantas afectadas
                        TextColumn::make('plants_count')
                            ->label(__('Plantas afectadas'))
                            ->grow(false)
                            ->alignCenter()
                            ->description(description: __(key: 'Plantas'), position: 'bellow')
                            ->getStateUsing(fn($record) => $record->plants()->count())
                            ->sortable()
                            ->columnSpan(1),

                        // Columna para mostrar el detalle de la acción
                        TextColumn::make('detalle_accion')
                            ->label(__('Detalle de acción'))
                            ->description(description: __(key: 'Detalle:'), position: 'above')
                            ->getStateUsing(fn($record) => $record->detalle_accion)
                            ->columnSpan([
                                'sm' => 2,
                                'xl' => 3,
                                '2xl' => 4,
                            ])
                            ->sortable(),

                        BadgeColumn::make('action_type')
                            ->formatStateUsing(fn($record) => str_replace('Registrar ', '', $record->action_type->name))
                            ->wrap()
                            ->columnSpan(1)
                            ->alignment('center') // Alineación horizontal
                            ->verticalAlignment('center') // Alineación vertical
                            ->colors(colors: [
                                'tertiary' => static fn($record): bool => $record->action_type->name === 'Registrar Poda',
                                'accent' => static fn($record): bool => $record->action_type->name === 'Registrar Transplante',
                                'primary' => static fn($record): bool => $record->action_type->name === 'Registrar Riego',
                                'dark' => static fn($record): bool => $record->action_type->name === 'Registrar Aplique producto',
                                'warning' => static fn($record): bool => $record->action_type->name === 'Registrar Observación con foto',
                                'danger' => static fn($record): bool => $record->action_type->name === 'Registrar Muerte de la planta',
                                'gray' => static fn($record): bool => $record->action_type->name === 'Registrar Cambio de Estado',
                            ]),


                    ])
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make(name: 'created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                SelectFilter::make('action_type_id')
                    ->label(label: __('Tipo de acción'))
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
                Tables\Actions\ViewAction::make(),
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

    public function create(bool $another = false): void
    {
        try {
            $formState = $this->form->getState();
            
            Log::debug('🚀 Iniciando creación', [
                'form_state' => $formState,
                'plantas' => $formState['plants'] ?? [],
                'indoor_id' => $formState['indoor_id'] ?? null
            ]);

            if (empty($formState['plants'])) {
                throw new \Exception('No se han seleccionado plantas');
            }

            $record = $this->handleRecordCreation($formState);

            Log::debug('✅ Creación completada', [
                'action_id' => $record->id,
                'plantas_count' => $record->plants()->count(),
                'plantas_ids' => $record->plants()->pluck('id')->toArray()
            ]);

            $this->record = $record;

            if ($another) {
                $this->redirect($this->getResource()::getUrl('create'));
            } else {
                $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
            }

        } catch (\Exception $e) {
            Log::error('💥 Error en create', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Asegurarnos de que tenemos todos los datos
        $data['_plants_state'] = $data['_plants_state'] ?? null;
        $data['plants'] = $data['plants'] ?? [];

        return $this->actionService->handleRecordCreation($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->actionService->handleRecordUpdate($record, $data);
    }

    // Asegurar que las plantas no se modifiquen en la edición
    public function afterSave(): void
    {
        if ($this->record->wasRecentlyCreated) {
            return; // Si es creación, no hacer nada adicional
        }

        // Si es edición, restaurar las plantas originales
        $originalPlants = $this->record->getOriginal('plants');
        if ($originalPlants) {
            $this->record->plants()->sync($originalPlants);
        }
    }
}
