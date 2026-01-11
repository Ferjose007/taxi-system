<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // Cambiamos el título de la pestaña y del menú
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?string $title = 'Inicio';
}