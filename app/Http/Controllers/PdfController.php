<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Document;

class PdfController extends Controller
{
    public function generarSolicitudChofer(Request $request)
    {
        $pdf = Pdf::loadView('pdf.solicitud-chofer', $request->all());
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Solicitud_Chofer.pdf');
    }

    public function generarSolicitudUnidad(Request $request)
    {
        $pdf = Pdf::loadView('pdf.solicitud-unidad', $request->all());
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Solicitud_Unidad.pdf');
    }

    public function downloadHistory(Document $document)
    {
        // Extraemos los datos del JSON guardado
        $data = $document->content;

        // Agregamos la fecha del registro
        $data['fecha'] = $document->generated_at;

        // Mapeo de datos para que coincidan con tus variables de Blade (Plantillas)
        // Esto adapta el JSON guardado a lo que esperan tus vistas .blade.php
        // Preparamos los datos extendidos para las vistas
        $viewData = array_merge($data, [
            // Mapeamos los campos nuevos
            'nombre_anio' => $data['year_name'] ?? '',
            'distrito' => $data['distrito'] ?? 'Lurín',
            'provincia' => $data['provincia'] ?? 'Lima',
            'departamento' => $data['departamento'] ?? 'Lima',

            // Mapeos anteriores...
            'accionista_nombre' => $data['nombre_completo'] ?? '---',
            'accionista_dni' => $data['dni'] ?? '---',
            'accionista_direccion' => $data['direccion'] ?? '---',

            // Solicitante (para el otro formato)
            'solicitante_nombre' => $data['nombre_completo'] ?? '---',
            'solicitante_dni' => $data['dni'] ?? '---',
            'solicitante_direccion' => $data['direccion'] ?? '---',

            // Auto
            'vehiculo_marca' => $data['vehiculo_marca'] ?? '',
            'vehiculo_modelo' => $data['vehiculo_modelo'] ?? '',
            'vehiculo_placa' => $data['vehiculo_placa'] ?? '',
            'vehiculo_color' => $data['vehiculo_color'] ?? '',

            // Nuevo Chofer
            'chofer_nombre' => $data['chofer_nombre'] ?? '',
            'chofer_dni' => $data['chofer_dni'] ?? '',
            'chofer_direccion' => $data['chofer_direccion'] ?? '',
        ]);

        // Elegir vista según tipo
        $view = match ($document->type) {
            'solicitud_chofer' => 'pdf.solicitud-chofer',
            'solicitud_unidad' => 'pdf.solicitud-unidad',
            default => 'pdf.solicitud-unidad',
        };

        $pdf = Pdf::loadView($view, $viewData);

        return $pdf->stream('documento-' . $document->id . '.pdf');
    }
}
