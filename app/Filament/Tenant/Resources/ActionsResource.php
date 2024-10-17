<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\ActionsResource\Pages;
use App\Filament\Tenant\Resources\ActionsResource\RelationManagers;
use App\Models\Action;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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
use Carbon\Carbon;
use Filament\Forms\Get;

class ActionsResource extends Resource
{
    protected static ?string $model = Action::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getPluralLabel(): string
    {
        return __('Action');
    }
    public static function getLabel(): string
    {
        return __('Actions');
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
                    ->required(),

                    Section::make(__('Irrigation'))
                        ->schema([
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
                            CheckboxList::make('plants')
                                ->label(__('Plants'))
                                ->relationship('plants', 'name')
                                ->columns(2)
                                ->required(),

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
                           CheckboxList::make('plant_id')
                               ->label(__('Plants'))
                               ->relationship('plants', 'name')
                               ->columns(2)
                               ->required(),

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
                           CheckboxList::make('plant_id')
                               ->label(__('Plants'))
                               ->relationship('plants', 'name')
                               ->columns(2)
                               ->required(),

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

                            Textarea::make('data.product_application.comments')
                                ->label(__('Comments')),
                        ])
                        ->visible(fn(Get $get) => $get('action_type_id') == 3),

                    Section::make(__('Transplant'))
                        ->schema([
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
                           CheckboxList::make('plant_id')
                               ->label(__('Plants'))
                               ->relationship('plants', 'name')
                               ->columns(2)
                               ->required(),

                            Select::make('data.transplant.new_pot_size')
                                ->label(__('Tamaño de la nueva maceta'))
                                ->options([
                                    'N10',
                                    'N12',
                                    'N14',
                                    '3L',
                                    '5L',
                                    '7L',
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

                    Section::make(__('Observation with photo'))
                        ->schema([
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
                           CheckboxList::make('plant_id')
                               ->label(__('Plants'))
                               ->relationship('plants', 'name')
                               ->columns(2)
                               ->required(),

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
                       CheckboxList::make('plant_id')
                           ->label(__('Plants'))
                           ->relationship('plants', 'name')
                           ->columns(2)
                           ->required(),
                        ])
                        ->visible(fn(Get $get) => $get('action_type_id') == 6),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('action_date')
                ->label(__('Action Date'))
                ->dateTime('d/m/Y') // formato de la fecha
                ->sortable(), // Permite ordenar por esta columna

                Tables\Columns\TextColumn::make('action_type.name')
                    ->label(__('Action Type'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('indoor.name')
                    ->label(__('Indoor Name'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('plants') // Cambiar 'plants.name' a 'plants'
                    ->label(__('Plant Names'))
                    ->sortable() // Sigue siendo sortable
                    ->getStateUsing(fn ($record) => $record->plants->pluck('name')->join(', '))
            ])
            ->filters([
                //
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
