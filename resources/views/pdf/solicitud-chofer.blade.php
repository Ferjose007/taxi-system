<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 14px; line-height: 1.5; margin: 30px; }
        /* 1. Estilo para el Año */
        .anio-header { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 20px; text-transform: uppercase; }
        
        /* 2. Estilo para SOLICITO a la derecha */
        .solicito-line { text-align: right; font-weight: bold; margin-bottom: 10px; }
        
        .destinatario { text-align: left; font-weight: bold; margin-bottom: 20px; }
        .contenido { text-align: justify; margin-top: 20px; }
        .firma { margin-top: 100px; text-align: center; } /* 5. Espacio firma (margin-top alto) */
        .linea { border-top: 1px solid #000; width: 250px; margin: 0 auto; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="anio-header">
        {{ $nombre_anio }}
    </div>

    <div class="solicito-line">
        SOLICITUD: Ingreso de nuevo chofer
    </div>

    <div class="destinatario">
        Sr. presidente de la empresa Transportes y Servicios Manto Bendito Virgen de las Mercedes.<br>
        Sr. Guilmar Huaringa<br>
        Presente.
    </div>

    <div class="contenido">
        <p>De mi consideración.</p>

        <p>
            Reciba un cordial saludo de parte, del Sr. <strong>{{ $accionista_nombre }}</strong>, 
            identificado con N° de DNI {{ $accionista_dni }}, con domicilio en {{ $accionista_direccion }}, 
            del distrito de {{ $distrito }}, provincia de {{ $provincia }}, departamento de {{ $departamento }},
            quien soy accionista de la empresa que usted dirige...
        </p>

        <p>
            El presente documento es para solicitarle a usted y a su junta directiva, que acepte al Sr. 
            <strong>{{ $chofer_nombre }}</strong>, identificado con N° de DNI {{ $chofer_dni }}, 
            con domicilio en {{ $chofer_direccion }}, como chofer del carro {{ $vehiculo_marca }}, 
            modelo {{ $vehiculo_modelo }}, con placa de rodaje <strong>{{ $vehiculo_placa }}</strong>...
        </p>

        <p>
            Esperando que mi solicitud sea aceptada favorablemente por usted y su junta directiva, me despido no sin antes 
            agradecerle por su atención ante mi pedido.
        </p>

        <p style="text-align: right; margin-top: 30px;">
            Lurín, {{ \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM [del] YYYY') }}
        </p>
    </div>

    <div class="firma">
        <p style="margin-bottom: 60px;">Atte.</p> <div class="linea">
            {{ $accionista_nombre }}<br>
            DNI {{ $accionista_dni }}
        </div>
    </div>
</body>
</html>