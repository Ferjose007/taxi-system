<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    //protected static ?string $navigationIcon = 'heroicon-o-truck';

    // --- TRADUCCIÓN ---
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Vehículos';
    protected static ?string $modelLabel = 'Vehículo';
    protected static ?string $pluralModelLabel = 'Vehículos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Vehículo')
                    ->description('Ingrese la información técnica y legal del auto.')
                    ->columns(2)
                    ->schema([
                        // 1. Dueño (Validamos que exista)
                        Forms\Components\Select::make('user_id')
                            ->label('Conductor')
                            ->relationship('owner', 'name')
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} {$record->last_name} - DNI: {$record->dni}")
                            ->searchable(['name', 'last_name'])
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => 'Debe asignar un dueño al vehículo.',
                            ]),

                        // 2. Placa (Con validación fuerte)
                        Forms\Components\TextInput::make('plate')
                            ->label('Placa')
                            ->required()
                            ->unique(ignoreRecord: true) // Evita duplicados al crear y editar
                            ->mask('***-***') // Formato visual: 3 letras - 3 números (Ajustable según país)
                            ->placeholder('A1C-58X')
                            ->dehydrateStateUsing(fn(string $state): string => strtoupper($state)) // Guardar siempre en MAYÚSCULAS
                            ->regex('/^[A-Z0-9]+-[A-Z0-9]+$/') // Regex para validar formato interno
                            ->validationMessages([
                                'unique' => 'Esta placa ya se encuentra registrada en el sistema.',
                                'regex' => 'El formato debe ser 3 letras y 3 números (ej: ABC-123).',
                            ]),

                        // 3. Marca
                        Forms\Components\TextInput::make('brand')
                            ->label('Marca')
                            ->placeholder('Ej: Toyota')
                            ->required()
                            ->maxLength(20),

                        // 4. Modelo
                        Forms\Components\TextInput::make('model')
                            ->label('Modelo')
                            ->placeholder('Ej: Yaris')
                            ->required()
                            ->maxLength(20),
                        
                        // --- NUEVO CAMPO COLOR ---
                        Forms\Components\TextInput::make('color')
                            ->label('Color')
                            ->placeholder('Ej: Rojo, Plata, Negro...')
                            ->required() // Lo hacemos obligatorio para tener datos completos
                            ->maxLength(30),
                        // -------------------------

                        // 5. Año (Validación lógica)
                        Forms\Components\TextInput::make('year')
                            ->label('Año de Fabricación')
                            ->numeric()
                            ->minValue(2000) // No aceptamos autos muy viejos para taxi
                            ->maxValue(date('Y') + 1) // Máximo hasta el año siguiente
                            ->required()
                            ->validationMessages([
                                'minValue' => 'El vehículo es muy antiguo (mínimo año 2000).',
                                'maxValue' => 'El año no puede ser superior al año próximo.',
                            ]),

                        // 6. Estado
                        Forms\Components\Select::make('status')
                            ->label('Estado Actual')
                            ->options([
                                'active' => 'Activo / Trabajando',
                                'maintenance' => 'En Taller / Mantenimiento',
                                'inactive' => 'De Baja / Inactivo',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false), // Hace el select más bonito visualmente
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plate')
                    ->label('Placa')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable(),

                Tables\Columns\TextColumn::make('model')
                    ->label('Modelo'),
                
                Tables\Columns\TextColumn::make('color') // <--- NUEVO
                ->label('Color')
                ->searchable(),

                // Mostrar nombre del dueño
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Conductor')
                    ->formatStateUsing(fn($record) => "{$record->owner->name} {$record->owner->last_name}")
                    ->searchable(['name', 'last_name'])
                    ->description(fn($record) => "DNI: {$record->owner->dni}")
                    ->sortable(),

                // Estado con colores (Badges)
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',      // Verde
                        'maintenance' => 'warning', // Naranja
                        'inactive' => 'danger',     // Rojo
                        default => 'gray',
                    }),
            ])
            ->filters([
                // Filtro rápido para ver solo los que están en Taller
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Activo',
                        'maintenance' => 'En Mantenimiento',
                        'inactive' => 'Inactivo',
                    ])
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
