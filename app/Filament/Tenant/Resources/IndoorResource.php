<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\IndoorResource\Pages;
use App\Filament\Tenant\Resources\IndoorResource\RelationManagers;
use App\Models\Indoor;
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

class IndoorResource extends Resource
{
    protected static ?string $model = Indoor::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    public static function getPluralLabel(): string
    {
        return __('Indoors');
    }
    public static function getLabel(): string
    {
        return __('Indoor');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),

                Fieldset::make(__('Dimensions'))
                    ->schema([
                       
                        TextInput::make('large')
                            ->label(__('Large'))
                            ->numeric()
                            ->required(),

                        TextInput::make('width')
                            ->label(__('Width'))
                            ->numeric()
                            ->required(),
                        
                        TextInput::make('height')
                            ->label(__('Height'))
                            ->numeric()
                            ->required(),

                    ]),

                Hidden::make('tenant_id')
                    ->default(fn () => auth()->user()->tenant_id),

                Repeater::make('fans')
                    ->label(__('Fans'))
                    ->schema([

                        TextInput::make('inches')
                            ->label(__('Inches'))
                            ->numeric()
                            ->required(),

                    ]),

                Repeater::make('lamps')
                    ->label(__('Lamps'))
                    ->schema([

                        TextInput::make('power')
                            ->label(__('Power in Watts'))
                            ->numeric()
                            ->required(),

                        Select::make('technology')
                            ->label(__('Technology'))
                            ->options([
                                'led' => 'Led',
                                'sodio' => 'Sodio',
                            ])
                            ->required(),
                            
                        TextInput::make('coverage_area')
                            ->label(__('Coverage area'))
                            ->numeric()
                            ->required(),
                    ])
                    //->collapsible()  
                    ->minItems(1)   
                    ->columns(3),   
 
                Section::make(__('Additional equipment'))
                        ->description('')
                        ->schema([
 
                            Checkbox::make('hygometer')
                                ->label(__('Tengo higometro para medir temperatura y humedad')),

                            Checkbox::make('humidifier')
                                ->label(__('Tengo algún humidificador')),
                                
                                ]),

                Section::make(__('Automatic irrigation equipment'))
                        ->description('')
                        ->schema([
                        
                            TextInput::make('peak_quantity')
                                ->label('Peak Quantity')
                                ->numeric()
                                ->required(),
            
                            TextInput::make('scheduled_time')
                                ->label('Scheduled Time')
                                ->numeric()
                                ->required(),
            
                            TextInput::make('times_a_day')
                                ->label('Times a day')
                                ->numeric()
                                ->required(),
            
                            CheckboxList::make('scheduled_days')
                                ->label('Scheduled days')
                                ->options([
                                    'lunes' => 'Lunes',
                                    'martes' => 'Martes',
                                    'miércoles' => 'Miércoles',
                                    'jueves' => 'Jueves',
                                    'viernes' => 'Viernes',
                                    'sábado' => 'Sábado',
                                    'domingo' => 'Domingo',
                                ])
                                ->columns(3)
                                ->required(),
                                
                        ])
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label(__('Name')),
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
            'index' => Pages\ListIndoors::route('/'),
            'create' => Pages\CreateIndoor::route('/create'),
            'edit' => Pages\EditIndoor::route('/{record}/edit'),
        ];
    }
}
