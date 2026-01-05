<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Contract; // Importante para buscar la relación

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECCIÓN 1: Configuración
                Forms\Components\Section::make('Configuración del Documento')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Documento')
                            ->options([
                                'solicitud_chofer' => 'Solicitud de Nuevo Chofer',
                                'solicitud_unidad' => 'Solicitud de Ingreso de Unidad',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\DatePicker::make('generated_at')
                            ->label('Fecha del Documento')
                            ->default(now())
                            ->required(),
                    ]),

                // SECCIÓN 2: El Solicitante (Accionista o Dueño)
                Forms\Components\Section::make('Datos del Solicitante')
                    ->description('Seleccione al accionista que realiza la solicitud.')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Solicitante')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} {$record->last_name} ({$record->dni})")
                            ->searchable(['name', 'last_name', 'dni'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) return;
                                $user = \App\Models\User::find($state);

                                // Llenar datos del solicitante
                                $set('content.nombre_completo', "$user->name $user->last_name");
                                $set('content.dni', $user->dni);
                                $set('content.direccion', 'Av. Principal S/N, Lurín');

                                // Buscar vehículo del contrato activo
                                $contrato = \App\Models\Contract::where('user_id', $user->id)
                                    ->where('status', true)
                                    ->with('vehicle')
                                    ->latest()
                                    ->first();

                                if ($contrato && $contrato->vehicle) {
                                    $set('content.vehiculo_marca', $contrato->vehicle->brand);
                                    $set('content.vehiculo_modelo', $contrato->vehicle->model);
                                    $set('content.vehiculo_placa', $contrato->vehicle->plate);
                                    $set('content.vehiculo_color', 'NO REGISTRADO');
                                }
                            }),

                        Forms\Components\TextInput::make('content.nombre_completo')->label('Nombre')->readOnly(),
                        Forms\Components\TextInput::make('content.dni')->label('DNI')->readOnly(),
                        Forms\Components\TextInput::make('content.direccion')->label('Dirección')->required(),
                    ])->columns(3),

                // <--- AQUÍ VA EL CÓDIGO NUEVO (GRUPO OCULTO) ---
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Datos del Nuevo Chofer') // Le puse Section para que se vea más ordenado
                            ->description('Ingrese los datos del conductor que desea postular.')
                            ->schema([
                                Forms\Components\TextInput::make('content.chofer_nombre')->label('Nombre Completo')->required(),
                                Forms\Components\TextInput::make('content.chofer_dni')->label('DNI')->required(),
                                Forms\Components\TextInput::make('content.chofer_direccion')->label('Dirección Domiciliaria')->required(),
                            ])->columns(3)
                    ])
                    ->visible(fn(Get $get) => $get('type') === 'solicitud_chofer'),
                // ------------------------------------------------

                // SECCIÓN 3: Vehículo
                Forms\Components\Section::make('Datos del Vehículo')
                    ->schema([
                        Forms\Components\TextInput::make('content.vehiculo_marca')->label('Marca')->required(),
                        Forms\Components\TextInput::make('content.vehiculo_modelo')->label('Modelo')->required(),
                        Forms\Components\TextInput::make('content.vehiculo_placa')->label('Placa')->required(),

                        Forms\Components\TextInput::make('content.vehiculo_color')
                            ->label('Color')
                            ->visible(fn(Get $get) => $get('type') === 'solicitud_unidad')
                            ->required(fn(Get $get) => $get('type') === 'solicitud_unidad'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Documento')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'solicitud_chofer' => 'Solicitud Chofer',
                        'solicitud_unidad' => 'Ingreso Unidad',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'solicitud_chofer' => 'success',
                        'solicitud_unidad' => 'warning',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Solicitante')
                    ->description(fn($record) => $record->user->dni),

                Tables\Columns\TextColumn::make('content.vehiculo_placa')
                    ->label('Placa Ref.'),
            ])
            ->filters([
                // Filtros por fecha o tipo
            ])
            ->actions([
                // BOTÓN DE DESCARGA PDF
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn(Document $record) => route('pdf.download', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('generated_at', 'desc');
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
