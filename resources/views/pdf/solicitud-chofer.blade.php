<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 14px; line-height: 1.5; margin: 30px; }
        .titulo { font-weight: bold; text-align: left; margin-bottom: 20px; }
        .contenido { text-align: justify; margin-top: 20px; }
        .firma { margin-top: 100px; text-align: center; }
        .linea { border-top: 1px solid #000; width: 250px; margin: 0 auto; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="titulo">
        SOLICITUD: Ingreso de nuevo chofer<br>
        Sr. presidente de la empresa Transportes y Servicios Manto Bendito Virgen de las Mercedes.<br>
        Sr. Guilmar Huaringa<br>
        Presente.
    </div>

    <div class="contenido">
        <p>De mi consideración.</p>

        <p>
            Reciba un cordial saludo de parte, del Sr. <strong>{{ $accionista_nombre }}</strong>, 
            identificado con N° de DNI {{ $accionista_dni }}, con domicilio en {{ $accionista_direccion }}, 
            quien soy accionista de la empresa que usted dirige, deseándole éxitos en las funciones que usted desempeña, 
            paso a decirle lo sgte.
        </p>

        <p>
            El presente documento es para solicitarle a usted y a su junta directiva, que acepte al Sr. 
            <strong>{{ $chofer_nombre }}</strong>, identificado con N° de DNI {{ $chofer_dni }}, 
            con domicilio en {{ $chofer_direccion }}, como chofer del carro {{ $vehiculo_marca }}, 
            modelo {{ $vehiculo_modelo }}, con placa de rodaje <strong>{{ $vehiculo_placa }}</strong>, 
            ya que dicho vehículo será quien trabaje por mi acción en fecha indefinida y así empezar a cotizar en la compañía.
        </p>
        
        <p>
            Ya que, por motivos de trabajo en otras áreas, se me hace difícil cumplir como conductor en la empresa, 
            no está de más recalcar que mi persona se hará responsable por las faltas que cometa el sr. Chofer 
            y cumpliendo con los reglamentos establecidos de la empresa.
        </p>

        <p>
            Esperando que mi solicitud sea aceptada favorablemente por usted y su junta directiva, me despido no sin antes 
            agradecerle por su atención ante mi pedido.
        </p>

        <p style="text-align: right; margin-top: 30px;">
            Lurín, {{ now()->isoFormat('D [de] MMMM [del] YYYY') }}
        </p>
    </div>

    <div class="firma">
        <p>Atte.</p>
        <div class="linea">
            {{ $accionista_nombre }}<br>
            DNI {{ $accionista_dni }}
        </div>
    </div>
</body>
</html>