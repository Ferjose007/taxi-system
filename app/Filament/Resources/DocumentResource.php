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
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    //protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Documentos';
    protected static ?string $navigationLabel = 'Historial de Solicitudes';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SECCIÓN 1: CONFIGURACIÓN ---
                Forms\Components\Section::make('Configuración del Documento')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Solicitud')
                            ->options([
                                'solicitud_chofer' => 'Solicitud de Nuevo Chofer (Accionista solicita)',
                                'solicitud_unidad' => 'Ingreso de Unidad Nueva (Contratado solicita)',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                // Limpiamos todo al cambiar de tipo para no mezclar roles
                                $set('user_id', null);
                                $set('vehicle_selector', null);
                                $set('content', []);
                            }),

                        Forms\Components\DatePicker::make('generated_at')
                            ->label('Fecha del Documento')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('content.year_name')
                            ->label('Nombre del Año (Encabezado)')
                            ->default('“AÑO DE LA CONSOLIDACIÓN DE NUESTRA INDEPENDENCIA”')
                            ->columnSpanFull(),
                    ]),

                // --- SECCIÓN 2: DATOS DEL SOLICITANTE ---
                Forms\Components\Section::make('Datos del Solicitante')
                    ->description(fn(Get $get) => match ($get('type')) {
                        'solicitud_chofer' => 'Seleccione al Accionista que solicita el chofer.',
                        'solicitud_unidad' => 'Seleccione al Conductor Contratado que ingresa la unidad.',
                        default => 'Seleccione el tipo de solicitud primero.'
                    })
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label(fn(Get $get) => match ($get('type')) {
                                'solicitud_chofer' => 'Accionista Solicitante',
                                'solicitud_unidad' => 'Conductor Contratado',
                                default => 'Solicitante'
                            })
                            // --- AQUÍ LA LÓGICA DE FILTRO DINÁMICO ---
                            ->relationship('user', 'name', modifyQueryUsing: function (Builder $query, Get $get) {
                                $type = $get('type');
                                if ($type === 'solicitud_chofer') {
                                    return $query->where('role', 'Accionista'); // REGLA 2
                                }
                                if ($type === 'solicitud_unidad') {
                                    return $query->where('role', 'Contratado'); // REGLA 3
                                }
                                // Si no ha elegido tipo, no mostramos a nadie para evitar errores
                                return $query->whereNull('id');
                            })
                            // ------------------------------------------
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} {$record->last_name} - {$record->role}")
                            ->searchable(['name', 'last_name', 'dni'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state)
                                    return;
                                $user = \App\Models\User::find($state);

                                // Llenar datos personales
                                $set('content.nombre_completo', "$user->name $user->last_name");
                                $set('content.dni', $user->dni);
                                $set('content.direccion', $user->address ?? '---');
                                $set('content.distrito', $user->district ?? 'Lurín');
                                $set('content.provincia', $user->province ?? 'Lima');
                                $set('content.departamento', $user->department ?? 'Lima');

                                $set('vehicle_selector', null);
                            }),

                        // Campos ReadOnly
                        Forms\Components\TextInput::make('content.nombre_completo')->label('Nombre')->readOnly(),
                        Forms\Components\TextInput::make('content.dni')->label('DNI')->readOnly(),
                        Forms\Components\TextInput::make('content.direccion')->label('Dirección')->readOnly(),
                        Forms\Components\TextInput::make('content.distrito')->label('Distrito')->readOnly(),
                        Forms\Components\TextInput::make('content.provincia')->label('Provincia')->readOnly(),
                        Forms\Components\TextInput::make('content.departamento')->label('Departamento')->readOnly(),

                    ])->columns(3),

                // --- SECCIÓN 3: VEHÍCULO ---
                Forms\Components\Section::make('Datos del Vehículo')
                    ->schema([
                        // Selector Opcional (Solo busca autos del usuario seleccionado)
                        Forms\Components\Select::make('vehicle_selector')
                            ->label('Autocompletar con Vehículo Existente (Opcional)')
                            ->options(function (Get $get) {
                                $userId = $get('user_id');
                                if (!$userId)
                                    return [];
                                return \App\Models\Vehicle::where('user_id', $userId)
                                    ->get()
                                    ->mapWithKeys(fn($v) => [$v->id => "$v->plate - $v->brand $v->model"]);
                            })
                            ->searchable()
                            ->live()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state)
                                    return;
                                $veh = \App\Models\Vehicle::find($state);
                                if ($veh) {
                                    $set('content.vehiculo_marca', $veh->brand);
                                    $set('content.vehiculo_modelo', $veh->model);
                                    $set('content.vehiculo_placa', $veh->plate);
                                    $set('content.vehiculo_color', $veh->color);
                                    Notification::make()->title('Datos cargados')->success()->send();
                                }
                            }),

                        Forms\Components\TextInput::make('content.vehiculo_marca')->label('Marca')->required(),
                        Forms\Components\TextInput::make('content.vehiculo_modelo')->label('Modelo')->required(),
                        Forms\Components\TextInput::make('content.vehiculo_placa')->label('Placa')->required(),
                        Forms\Components\TextInput::make('content.vehiculo_color')
                            ->label('Color')
                            ->visible(fn(Get $get) => $get('type') === 'solicitud_unidad' || $get('vehicle_selector'))
                            ->required(fn(Get $get) => $get('type') === 'solicitud_unidad'),
                    ])->columns(3),

                // --- SECCIÓN 4: NUEVO CHOFER (Solo para Solicitud Chofer) ---
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Datos del Nuevo Chofer')
                            ->description('Ingrese los datos del conductor que cubrirá el puesto del Accionista.')
                            ->schema([
                                Forms\Components\TextInput::make('content.chofer_nombre')->label('Nombre Completo')->required(),
                                Forms\Components\TextInput::make('content.chofer_dni')->label('DNI')->required(),
                                Forms\Components\TextInput::make('content.chofer_direccion')->label('Dirección')->required(),
                            ])->columns(3)
                    ])
                    ->visible(fn(Get $get) => $get('type') === 'solicitud_chofer'),
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
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'solicitud_chofer' => 'Nuevo Chofer',
                        'solicitud_unidad' => 'Ingreso Unidad',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'solicitud_chofer' => 'info',   // Azul
                        'solicitud_unidad' => 'warning', // Naranja
                        default => 'gray',
                    }),

                // Columna 3: Solicitante
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Solicitante')
                    ->description(fn(Document $record) => $record->user->dni ?? '')
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
                    ->url(fn(Document $record) => route('pdf.download', $record))
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
