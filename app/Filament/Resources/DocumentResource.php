<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\Models\Contract;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Documentos';
    protected static ?string $navigationLabel = 'Historial de Solicitudes';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SECCIÓN 1: ¿QUÉ VAMOS A HACER? ---
                Forms\Components\Section::make('Tipo de Trámite')
                    ->description('Seleccione primero el tipo de solicitud para configurar el formulario.')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Solicitud')
                            ->options([
                                'solicitud_chofer' => 'Solicitud de Nuevo Chofer',
                                'solicitud_unidad' => 'Ingreso de Unidad Nueva',
                            ])
                            ->required()
                            ->live() // ¡CLAVE! Hace que el formulario reaccione al cambio
                            ->afterStateUpdated(function (Set $set) {
                                // Limpia los campos si cambias de tipo para evitar mezclas
                                $set('user_id', null);
                                $set('content', []); 
                            }),

                        Forms\Components\DatePicker::make('generated_at')
                            ->label('Fecha del Documento')
                            ->default(now())
                            ->required(),
                    ])->columns(2),

                // --- SECCIÓN 2: DATOS DEL SOLICITANTE ---
                Forms\Components\Section::make('Datos del Solicitante')
                    ->description('Quién realiza la solicitud (Accionista o Conductor contratado).')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Solicitante')
                            // Filtro: Solo mostramos Accionistas y Choferes
                            ->relationship('user', 'name', modifyQueryUsing: fn ($query) => $query->whereIn('role', ['Accionista', 'Contratado']))
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} {$record->last_name} - " . ucfirst($record->role))
                            ->searchable(['name', 'last_name', 'dni'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (!$state) return;
                                $user = \App\Models\User::find($state);

                                // 1. Llenamos datos personales siempre
                                $set('content.nombre_completo', "$user->name $user->last_name");
                                $set('content.dni', $user->dni);

                                // --- AQUÍ EL CAMBIO: Jalamos la dirección real ---
                                // Si el usuario no tiene dirección, ponemos un texto por defecto para que no salga vacío
                                $set('content.direccion', $user->address ?? 'DIRECCIÓN NO REGISTRADA EN EL SISTEMA'); 
                                // -------------------------------------------------

                                // 2. LÓGICA CONDICIONAL DE VEHÍCULO
                                // Solo buscamos auto automático si es "Solicitud de Chofer" (porque el auto ya existe)
                                if ($get('type') === 'solicitud_chofer') {
                                    $contrato = Contract::where('user_id', $user->id)
                                        ->where('status', true)
                                        ->with('vehicle')
                                        ->latest()
                                        ->first();

                                    if ($contrato && $contrato->vehicle) {
                                        $set('content.vehiculo_marca', $contrato->vehicle->brand);
                                        $set('content.vehiculo_modelo', $contrato->vehicle->model);
                                        $set('content.vehiculo_placa', $contrato->vehicle->plate);
                                    }
                                } 
                                // Si es "Ingreso de Unidad", NO llenamos nada, porque el usuario debe escribir los datos del auto nuevo.
                            }),

                        // Campos de solo lectura (se llenan solos al elegir al usuario)
                        Forms\Components\TextInput::make('content.nombre_completo')->label('Nombre')->readOnly(),
                        Forms\Components\TextInput::make('content.dni')->label('DNI')->readOnly(),
                        Forms\Components\TextInput::make('content.direccion')->label('Dirección')->required(),
                    ])->columns(3),

                // --- SECCIÓN 3: NUEVO CHOFER (Solo visible si el tipo es Solicitud Chofer) ---
                Forms\Components\Section::make('Datos del Nuevo Conductor')
                    ->description('Ingrese los datos del postulante.')
                    ->schema([
                        Forms\Components\TextInput::make('content.chofer_nombre')->label('Nombre Completo')->required(),
                        Forms\Components\TextInput::make('content.chofer_dni')->label('DNI')->required(),
                        Forms\Components\TextInput::make('content.chofer_direccion')->label('Dirección')->required(),
                    ])
                    ->columns(3)
                    ->visible(fn (Get $get) => $get('type') === 'solicitud_chofer'),

                // --- SECCIÓN 4: VEHÍCULO (Visible siempre, pero cambia si es manual o auto) ---
                Forms\Components\Section::make('Datos del Vehículo')
                    ->description(fn (Get $get) => $get('type') === 'solicitud_unidad' 
                        ? 'Ingrese los datos de la unidad que desea ingresar.' 
                        : 'Vehículo al que se asignará el nuevo chofer.')
                    ->schema([
                        Forms\Components\TextInput::make('content.vehiculo_marca')->label('Marca')->required(),
                        Forms\Components\TextInput::make('content.vehiculo_modelo')->label('Modelo')->required(),
                        Forms\Components\TextInput::make('content.vehiculo_placa')->label('Placa')->required(),
                        
                        // El color solo lo piden en Ingreso de Unidad
                        Forms\Components\TextInput::make('content.vehiculo_color')
                            ->label('Color')
                            ->visible(fn (Get $get) => $get('type') === 'solicitud_unidad')
                            ->required(fn (Get $get) => $get('type') === 'solicitud_unidad'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columna 1: Fecha
                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                // Columna 2: Tipo (con colores para diferenciar rápido)
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo de Solicitud')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'solicitud_chofer' => 'Nuevo Chofer',
                        'solicitud_unidad' => 'Ingreso Unidad',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'solicitud_chofer' => 'info',   // Azul
                        'solicitud_unidad' => 'warning', // Naranja
                        default => 'gray',
                    }),

                // Columna 3: Solicitante
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Solicitante')
                    ->description(fn (Document $record) => $record->user->dni ?? '')
                    ->searchable(),

                // Columna 4: Placa Referencial (sacada del JSON)
                Tables\Columns\TextColumn::make('content.vehiculo_placa')
                    ->label('Placa')
                    ->searchable(),
            ])
            ->filters([
                // Filtro para ver solo un tipo de solicitud
                Tables\Filters\SelectFilter::make('type')
                    ->label('Filtrar por Tipo')
                    ->options([
                        'solicitud_chofer' => 'Nuevo Chofer',
                        'solicitud_unidad' => 'Ingreso Unidad',
                    ]),
            ])
            ->actions([
                // Botón para descargar el PDF
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Document $record) => route('pdf.download', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('generated_at', 'desc');
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