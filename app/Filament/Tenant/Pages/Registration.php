<?php

namespace App\Filament\Tenant\Pages;

use Filament\Forms\Form;
use Filament\Pages\Auth\Register;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Fieldset;
use Illuminate\Validation\Rules\Required;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Model;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class Registration extends Register
{
    protected ?string $maxWidth = '2xl';
    
    protected $tenant = null;

    public function form(Form $form): Form
    {
        \Log::debug('Registration form schema being built');
        
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Información Personal')
                        ->schema([
                            $this->getNameFormComponent()
                                ->required()
                                ->autofocus(),
                            $this->getEmailFormComponent()
                                ->required()
                                ->email(),
                            Select::make('user_type')
                                ->label('Tipo de usuario')
                                ->options([
                                    'cultivador_hogareño' => 'Cultivador Hogareño / Cultivador solidario',
                                    'growshop' => 'Growshop - Club de cultivo / Comercio',
                                    'cooperativa' => 'Cooperativa',
                                    'otro' => 'Otro',
                                ])
                                ->required()
                                ->native(false),
                        ]),
                    Wizard\Step::make('Uso')
                        ->schema([
                            Select::make('usage_type')
                                ->label('¿Quién usará la plataforma?')
                                ->options([
                                    'individual' => 'Quien completa el formulario',
                                    'equipo_trabajo' => 'Grupo de personas/equipo de trabajo',
                                ])
                                ->required()
                                ->native(false)
                                ->live(),
                            Textarea::make('team_emails')
                                ->label('Correos electrónicos del equipo')
                                ->placeholder('email1@ejemplo.com, email2@ejemplo.com, email3@ejemplo.com')
                                ->rows(3)
                                ->required(fn (Get $get): bool => $get('usage_type') === 'equipo_trabajo')
                                ->hidden(fn (Get $get): bool => $get('usage_type') !== 'equipo_trabajo')
                                ->helperText('Ingrese los correos electrónicos separados por coma')
                                ->rules([
                                    function (Get $get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            if ($get('usage_type') === 'equipo_trabajo') {
                                                $emails = array_map('trim', explode(',', $value));
                                                $invalidEmails = [];
                                                
                                                foreach ($emails as $email) {
                                                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                                        $invalidEmails[] = $email;
                                                    }
                                                }
                                                
                                                if (!empty($invalidEmails)) {
                                                    $fail('Los siguientes correos electrónicos no son válidos: ' . implode(', ', $invalidEmails));
                                                }
                                            }
                                        };
                                    },
                                ])
                                ->validationMessages([
                                    'required' => 'Este campo es obligatorio cuando se selecciona Grupo de personas/equipo de trabajo',
                                ]),
                            $this->getPasswordFormComponent()
                                ->required()
                                ->password(),
                            $this->getPasswordConfirmationFormComponent()
                                ->required()
                                ->password(),
                        ]),
                    Wizard\Step::make('Cultivos')
                        ->schema([
                            Select::make('plants_per_cycle')
                                ->label('¿Qué cantidad de plantas cultivas por ciclo?')
                                ->options([
                                    1 => '1-5 plantas',
                                    2 => '6-10 plantas',
                                    3 => '11-20 plantas',
                                    4 => '21-50 plantas',
                                    5 => 'Más de 50 plantas',
                                ])
                                ->required()
                                ->native(false)
                                ->helperText('Selecciona el rango que mejor describa tu escala de cultivo'),
                            CheckboxList::make('harvest_products')
                                ->label('Habitualmente con la cosecha:')
                                ->options([
                                    'enfrasco_etiqueto' => 'Enfrascó y etiquetó lo cosechado',
                                    'aceites' => 'Produzco aceites',
                                    'cremas' => 'Produzco cremas',
                                    'edibles' => 'Produzco edibles o algún tipo de alimento',
                                    'otro' => 'Otro',
                                ])
                                ->required()
                                ->columns(1),
                        ]),
                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
                    <x-filament::button
                        type="submit"
                        size="sm"
                        wire:submit="register"
                    >
                        Registrarse
                    </x-filament::button>
                    BLADE))),
            ]);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function afterRegister(): void
    {
        \Log::debug('afterRegister called with data:', $this->data);
        
        // Get the user from the form model (should be available since we're in the same request)
        $user = $this->form->getModel();
        
        // Debug what we're getting from getModel()
        \Log::debug('getModel() returned:', ['type' => gettype($user), 'value' => $user]);
        
        // If we get a string (class name), try to find the user by email from form data
        if (is_string($user) && class_exists($user)) {
            \Log::warning('getModel() returned class name, trying to find user by email');
            $user = \App\Models\User::where('email', $this->data['email'])->first();
        }
        
        // If we still don't have a valid User model, try to get the authenticated user
        if (!$user || !is_object($user) || (is_object($user) && !$user->exists)) {
            \Log::warning('User not available from form model, trying authenticated user');
            $user = Auth::user();
            
            if (!$user) {
                \Log::error('User not available from either form model or authentication in afterRegister');
                return; // Don't throw exception, just log and return
            }
        }
        
        \Log::debug('User in afterRegister:', ['user_id' => $user->id, 'email' => $user->email, 'tenant_id' => $user->tenant_id]);
        
        // Process team emails if team option selected (excluding the main user's email)
        if ($this->data['usage_type'] === 'equipo_trabajo' && !empty($this->data['team_emails'])) {
            $emails = array_map('trim', explode(',', $this->data['team_emails']));
            $validEmails = array_filter($emails, function($email) use ($user) {
                // Filter out the main user's email and validate format
                return $email !== $user->email && filter_var($email, FILTER_VALIDATE_EMAIL);
            });
            
            \Log::debug('Team emails processed (excluding main user):', [
                'original' => $this->data['team_emails'],
                'parsed' => $emails,
                'valid' => $validEmails,
                'main_user_email' => $user->email
            ]);
            
            // Create team users (only additional users, not the main one)
            foreach ($validEmails as $email) {
                $this->createTeamUser($email, $user->tenant_id);
            }
            
            \Log::debug('Team users created count:', ['count' => count($validEmails)]);
        }
        
        // Send confirmation email to the main user with their account details
        if ($user->tenant_id) {
            $tenant = \App\Models\Tenant::find($user->tenant_id);
            if ($tenant) {
                $user->notify(new \App\Notifications\RegistrationConfirmationNotification($tenant->name));
                
                \Log::debug('Registration confirmation sent to main user:', [
                    'email' => $user->email,
                    'tenant_name' => $tenant->name
                ]);
            }
        }

        // Show success notification after registration
        Notification::make()
            ->title('Registro exitoso')
            ->body('Tu cuenta ha sido creada exitosamente. Debes esperar la activación de tu cuenta por parte de nuestro equipo de superadministradores.')
            ->success()
            ->send();
    }


    protected function handleRegistration(array $data): Model
    {
        \Log::debug('Custom handleRegistration called with data:', $data);
        
        // Convert harvest_products array to JSON before saving
        if (isset($data['harvest_products']) && is_array($data['harvest_products'])) {
            $data['harvest_products'] = json_encode($data['harvest_products']);
        }
        
        // Create the user first
        $user = parent::handleRegistration($data);
        
        \Log::debug('User created successfully in handleRegistration:', ['user_id' => $user->id]);
        
        // Create tenant and assign it to the user
        try {
            $tenant = \App\Models\Tenant::create([
                'name' => $user->name . "'s Tenant",
                'email' => $user->email,
                'active' => false, // Tenant desactivado por defecto, requiere activación
            ]);
            
            \Log::debug('Tenant created successfully:', ['tenant_id' => $tenant->id]);
            
            // Assign the tenant to the user
            $user->tenant_id = $tenant->id;
            $user->save();
            
            \Log::debug('Tenant assigned to user:', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id
            ]);
            
            // Store the tenant for use in afterRegister if needed
            $this->tenant = $tenant;
            
        } catch (\Exception $e) {
            \Log::error('Failed to create tenant in handleRegistration: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
            throw $e;
        }
        
        return $user;
    }
    
    protected function createTeamUser(string $email, int $tenantId): void
    {
        try {
            // Generate a random password
            $password = \Illuminate\Support\Str::random(12);
            
            // Create the team user
            $teamUser = \App\Models\User::create([
                'name' => explode('@', $email)[0], // Use the part before @ as name
                'email' => $email,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'tenant_id' => $tenantId,
                'force_password_change' => true,
                'user_type' => $this->data['user_type'] ?? 'cultivador_hogareño',
                'usage_type' => 'individual',
                'plants_per_cycle' => $this->data['plants_per_cycle'] ?? 1,
                'harvest_products' => $this->data['harvest_products'] ?? [],
            ]);
            
            \Log::debug('Team user created:', [
                'email' => $email,
                'user_id' => $teamUser->id,
                'tenant_id' => $tenantId
            ]);
            
            // Send activation notification
            $tenant = \App\Models\Tenant::find($tenantId);
            if ($tenant) {
                $teamUser->notify(new \App\Notifications\TeamUserActivationNotification(
                    $password,
                    $tenant->name
                ));
            } else {
                \Log::error('Failed to send team activation: Tenant not found', [
                    'tenant_id' => $tenantId,
                    'team_user_email' => $email
                ]);
            }
            
            \Log::debug('Activation notification sent to team user:', [
                'email' => $email,
                'user_id' => $teamUser->id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to create team user: ' . $e->getMessage(), [
                'email' => $email,
                'tenant_id' => $tenantId
            ]);
        }
    }

}