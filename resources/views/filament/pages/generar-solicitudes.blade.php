<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <x-filament::section>
            <x-slot name="heading">Ingreso de Nuevo Chofer</x-slot>
            <x-slot name="description">Carta dirigida al presidente Sr. Guilmar Huaringa solicitando el ingreso de un conductor.</x-slot>
            {{ $this->solicitudChoferAction }}
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Ingreso de Unidad</x-slot>
            <x-slot name="description">Carta de compromiso y solicitud para ingresar un vehículo nuevo a la flota.</x-slot>
            {{ $this->solicitudUnidadAction }}
        </x-filament::section>

    </div>
</x-filament-panels::page>
