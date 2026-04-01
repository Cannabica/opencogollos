<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Job;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class UpcomingNotificationsWidget extends BaseWidget
{
    protected static ?string $heading = 'Notificaciones Programadas';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $tenantId = auth()->user()->tenant_id;

        return $table
            ->query(
                Job::query()
                    ->where('payload', 'like', '%SendDelayedProductNotification%')
                    ->where('payload', 'like', '%tenantId%i:' . $tenantId . ';%')
            )
            ->columns([
                TextColumn::make('payload.displayName')
                    ->label('Evento')
                    ->icon('heroicon-o-clock')
                    ->color('primary')
                    ->weight('bold')
                    ->getStateUsing(function ($record) {
                        $payload = $record->payload;
                        $command = $payload['data']['command'] ?? '';

                        // Intentar extraer el tipo de aplicación del comando serializado
                        if (preg_match('/"applicationType";s:\d+:"([^"]+)"/', $command, $matches)) {
                            $type = trim(strtolower($matches[1]));

                            if ($type === 'other') {
                                return 'Aplicación de producto programada';
                            }

                            $translatedType = match ($type) {
                                'flora' => 'Flora',
                                'plague' => 'Control de Plagas',
                                'plántula', 'plantula' => 'Etapa de Plántula',
                                default => ucfirst($type),
                            };

                            return 'Aplicación programada: ' . $translatedType;
                        }

                        return 'Recordatorio programado';
                    })
                    ->description(function ($record) {
                        $payload = $record->payload;
                        $command = $payload['data']['command'] ?? '';
                        $description = "";

                        // Extraer cantidad de plantas y actionId para el nombre
                        $count = null;
                        if (preg_match('/plantsCount";i:(\d+);/', $command, $matches)) {
                            $count = (int) $matches[1];
                        }

                        if (preg_match('/actionId";i:(\d+);/', $command, $matches)) {
                            $actionId = $matches[1];
                            $action = \App\Models\Action::with(['indoor', 'plants'])->find($actionId);

                            $type = null;
                            if (preg_match('/"applicationType";s:\d+:"([^"]+)"/', $command, $typeMatches)) {
                                $type = trim(strtolower($typeMatches[1]));
                            }

                            $applicationName = match ($type) {
                                'flora' => 'Flora',
                                'plague' => 'Control de Plagas',
                                'plántula', 'plantula' => 'Etapa de Plántula',
                                default => 'un producto',
                            };

                            if ($count === 1 && $action?->plants->count() > 0) {
                                $description .= "Pendiente para aplicar **{$applicationName}** a la planta **" . $action->plants->first()->name . "**";
                            } elseif ($count !== null) {
                                $description .= "Pendiente para aplicar **{$applicationName}** a **" . $count . "** plantas";
                            }

                            if ($action?->indoor) {
                                $description .= ($description ? " en el indoor " : "Para el indoor ") . "**" . $action->indoor->name . "**";
                            }
                        } elseif ($count !== null) {
                            $description .= "Pendiente para **" . $count . "** plantas";
                        }

                        return new HtmlString(str($description)->inlineMarkdown());
                    })
                    ->html(),

                TextColumn::make('available_at')
                    ->label('Programado')
                    ->getStateUsing(fn($record) => Carbon::createFromTimestamp($record->available_at))
                    ->since()
                    ->dateTimeTooltip()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Cancelar recordatorio')
                    ->icon('heroicon-o-trash')
                    ->modalHeading('¿Cancelar recordatorio?')
                    ->modalDescription('Se eliminará la notificación programada.'),
            ])
            ->emptyStateHeading('No hay notificaciones programadas');
    }
}
