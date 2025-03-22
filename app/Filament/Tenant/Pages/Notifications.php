<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class Notifications extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $title = 'Notificaciones';
    protected static ?string $slug = 'notifications';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationGroup = 'Sistema';

    protected static string $view = 'filament.tenant.pages.notifications';

    public function table(Table $table): Table
    {
        return $table
            ->query(Auth::user()->unreadNotifications())
            ->columns([
                TextColumn::make('data.title')
                    ->label('Título')
                    ->searchable(),
                TextColumn::make('data.message')
                    ->label('Mensaje')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('mark_as_read')
                    ->label('Marcar como leída')
                    ->icon('heroicon-o-check')
                    ->action(function ($record) {
                        $record->markAsRead();
                        Notification::make()
                            ->title('Notificación marcada como leída')
                            ->success()
                            ->send();
                    })
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }
} 