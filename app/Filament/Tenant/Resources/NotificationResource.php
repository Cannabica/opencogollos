<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\NotificationResource\Pages;
use Illuminate\Notifications\DatabaseNotification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificationResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?int $navigationSort = 999;

    protected static ?string $navigationGroup = 'Grupo de trabajo';

    protected static ?string $navigationLabel = 'Centro de notificaciones';

    public static function getPluralLabel(): string
    {
        return 'Notificaciones';
    }

    public static function getLabel(): string
    {
        return 'Notificación';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('notifiable_id', auth()->id())->where('notifiable_type', get_class(auth()->user())))
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-s-envelope') // Use solid for unread
                    ->trueColor('gray')
                    ->falseColor('primary')
                    ->getStateUsing(fn($record) => $record->read_at !== null)
                    ->extraAttributes(['class' => 'w-10']),

                TextColumn::make('data.title')
                    ->label('Notificación')
                    ->weight('bold')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('data->title', 'ilike', "%{$search}%");
                    })
                    ->description(fn($record) => new HtmlString(str($record->data['body'] ?? '')->inlineMarkdown()), position: 'below')
                    ->wrap()
                    ->html()
                    ->getStateUsing(fn($record) => $record->data['title'] ?? ''),

                TextColumn::make('created_at')
                    ->label('Enviada')
                    ->since()
                    ->dateTimeTooltip()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('read_at')
                    ->label('Estado')
                    ->placeholder('Todas')
                    ->trueLabel('Leídas')
                    ->falseLabel('No leídas')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('read_at'),
                        false: fn(Builder $query) => $query->whereNull('read_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('markAsRead')
                    ->label('') // No text for a cleaner look
                    ->tooltip('Marcar como leída')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn($record) => $record->markAsRead())
                    ->visible(fn($record) => $record->read_at === null),

                Tables\Actions\Action::make('markAsUnread')
                    ->label('')
                    ->tooltip('Marcar como no leída')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->action(fn($record) => $record->markAsUnread())
                    ->visible(fn($record) => $record->read_at !== null),

                Tables\Actions\Action::make('viewAction')
                    ->label('')
                    ->tooltip('Revisar entrada')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(function ($record) {
                        $actions = $record->data['actions'] ?? [];
                        $goToAction = collect($actions)->firstWhere('name', 'goToAction');
                        return $goToAction['url'] ?? null;
                    })
                    ->visible(fn($record) => collect($record->data['actions'] ?? [])->contains('name', 'goToAction')),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Borrar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsRead')
                        ->label('Marcar como leídas')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn($records) => $records->each->markAsRead()),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}
