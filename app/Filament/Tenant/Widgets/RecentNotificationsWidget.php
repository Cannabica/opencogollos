<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Notifications\DatabaseNotification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class RecentNotificationsWidget extends BaseWidget
{
    protected static ?string $heading = 'Historial de Notificaciones';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DatabaseNotification::query()
                    ->where('notifiable_id', auth()->id())
                    ->where('notifiable_type', get_class(auth()->user()))
            )
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('gray')
                    ->falseColor('primary')
                    ->getStateUsing(fn($record) => $record->read_at !== null),

                TextColumn::make('data.title')
                    ->label('Título')
                    ->wrap(),

                TextColumn::make('data.body')
                    ->label('Mensaje')
                    ->wrap()
                    ->html()
                    ->getStateUsing(fn($record) => $record->data['body'] ?? $record->data['message'] ?? ''),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('markAsRead')
                    ->label('Marcar leída')
                    ->icon('heroicon-o-check')
                    ->action(fn($record) => $record->markAsRead())
                    ->visible(fn($record) => $record->read_at === null),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
