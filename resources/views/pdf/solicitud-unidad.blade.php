<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 14px; line-height: 1.5; margin: 30px; }
        .header { font-weight: bold; margin-bottom: 20px; }
        .contenido { text-align: justify; margin-top: 20px; }
        .firma { margin-top: 100px; text-align: center; }
        .linea { border-top: 1px solid #000; width: 250px; margin: 0 auto; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        SOLICITO : INGRESO DE UNIDAD<br>
        Señor presidente:<br>
        GUILMAR HUARINGA ROJAS, de la Empresa de Transportes y Servicios EL MANTO BENDITO VIRGEN DE LAS MERCEDES S.A.
    </div>

    <div class="contenido">
        <p>
            Yo, <strong>{{ $solicitante_nombre }}</strong>, identificado con N.º de DNI {{ $solicitante_dni }}, 
            con domicilio en {{ $solicitante_direccion }}, del distrito de Punta Negra, provincia y departamento de Lima:
        </p>

        <p>
            Solicito ingreso de unidad marca {{ $vehiculo_marca }}, modelo {{ $vehiculo_modelo }}, 
            placa <strong>{{ $vehiculo_placa }}</strong> y color {{ $vehiculo_color }}, para laborar en su empresa de 
            transportes y servicios EL MANTO BENDITO VIRGEN DE LAS MERCEDES S.A.
        </p>

        <p>
            Y me comprometo a cumplir con todas las normas establecidas con la empresa, respetando los turnos, sanciones y reglamentos.
        </p>

        <p>Por lo expuesto:</p>
        <p>Adjunto los siguientes documentos.<br>A Uds. Señores, se sirva acceder a mi solicitud por ser de ley.</p>

        <p style="text-align: right; margin-top: 30px;">
            Lurín, {{ now()->isoFormat('D [de] MMMM [del] YYYY') }}
        </p>
    </div>

    <div class="firma">
        <p>Atentamente</p>
        <div class="linea">
            {{ $solicitante_nombre }}<br>
            DNI {{ $solicitante_dni }}
        </div>
    </div>
</body>
</html>