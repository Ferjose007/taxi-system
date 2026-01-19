<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    //protected static ?string $navigationIcon = 'heroicon-o-users';

    // --- TRADUCCIÓN ---
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SECCIÓN 1: DATOS DEL USUARIO (ACCIONISTA/EMPLEADO) ---
                Forms\Components\Section::make('Información Personal')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombres')
                            ->required()
                            ->dehydrateStateUsing(fn(string $state): string => strtoupper($state)),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellidos')
                            ->required()
                            ->dehydrateStateUsing(fn(string $state): string => strtoupper($state)),
                        Forms\Components\TextInput::make('dni')
                            ->label('DNI / Identificación')
                            ->numeric()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->mask('99999999'),

                        Forms\Components\TextInput::make('address')
                            ->label('Dirección Domiciliaria')
                            ->placeholder('Av. Principal 123...')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('district')->label('Distrito')->required(),
                                Forms\Components\TextInput::make('province')->label('Provincia')->default('Lima'),
                                Forms\Components\TextInput::make('department')->label('Departamento')->default('Lima'),
                            ])->columns(3)->columnSpanFull(),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel(),
                    ]),

                // --- SECCIÓN 2: CUENTA Y ROL ---
                Forms\Components\Section::make('Datos de Cuenta')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required(), // Email suele ser requerido para el login

                        Forms\Components\Select::make('role')
                            ->label('Rol en la empresa')
                            ->options([
                                'Admin' => 'Admin',
                                'Accionista' => 'Accionista',
                                'Contratado' => 'Contratado',
                                'Secretaria' => 'Secretaria',
                            ])
                            ->required()
                            ->live(), // ¡IMPORTANTE! Permite mostrar/ocultar la sección de abajo

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create'),
                    ]),

                // --- SECCIÓN 3: DATOS DEL CHOFER (LÓGICA MEJORADA) ---
                Forms\Components\Section::make('Datos del Chofer Asignado')
                    ->description('Active la opción si el accionista NO conducirá su propia unidad.')
                    ->schema([
                        // 1. EL INTERRUPTOR (No se guarda en BD, solo controla el formulario)
                        Forms\Components\Toggle::make('has_driver')
                            ->label('¿Este Accionista tiene un chofer contratado?')
                            ->onColor('success')
                            ->offColor('gray')
                            ->default(false)
                            ->live() // ¡Importante! Hace que el formulario reaccione al clic
                            ->dehydrated(false) // Esto evita que intente guardar "has_driver" en la base de datos
                            ->afterStateHydrated(function ($component, $state, $record) {
                                // Si estamos editando y el usuario YA tiene un nombre de chofer guardado,
                                // encendemos el interruptor automáticamente.
                                if ($record && !empty($record->driver_name)) {
                                    $component->state(true);
                                }
                            }),

                        // 2. GRUPO DE CAMPOS (Solo visibles si el interruptor está ON)
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('driver_name')
                                    ->label('Nombre Completo del Chofer')
                                    ->required() // Obligatorio SOLO si se muestra
                                    ->dehydrateStateUsing(fn($state) => strtoupper($state)),

                                Forms\Components\TextInput::make('driver_dni')
                                    ->label('DNI del Chofer')
                                    ->numeric()
                                    ->mask('99999999')
                                    ->required(), // Obligatorio SOLO si se muestra

                                Forms\Components\TextInput::make('driver_address')
                                    ->label('Dirección del Chofer')
                                    ->maxLength(255)
                                    ->required(),

                                Forms\Components\TextInput::make('driver_phone')
                                    ->label('Celular del Chofer')
                                    ->tel(),
                            ])
                            ->columns(2)
                            ->visible(fn(Forms\Get $get) => $get('has_driver')), // La magia de la visibilidad
                    ])
                    // Todo esto solo aparece si el rol es Accionista
                    ->visible(fn(Forms\Get $get) => $get('role') === 'Accionista'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Apellido')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rol')
                    ->badge() // Lo muestra como una etiqueta de color
                    ->color(fn(string $state): string => match ($state) {
                        'Admin' => 'danger',
                        'Accionista' => 'success',
                        'Contratado' => 'info',
                        'Secretaria' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono'),
                Tables\Columns\TextColumn::make('email'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
