<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    //protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Documentos';

    protected static ?string $navigationLabel = 'Contratos';

    protected static ?int $navigationSort = 1;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles del Contrato')
                    ->columns(2)
                    ->schema([
                        // Seleccionar Conductor
                        Forms\Components\Select::make('user_id')
                            ->label('Conductor / Contratado')
                            ->relationship('user', 'name') // Busca por nombre en la tabla users
                            // Opcional: Filtrar solo usuarios con rol 'driver'
                            // ->options(User::where('role', 'driver')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        // Seleccionar Vehículo
                        Forms\Components\Select::make('vehicle_id')
                            ->label('Vehículo Asignado')
                            ->relationship('vehicle', 'plate') // Muestra la placa
                            ->searchable()
                            ->preload()
                            ->required(),

                        // Fechas
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha de Inicio')
                            ->required(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha de Fin')
                            ->nullable(), // Puede ser indefinido

                        // Tipo de contrato
                        Forms\Components\Select::make('type')
                            ->label('Modalidad')
                            ->options([
                                'alquiler' => 'Alquiler (Puerta Libre)',
                                'porcentaje' => 'Porcentaje (Comisión)',
                                'nomina' => 'Nómina (Sueldo Fijo)',
                            ])
                            ->required(),

                        // Estado
                        Forms\Components\Toggle::make('status')
                            ->label('Contrato Vigente')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Conductor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('vehicle.plate')
                    ->label('Vehículo')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Modalidad')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'alquiler' => 'info',
                        'porcentaje' => 'warning',
                        'nomina' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date(),

                Tables\Columns\IconColumn::make('status')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                // Filtro para ver solo contratos activos
                Tables\Filters\Filter::make('active')
                    ->query(fn($query) => $query->where('status', true))
                    ->label('Solo Vigentes'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Aquí agregaremos el botón de PDF en el siguiente paso
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
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }
}
