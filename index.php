<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="es"> <!--<![endif]-->

  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="author" content="enriquezapata.com">
	<meta name="owner" content="Enrique Zapata S.">
	<meta name="robots" content="index, follow">
	<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,600' rel='stylesheet' type='text/css'>

    <!-- Bootstrap CSS -->
	<link rel="stylesheet" href="css/bootstrap.min.css">
	
	<style>	
		thead {
			background-color: #304355;
		}
	</style>

    <title>Lector de CFDI (XML)</title>

	<script type="text/javascript" src="https://apis.google.com/js/plusone.js">
		{lang: 'es'}
	</script>
</head>


<?php


class LeerCFDI
{
private $namespaces;
private $xml;
private $serie;
private $folio;
private $rfcEmisor;
private $nombreEmisor;
private $rfcReceptor;
private $fecha;
private $montobase;
private $iva;
private $tasaiva;
private $total;
private $tipoComprobante;
private $uuid;
private $garrStrDirectorio;


function leerDirectorio( $pstrDirectorio )
{
	$garrStrDirectorio = scandir($pstrDirectorio);

	foreach( $garrStrDirectorio as $archivo )
	{
		if ( strstr($archivo,'.xml') )
		{
			$this->cargaXML($pstrDirectorio."/".$archivo);

			echo "<tr>";
			echo "<td scope=\"row\">";
			echo $archivo;
			echo "</td>";
			echo "<td>";
			echo str_replace("T", " ", $this->fecha());
			echo "</td>";
			echo "<td>";
			echo $this->tipoComprobante();
			echo "</td>";
			echo "<td>";
			echo $this->nombreEmisor();
			echo "</td>";
			echo "<td>";
			echo $this->montobase();
			echo "</td>";
			echo "<td>";
			echo $this->tasaiva();
			echo "</td>";
			echo "<td>";
			echo $this->iva();
			echo "</td>";
			echo "<td>";
			echo $this->total();
			echo "</td>";
			echo "</tr>";
	
		}
	}
	
}

function cargaXml($archivoXML)
{
	setlocale(LC_MONETARY,"es_MX");

	$this->rfcEmisor = "";
	$this->fecha = "";
	$this->tipoComprobante = "";
	$this->nombreEmisor = "";
	$this->tasaiva = "0%";
	$this->iva = NumberFormatter::create( 'es_MX', NumberFormatter::CURRENCY )->format(0.0);
	$this->total = NumberFormatter::create( 'es_MX', NumberFormatter::CURRENCY )->format(0.0);
	$this->montobase = NumberFormatter::create( 'es_MX', NumberFormatter::CURRENCY )->format(0.0);

	if ( file_exists($archivoXML) )
	{
		libxml_use_internal_errors(true);
		$this -> xml = new SimpleXMLElement($archivoXML, null, true);
		$this -> namespaces = $this -> xml -> getNamespaces(true);
	}
	else
	{
		throw new Exception("Error al cargar archivo XML, verifique que el archivo exista.", 1);
	}
}


function getRfcEmisor()
{
	foreach($this->xml->xpath('//cfdi:Comprobante//cfdi:Emisor') as $emisor)
	{
		$this->rfcEmisor = $emisor['rfc'] != "" ? $emisor['rfc'] : $emisor['Rfc'];
	}

	return $this->rfcEmisor;
}

function nombreEmisor()
{
	foreach ($this -> xml->xpath('//cfdi:Comprobante//cfdi:Emisor') as $emisor)
	{
		$this -> nombreEmisor = $emisor['nombre'] != "" ? $emisor['nombre'] : $emisor['Nombre'];
	}

	return $this -> nombreEmisor;
}

function rfcReceptor()
{
	foreach ($this->xml->xpath('//cfdi:Comprobante//cfdi:Receptor') as $receptor)
	{
		$this->rfcReceptor = $receptor['rfc'] != "" ? $receptor['rfc'] : $receptor['Rfc'];
	}

	return $this -> rfcReceptor;
}

function total()
{
	setlocale(LC_MONETARY,"es_MX");

	foreach ($this -> xml->xpath('//cfdi:Comprobante') as $comprobante)
	{
		$this -> total = NumberFormatter::create( 'es_MX', NumberFormatter::CURRENCY )->format(floatval($comprobante['total'] != "" ? $comprobante['total'] : $comprobante['Total']));
	}

	return $this -> total;
}

function montobase()
{
	foreach ($this -> xml->xpath('//cfdi:Comprobante//cfdi:Conceptos//cfdi:Concepto//cfdi:Impuestos//cfdi:Traslados//cfdi:Traslado') as $comprobante)
	{
		$this -> montobase = NumberFormatter::create( 'es_MX', NumberFormatter::CURRENCY )->format(floatval($comprobante['base'] != "" ? $comprobante['base'] : $comprobante['Base']));
	}

	return $this -> montobase;
}

function iva()
{
	foreach ($this -> xml->xpath('//cfdi:Comprobante//cfdi:Conceptos//cfdi:Concepto//cfdi:Impuestos//cfdi:Traslados//cfdi:Traslado') as $comprobante)
	{
		$this -> iva = NumberFormatter::create( 'es_MX', NumberFormatter::CURRENCY )->format(floatval($comprobante['importe'] != "" ? $comprobante['importe'] : $comprobante['Importe']));
	}

	return $this -> iva;
}

function tasaiva()
{
	foreach ($this -> xml->xpath('//cfdi:Comprobante//cfdi:Conceptos//cfdi:Concepto//cfdi:Impuestos//cfdi:Traslados//cfdi:Traslado') as $comprobante)
	{
		$this -> tasaiva = number_format(floatval($comprobante['tasaocuota'] != "" ? $comprobante['tasaocuota'] : $comprobante['TasaOCuota']) * 100, 0, '.',',')."%";
	}

	return $this -> tasaiva;
}

function serie()
{
	foreach ($this -> xml->xpath('//cfdi:Comprobante') as $comprobante)
	{
		$this -> serie = $comprobante['serie'] != "" ? $comprobante['serie'] : $comprobante['Serie'];
	}

	return $this -> serie;
}


function folio()
{
	foreach ($this -> xml->xpath('//cfdi:Comprobante') as $comprobante)
	{
		$this -> folio = $comprobante['folio'] != "" ? $comprobante['folio'] : $comprobante['Folio'];
	}

	return $this -> folio;
}


function fecha()
{
	foreach ($this->xml->xpath('//cfdi:Comprobante') as $comprobante)
	{
		$this->fecha = $comprobante['fecha'] != "" ? $comprobante['fecha'] : $comprobante['Fecha'];
	}

	return $this->fecha;
}


function tipoComprobante()
{
	foreach ($this->xml->xpath('//cfdi:Comprobante') as $comprobante)
	{
		$this->tipoComprobante = $comprobante['tipoDeComprobante'] != "" ? $comprobante['tipoDeComprobante'] : $comprobante['TipoDeComprobante'];
	}

	if (strcmp(strtolower($this->tipoComprobante), 'ingreso') == 0 || strcmp(strtolower($this -> tipoComprobante), 'i') == 0)
	{
		$this->tipoComprobante = "I";
	}
	else
	{
		$this -> tipoComprobante = "E";
	}

	return $this -> tipoComprobante;
}

/**
* Obtener el UUID de la factura
*/
function uuid()
{
	$this -> xml -> registerXPathNamespace('t', $this -> namespaces['tfd']);

	foreach ($this->xml->xpath('//t:TimbreFiscalDigital') as $tfd)
	{
		$this -> uuid = "{$tfd['UUID']}";
	}

	return $this -> uuid;
}

}

