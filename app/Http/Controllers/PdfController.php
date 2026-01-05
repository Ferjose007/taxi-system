<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Document;

class PdfController extends Controller
{
    public function generarSolicitudChofer(Request $request) {
        $pdf = Pdf::loadView('pdf.solicitud-chofer', $request->all());
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Solicitud_Chofer.pdf');
    }

    public function generarSolicitudUnidad(Request $request) {
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
    $viewData = [
        // Datos comunes
        'accionista_nombre' => $data['nombre_completo'] ?? '---',
        'accionista_dni' => $data['dni'] ?? '---',
        'accionista_direccion' => $data['direccion'] ?? '---',
        'solicitante_nombre' => $data['nombre_completo'] ?? '---', // Para el otro formato
        'solicitante_dni' => $data['dni'] ?? '---',
        'solicitante_direccion' => $data['direccion'] ?? '---',
        
        // Datos vehículo
        'vehiculo_marca' => $data['vehiculo_marca'] ?? '',
        'vehiculo_modelo' => $data['vehiculo_modelo'] ?? '',
        'vehiculo_placa' => $data['vehiculo_placa'] ?? '',
        'vehiculo_color' => $data['vehiculo_color'] ?? '',
        
        // Datos Chofer (En caso de Solicitud Chofer, el 'user' del documento es el accionista solicitante)
        // OJO: Aquí hay un detalle. En tu Word de "Chofer", un Accionista pide por un Chofer.
        // En el formulario simplificado arriba, pusimos solo un "Usuario".
        // Si necesitas distinguir "Quién pide" vs "Quién es el chofer nuevo", avísame para agregar un campo extra al form.
    ];
    
    // Elegir vista según tipo
    $view = match ($document->type) {
        'solicitud_chofer' => 'pdf.solicitud-chofer',
        'solicitud_unidad' => 'pdf.solicitud-unidad',
        default => 'pdf.solicitud-unidad',
    };

    $pdf = Pdf::loadView($view, $viewData);

    return $pdf->stream('documento-'.$document->id.'.pdf');
}
}