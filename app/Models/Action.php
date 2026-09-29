<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;

class Action extends Model
{
    use HasFactory;
    protected $fillable = [
        'action_type_id',
        'action_date',
        'indoor_id',
        'tenant_id',
        'data'
    ];

    protected $casts = [
        'data' => 'array',
        'action_date' => 'datetime',
    ];

    public function indoor()
    {
        return $this->belongsTo(Indoor::class);
    }

    public function plants(): BelongsToMany
    {
        return $this->belongsToMany(Plant::class, 'action_plant')
            ->withTimestamps();
    }

    // Atributo dinámico para contar las plantas
    public function getPlantsCountAttribute()
    {
        return $this->plants()->count();
    }

    public function action_type()
    {
        return $this->belongsTo(ActionType::class);
    }

    public function getDetalleAccionAttribute()
    {
        $data = $this->data;

        if (!$data) {
            return __('No details available');
        }

        switch ($this->action_type_id) {
            case 1: // Irrigation
                $irrigationType = $data['irrigation']['irrigation_type'] ?? null;
                if ($irrigationType === 'liters') {
                    return __('Irrigation:') . ' ' . ($data['irrigation']['liters'] ?? 0) . ' liters';
                } elseif ($irrigationType === 'timer') {
                    return __('Irrigation:') . ' ' . ($data['irrigation']['timer'] ?? 0) . ' min timer';
                }
                return __('Irrigation: No details');
            
            case 2: // Pruning
                $pruningTypes = $data['pruning']['pruning_type'] ?? [];
                return __('Pruning Types:') . ' ' . implode(', ', $pruningTypes);
            
            case 3: // Product Application
                return __('Application Type:') . ' ' . ($data['product_application']['application_type'] ?? __('No details'));
            
            case 4: // Transplant
                return __('New Pot Size:') . ' ' . ($data['transplant']['new_pot_size'] ?? __('No details'));
            
            case 5: // Observation with photo
                return __('Observation:') . ' ' . ($data['observation']['comments'] ?? __('No comments'));
            
            case 6: // Death
                return __('Death action');
            
            case 7: // Change of State
                return __('Estado:') . ' ' . $data['change_state']['state'];
            
            default:
                return __('No details available');
        }
    }

    protected static function booted()
    {
        // `actions` sí tiene `tenant_id`: variante normal del scope.
        static::addGlobalScope(new TenantScope);

        static::created(function ($action) {
            Log::debug('📢 Acción creada - Evento modelo', [
                'action_id' => $action->id,
                'tipo' => $action->action_type_id,
                'plantas_asociadas' => $action->plants()->count()
            ]);
        });

        static::saved(function ($action) {
            Log::debug('📢 Acción guardada - Evento modelo', [
                'action_id' => $action->id,
                'plantas_asociadas' => $action->plants()->count()
            ]);
        });
    }
}