$cfdi = new LeerCFDI();

?>

<body>

	<div class="container-fluid py-5">
      <div class="container">
        <h1 class="display-4 text-center"><b>Lectura de archivos xml de facturas emitidas.</b></h1>
        <p class="lead text-center">Esta aplicacion realizara la lectura de los archivos xml para mostrate el contenido en un formato legible.</p>
        <p class="lead text-center">Solo mostrará el nombre del archivo, La fecha del documento, el Tipo (I/E), quien emite la factura y el total de la misma.</p>
	  </div>
	  <div class="py-5">
		  <div class="table-hover table-responsive">
			<table class="table font-weight-light">
				<thead class="text-white">
					<th scope="col">Archivo</th>
					<th scope="col">Fecha</th>
					<th scope="col">Tipo</th>
					<th scope="col">Emisor</th>
					<th scope="col">Base</th>
					<th scope="col">Tasa</th>
					<th scope="col">Iva</th>
					<th scope="col">Total</th>
				</thead>
				<tbody class="text-black">
					<?php $mes_app = $_GET['mes']; $anio_app = $_GET['anio']; $cfdi->leerDirectorio("facturas/".$anio_app."/".$mes_app."/"); ?>
				</tbody>
			</table>
		</div>
	</div>
	</div>


    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="js/bootstrap.min.js"></script>

</body>
</html>
