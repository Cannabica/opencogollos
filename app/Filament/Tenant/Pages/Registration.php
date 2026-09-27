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
                    Wizard\Step::make('Personal')
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

                            
                            $this->getPasswordFormComponent()
                                ->label('Contraseña del administrador')    
                                ->required()
                                ->password(),
                            $this->getPasswordConfirmationFormComponent()
                                ->label('Confirmar contraseña del administrador')
                                ->required()
                                ->password(),

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
                                ->helperText('Cargarlos separados por coma, a todos se les va a enviar un correo con su clave temporal')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    if ($get('usage_type') === 'equipo_trabajo' && !empty($state)) {
                                        $emails = array_map('trim', explode(',', $state));
                                        $validEmails = [];
                                        $invalidEmails = [];
                                        $duplicateEmails = [];
                                        $strayCharacterEmails = [];
                                        $seenEmails = [];
                                        
                                        foreach ($emails as $email) {
                                            $originalEmail = $email;
                                            $email = trim($email);
                                            
                                            // Check for stray characters (commas, semicolons, spaces within email)
                                            if (preg_match('/^[,\s;]+|[,\s;]+$/', $originalEmail)) {
                                                $strayCharacterEmails[] = $originalEmail;
                                            }
                                            
                                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                                if (in_array($email, $seenEmails)) {
                                                    $duplicateEmails[] = $email;
                                                } else {
                                                    $validEmails[] = $email;
                                                    $seenEmails[] = $email;
                                                }
                                            } else {
                                                $invalidEmails[] = $email;
                                            }
                                        }
                                        
                                        $validationState = [];
                                        if (!empty($invalidEmails)) {
                                            $validationState[] = 'Correos inválidos: ' . implode(', ', $invalidEmails);
                                        }
                                        if (!empty($strayCharacterEmails)) {
                                            $validationState[] = 'Caracteres inválidos: ' . implode(', ', $strayCharacterEmails);
                                        }
                                        if (!empty($duplicateEmails)) {
                                            $validationState[] = 'Duplicados: ' . implode(', ', $duplicateEmails);
                                        }
                                        if (!empty($validEmails)) {
                                            $validationState[] = 'Válidos: ' . count($validEmails);
                                        }
                                        
                                        $set('team_emails_validation', implode(' | ', $validationState));
                                    } else {
                                        $set('team_emails_validation', null);
                                    }
                                })
                                ->rules([
                                    function (Get $get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            if ($get('usage_type') === 'equipo_trabajo') {
                                                $emails = array_map(function($email) {
                                                    $email = trim($email);
                                                    // Remove any stray commas or semicolons at the beginning/end
                                                    $email = trim($email, ',; ');
                                                    return $email;
                                                }, explode(',', $value));
                                                
                                                // Filter out empty strings after trimming
                                                $emails = array_filter($emails);
                                                
                                                $invalidEmails = [];
                                                $duplicateEmails = [];
                                                $strayCharacterEmails = [];
                                                $seenEmails = [];
                                                
                                                foreach ($emails as $email) {
                                                    $originalEmail = $email;
                                                    $email = trim($email);
                                                    
                                                    // Check for stray characters (commas, semicolons, spaces within email)
                                                    if (preg_match('/^[,\s;]+|[,\s;]+$/', $originalEmail)) {
                                                        $strayCharacterEmails[] = $originalEmail;
                                                    }
                                                    
                                                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                                        $invalidEmails[] = $email;
                                                    } else {
                                                        if (in_array($email, $seenEmails)) {
                                                            $duplicateEmails[] = $email;
                                                        }
                                                        $seenEmails[] = $email;
                                                    }
                                                }
                                                
                                                $errors = [];
                                                if (!empty($invalidEmails)) {
                                                    $errors[] = 'inválido: ' . implode(', ', $invalidEmails);
                                                }
                                                if (!empty($strayCharacterEmails)) {
                                                    $errors[] = 'caracteres inválidos: ' . implode(', ', $strayCharacterEmails);
                                                }
                                                if (!empty($duplicateEmails)) {
                                                    $errors[] = 'duplicados: ' . implode(', ', $duplicateEmails);
                                                }
                                                
                                                if (!empty($errors)) {
                                                    $fail(implode(' ', $errors));
                                                }
                                            }
                                        };
                                    },
                                ])
                                ->validationMessages([
                                    'required' => 'Este campo es obligatorio cuando se selecciona Grupo de personas/equipo de trabajo',
                                ])
                        ]),
                    Wizard\Step::make('Cultivos')
                        ->schema([
                            Select::make(name: 'plants_per_cycle')
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
                                    'enfrasco_etiqueto' => 'Enfrasco y etiqueto lo cosechado',
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
                
                // Footer de marca (link de estado + copyright): sin config('platform.*')
                // no se renderiza NADA (instalación neutra).
                ...$this->brandFooterPlaceholder(),
            ]);
    }

    /**
     * Placeholder del footer de marca, o array vacío si no hay nada configurado.
     *
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected function brandFooterPlaceholder(): array
    {
        $statusUrl = config('platform.status_page_url');
        $brandName = config('platform.brand_name');

        if (blank($statusUrl) && blank($brandName)) {
            return [];
        }

        $html = '<div class="mt-8 pt-6 border-t border-gray-200 text-center">';

        if (filled($statusUrl)) {
            $html .= '<p class="text-sm text-gray-600">¿Problemas con el sistema? Verifica el estado en '
                . '<a href="' . e($statusUrl) . '" target="_blank" class="text-cadetblue hover:text-cadetblue-700 font-medium">'
                . e(parse_url($statusUrl, PHP_URL_HOST) ?: $statusUrl)
                . '</a></p>';
        }

        if (filled($brandName)) {
            $html .= '<p class="text-xs text-gray-500 mt-2">&copy; ' . date('Y') . ' ' . e($brandName)
                . '. Todos los derechos reservados.</p>';
        }

        $html .= '</div>';

        return [
            \Filament\Forms\Components\Placeholder::make('brand_footer')
                ->content(new HtmlString($html))
                ->columnSpanFull(),
        ];
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
            ->body('Tu cuenta ha sido creada exitosamente. Debes esperar la activación de tu cuenta por parte de nuestro equipo.')
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
        
        // Create the user first (without registration fields)
        $userData = $data;
        // Remove registration fields that should go to tenant
        unset($userData['user_type'], $userData['usage_type'], $userData['team_emails'], $userData['plants_per_cycle'], $userData['harvest_products']);
        
        $user = parent::handleRegistration($userData);
        
        \Log::debug('User created successfully in handleRegistration:', ['user_id' => $user->id]);
        
        // Create tenant with registration data
        try {
            $tenant = \App\Models\Tenant::create([
                'name' => $user->name . "'s Tenant",
                'email' => $user->email,
                'active' => false, // Tenant desactivado por defecto, requiere activación
                'user_type' => $data['user_type'] ?? null,
                'usage_type' => $data['usage_type'] ?? null,
                'team_emails' => $data['team_emails'] ?? null,
                'plants_per_cycle' => $data['plants_per_cycle'] ?? null,
                'harvest_products' => $data['harvest_products'] ?? null,
                'activated_at' => null,
            ]);
            
            \Log::debug('Tenant created successfully with registration data:', ['tenant_id' => $tenant->id]);
            
            // Assign the tenant to the user
            $user->tenant_id = $tenant->id;
            $user->save();
            
            \Log::debug('Tenant assigned to user:', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id
            ]);
            
            // Store the tenant for use in afterRegister if needed
            $this->tenant = $tenant;

            // Aviso inmediato al superadmin por el bot de administración
            \App\Services\Admin\AdminNotifierService::notifyNewTenant($tenant);
            
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
            
            // Create the team user (without registration fields - they're stored in tenant)
            $teamUser = \App\Models\User::create([
                'name' => explode('@', $email)[0], // Use the part before @ as name
                'email' => $email,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'tenant_id' => $tenantId,
                'force_password_change' => true,
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
