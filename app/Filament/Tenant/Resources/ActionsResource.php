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
use Filament\Tables\Columns\ViewColumn;
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
use Filament\Forms\Components\Wizard;
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
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'Plantas';

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
                Wizard::make([
                    \Filament\Forms\Components\Wizard\Step::make(__('Datos básicos'))
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
                    \Filament\Forms\Components\Wizard\Step::make(__('Tipo de acción'))
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
                                })
                                ->disabled(fn($record) => $record !== null),
                        ])
                    ,
                    \Filament\Forms\Components\Wizard\Step::make(__('Datos de la acción'))
                        ->description(__('action_data_description'))
                        ->schema([
                        Placeholder::make('Disclaimer')
                            ->content(function (Get $get) {
                                if ($get('action_type_id') != null) {
                                    $actionClass = new (ActionType::find($get('action_type_id'))->action_class);
                                    return $actionClass->disclaimer() ? new HtmlString(
                                        '<div class="rounded-lg border border-warning-500/40 bg-warning-500/10 px-4 py-3 text-sm text-warning-700 dark:text-warning-300">' .
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
                                    ->options(function () {
                                        $options = [
                                            '1d' => 'En 1 día',
                                            '7d' => 'En 7 días',
                                            '14d' => 'En 14 días'
                                        ];

                                        if (config('app.debug')) {
                                            $options['5s'] = 'En 5 segundos (DEBUG)';
                                        }

                                        return $options;
                                    })
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
                                    ->imagePreviewHeight('300')
                                    // OJO: NO usar regla 'array' acá. Con UN archivo existente en edición,
                                    // Filament deshidrata el FileUpload como string (no array) y la regla
                                    // 'array' rompe el guardado con validation.array. El resto del código
                                    // (detalle/listado/handleRecordUpdate) ya normaliza string o array.
                                    ->rules(['nullable'])
                                    ->maxSize(10240)
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
                ])
                // En edición el dato a corregir vive en el paso 3: arrancar ahí directo
                // (los pasos 1-2 quedan accesibles con Anterior para revisar indoor/plantas/tipo).
                ->startOnStep(fn($record) => $record !== null ? 3 : 1)
            ])->columns(1)
            ->statePath('data');
    }



    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->with('plants')
                    ->withCount('plants')
                    ->whereHas('indoor', function ($query) {
                        $query->where('tenant_id', auth()->user()->tenant_id);
                    })
            )
            ->searchPlaceholder(__('actions_searchable_placeholder'))
            ->columns([
                TextColumn::make('action_date')
                    ->label(__('Fecha'))
                    ->dateTime('d/m/Y')
                    ->description(__('Registrado el'))
                    ->sortable()
                    ->searchable()
                    ->size('sm'),

                TextColumn::make('indoor.name')
                    ->label(__('Indoor'))
                    ->searchable()
                    ->wrap()
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('plants_count')
                    ->label(__('Plantas'))
                    ->badge()
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        $count = $record->plants()->count();
                        return $count . ' ' . ($count === 1 ? __('Planta') : __('Plantas'));
                    })
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('detalle_accion')
                    ->label(__('Detalle'))
                    ->formatStateUsing(function ($state, $record) {

                        $data = is_string($record->data) ? json_decode($record->data, true) : $record->data;

                        if ($record->action_type->id == 6) {
                            return "La planta ha muerto";
                        }

                        if (isset($data['irrigation'])) {
                            $irrigation = $data['irrigation'];
                            if ($irrigation['irrigation_type'] === 'timer') {
                                return "Riego por {$irrigation['timer']} minutos ";
                            } else {
                                // tolera data parcial (riego sin 'liters' no rompe el listado)
                                return "Riego " . ($irrigation['liters'] ?? '?') . " lts ";
                            }
                        } elseif (isset($data['pruning'])) {
                            $types = $data['pruning']['pruning_type'];
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
                            return "{$text}";
                        } elseif (isset($data['transplant'])) {
                            // dd($data);
                            return "Transplante a {$data['transplant']['new_flowerpot']} {$data['transplant']['new_capacity']}L";
                        } elseif (isset($data['change_state'])) {
                            return "Cambio de estado a {$data['change_state']['state']}";
                        } elseif (isset($data['observation'])) {
                            $imageData = $data['observation']['image'] ?? null;
                            $images = $imageData === null ? [] : (is_array($imageData) ? $imageData : [$imageData]);
                            $imageCount = count($images);

                            $messageText = '';
                            $messageText .= "\n" . (strlen($data['observation']['comments']) > 30
                                ? substr($data['observation']['comments'], 0, 30) . '...'
                                : $data['observation']['comments']);
                            if ($imageCount > 0) {
                                if ($imageCount > 1) {
                                    $messageText .= " ({$imageCount} fotos)";
                                } else {
                                    $messageText .= " (1 foto)";
                                }
                            }

                            return $messageText;

                        } elseif (isset($data['product_application'])) {
                            $appType = $data['product_application']['application_type'];
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

                ViewColumn::make('fotos')
                    ->label(__('Fotos'))
                    ->view('filament.tables.columns.action-photos')
                    ->placeholder('')
                    ->toggleable()
                    ->alignStart(),

                BadgeColumn::make('action_type')
                    ->label(__('Tipo'))
                    ->formatStateUsing(callback: fn($record) => str_replace('Registrar ', '', $record->action_type->name))
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
                Tables\Actions\EditAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])->iconButton(),
            ])
            ->striped()
            ->paginated([
                'default' => 10,
                'sm' => 10,
                'md' => 15,
                'lg' => 20,
            ])
            ->recordClasses(fn($record) => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800')
            ->recordUrl(fn($record) => route('filament.tenant.resources.actions.edit', ['record' => $record]))
            ->defaultPaginationPageOption(10);
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

    public static function executeActionTrigger($record): void
    {
        $actionClass = $record->action_type->action_class ?? null;

        if ($actionClass && class_exists($actionClass)) {
            $actionTypeInstance = new $actionClass();
            $plants = $record->plants()->get();
            foreach ($plants as $plant) {
                $actionTypeInstance->trigger($plant, $record->data);
            }
        }

        // Programación de notificaciones retardadas (Tipo 3: Aplicación de producto)
        if ($record->action_type_id == 3) {
            $reminderTime = $record->data['product_application']['reminder_time'] ?? 'none';

            if ($reminderTime !== 'none') {
                $delay = match($reminderTime) {
                    '5s' => now()->addSeconds(5),
                    '1d' => now()->addDay(),
                    '7d' => now()->addDays(7),
                    '14d' => now()->addDays(14),
                    default => null,
                };

                if ($delay) {
                    \App\Jobs\SendDelayedProductNotification::dispatch(
                        $record->data['product_application']['application_type'] ?? 'other',
                        $record->plants()->count(),
                        $record->tenant_id,
                        $record->id
                    )->delay($delay);
                }
            }
        }
    }
}
