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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección de Datos Personales
                Forms\Components\Section::make('Información Personal')
                    ->columns(2) // 2 columnas
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombres')
                            ->required()
                            ->dehydrateStateUsing(fn(string $state): string => strtoupper(string: $state)),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellidos')
                            ->required()
                            ->dehydrateStateUsing(fn(string $state): string => strtoupper(string: $state)),
                        Forms\Components\TextInput::make('dni')
                            ->label('DNI / Identificación')
                            ->numeric()
                            ->unique(ignoreRecord: true) // Valida que sea único
                            ->required()
                            ->mask('999999999'),
                        // --- NUEVO CAMPO ---
                        Forms\Components\TextInput::make('address')
                            ->label('Dirección Domiciliaria')
                            ->placeholder('Av. Principal 123, Mz A Lt 1...')
                            ->maxLength(255)
                            ->columnSpanFull() // Para que ocupe todo el ancho si usas columnas
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

                // Sección de Cuenta y Rol
                Forms\Components\Section::make('Datos de Cuenta')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('role')
                            ->label('Rol en la empresa')
                            ->options([
                                'Admin' => 'Admin',
                                'Accionista' => 'Accionista',
                                'Contratado' => 'Contratado',
                                'Secretaria' => 'Secretaria',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => Hash::make($state)) // Encriptar al guardar
                            ->dehydrated(fn($state) => filled($state)) // Solo guardar si se escribe algo
                            ->required(fn(string $context): bool => $context === 'create'), // Obligatorio solo al crear
                    ]),
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
