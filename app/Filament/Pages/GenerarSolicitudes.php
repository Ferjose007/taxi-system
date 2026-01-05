<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Models\User;
use App\Models\Vehicle;

class GenerarSolicitudes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    // AQUÍ LA MAGIA: Lo ponemos en el mismo grupo que Contratos
    protected static ?string $navigationGroup = 'Documentos';
    protected static ?int $navigationSort = 2; // Saldrá debajo de Contratos
    
    protected static ?string $title = 'Trámites y Cartas';
    protected static string $view = 'filament.pages.generar-solicitudes';

    // --- ACCIÓN 1: SOLICITUD DE CHOFER (Basado en tu Word 1) ---
    public function solicitudChoferAction(): Action
    {
        return Action::make('solicitudChofer')
            ->label('Nueva Solicitud de Chofer')
            ->icon('heroicon-o-user-plus')
            ->color('success')
            ->form([
                // Datos del Accionista (Quien solicita)
                Select::make('accionista_id')
                    ->label('Accionista que solicita')
                    ->options(User::where('role', 'shareholder')->get()->mapWithKeys(fn ($user) => [$user->id => "$user->name $user->last_name"]))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        $user = User::find($state);
                        if ($user) {
                            $set('accionista_nombre', "$user->name $user->last_name");
                            $set('accionista_dni', $user->dni);
                        }
                    }),
                
                // Campos ocultos o editables para el PDF
                TextInput::make('accionista_nombre')->label('Nombre Accionista')->required(),
                TextInput::make('accionista_dni')->label('DNI Accionista')->required(),
                TextInput::make('accionista_direccion')->label('Dirección Accionista')->required()->placeholder('Ej: Mz. A Lt. 14 Zona C...'),

                // Datos del Nuevo Chofer
                TextInput::make('chofer_nombre')->label('Nombre del Nuevo Chofer')->required(),
                TextInput::make('chofer_dni')->label('DNI Chofer')->required(),
                TextInput::make('chofer_direccion')->label('Dirección Chofer')->required(),

                // Datos del Auto
                Select::make('vehiculo_id')
                    ->label('Vehículo a Asignar')
                    ->options(Vehicle::all()->pluck('plate', 'id'))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        $veh = Vehicle::find($state);
                        if ($veh) {
                            $set('vehiculo_marca', $veh->brand);
                            $set('vehiculo_modelo', $veh->model);
                            $set('vehiculo_placa', $veh->plate);
                        }
                    }),
                TextInput::make('vehiculo_marca')->required(),
                TextInput::make('vehiculo_modelo')->required(),
                TextInput::make('vehiculo_placa')->required(),
            ])
            ->action(function (array $data) {
                return redirect()->route('pdf.chofer', $data);
            });
    }

    // --- ACCIÓN 2: INGRESO DE UNIDAD (Basado en tu Word 2) ---
    public function solicitudUnidadAction(): Action
    {
        return Action::make('solicitudUnidad')
            ->label('Solicitud Ingreso de Unidad')
            ->icon('heroicon-o-truck')
            ->color('warning')
            ->form([
                // Datos del Solicitante
                Select::make('user_id')
                    ->label('Solicitante')
                    ->options(User::all()->pluck('name', 'id')) // Puedes filtrar por rol si gustas
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        $user = User::find($state);
                        if ($user) {
                            $set('solicitante_nombre', "$user->name $user->last_name");
                            $set('solicitante_dni', $user->dni);
                        }
                    }),
                TextInput::make('solicitante_nombre')->required(),
                TextInput::make('solicitante_dni')->required(),
                TextInput::make('solicitante_direccion')->required(),

                // Datos del Auto
                TextInput::make('vehiculo_marca')->required(),
                TextInput::make('vehiculo_modelo')->required(),
                TextInput::make('vehiculo_placa')->required(),
                TextInput::make('vehiculo_color')->required()->label('Color del Auto'),
            ])
            ->action(function (array $data) {
                return redirect()->route('pdf.unidad', $data);
            });
    }
}