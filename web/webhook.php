<?php
header('Content-Type: application/json');
ob_start();

#Constantes
$BDstock = 'bd/stock.csv';
$BDpedidos = 'bd/pedidos.csv';

#Clase Stock
class Stock
{
	public $nombre;
	public $tipo;
	public $precio;
	public $stock;		
	
	public function __construct($n, $t, $p, $s)
	{
        $this->nombre = $n;
		$this->tipo = $t;
		$this->precio = $p;
		$this->stock = $s;
    }
}

function obtenerStock($n, $lista)
{
	foreach ($lista as &$Stock)
	{
		if($Stock->nombre==$n)
		{
			return $Stock->stock;
		}
	}
}

function obtenerPrecio($u, $n)			#NO PASO LA LISTA!!!!! POSIBLE ERROR, CHEQUEAR
{
	foreach ($arrayStock as &$Stock)
	{
		if($Stock->nombre==$n)
		{
			return $Stock->precio * $u;
		}
	}
}

#Parametros Ficticios (BD)
$direccion = 'C/Luis Jorge Castaños, 23, 4º Dcha. 28999 Valdecillas de Jarama, Madrid';

#Obtener Info. Peticion
$json = file_get_contents('php://input'); 
$request = json_decode($json, true);
$action = $request['result']['action'];
$parameters = $request['result']['parameters'];

#Obtener CSV Stock
if (($fichero = fopen($BDstock, "r")) !== FALSE) 
{
	while (($data = fgetcsv($fichero, 1000, ",")) !== FALSE) 
	{
		$arrayStock[] = new Stock($data[0], $data[1], $data[2], $data[3]);
	}
	fclose($fichero);
}

#LOG Stock
error_log("STOCK");
foreach($arrayStock as &$Stock)
{
	error_log($Stock->nombre . ' (' . $Stock->tipo . ') = ' . $Stock->precio . '€ - ' . $Stock->stock . ' en Stock');
}

switch ($action) 
{
    case 'nuevo.consultarStock':
		error_log('ACCION = Consultar Stock');
		#Parametros
		$vino = $parameters['vino'];
		$stock = obtenerStock($vino, $arrayStock);
		$nbotellas = $parameters['nbotellas'];
		
		#Log
		error_log('Petición: ' . $nbotellas . ' botellas de ' . $vino);
		error_log($stock . ' botellas en stock');
		
		#Consultar Stock
		if($stock<=0)
		{
			#STOCK VACIO > Proponer Sustituir por Gran Coronas
			$outputtext = 'Lo sentimos pero no nos quedan existencias de ' . $vino . ', Le recomendamos un vino similar como es el Gran Coronas. Disponemos de las ' . $nbotellas . ' botellas por ' . obtenerPrecio($completar, 'Gran Coronas') . '€. ¿Las quiere?';
			$contextout = array(array('name'=>'consultarCambio', 'lifespan'=>2, 'parameters'=>array('vino'=>'Gran Coronas', 'nBotellas'=>$nbotellas)));;
		}
		else if ($stock<$nbotellas) 
		{
			#STOCK INSUFICIENTE > Completar Pedido o Sustituir por Gran Coronas
			$outputtext = 'Lo sentimos pero solamente nos quedan ' . $stock . ' existencias de ' . $vino . ', Le recomendamos un vino similar como es el Gran Coronas. Puede completar el pedido con ' . ($nbotellas - $stock) . ' unidades o sustituirlo por completo con ' . $nbotellas . ' botellas.';
			$contextout = array(array('name'=>'consultarAlternativa', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas)));;
		} 
		else
		{
			#STOCK OK
			$outputtext = 'Perfecto, tenemos las ' . $nbotellas . ' botellas de ' . $vino . ' en stock, a un precio de ' . obtenerPrecio($nbotellas, $vino) . '€ ¿Es ésta su dirección? = ' . $direccion;
			$contextout = array(array('name'=>'consultaDireccion', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'direccion'=>$direccion)));
		}
		break;
    case 'nuevo.completarPedido':
	    error_log('ACCION = Completar Pedido');
		#Parametros
		$vino = $parameters['vino'];
		$stock = obtenerStock($vino, $arrayStock);
		$nbotellas = $stock;
		$completar = $parameters['nbotellas'] - $stock;

		$outputtext = 'Perfecto, entonces serán ' . $nbotellas . ' botellas de ' . $vino . ' junto con ' . $completar . ' de Gran Coronas. El precio totales de ' . obtenerPrecio($nbotellas, $vino) + obtenerPrecio($completar, 'Gran Coronas') . '€ ¿Es ésta su dirección? = ' . $direccion;
		$contextout = array(array('name'=>'consultaDireccion', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'completar' => $completar, 'direccion'=>$direccion)));

        break;
	case 'nuevo.cambiarPedido':
		error_log('ACCION = Cambiar Pedido');
		#Parametros
		$vino = $parameters['vino'];
		$nbotellas = $parameters['nbotellas'];
		$outputtext = 'Perfecto, entonces serán ' . $nbotellas . ' botellas de Gran Coronas. ¿Es ésta su dirección? = ' . $direccion;
		$contextout = array(array('name'=>'consultaDireccion', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'completar' => 0, 'direccion'=>$direccion)));
		break;
	case 'nuevo.confirmarDireccion':
        error_log('ACCION = Confirmar Direccion');
		#Parametros
		$vino = $parameters['vino'];
		$nbotellas = $parameters['nbotellas'];
		$completar = $parameters['completar'];
		$direccion = $parameters['direccion'];
		
		#Almacenar Pedido
		error_log($vino . ' = ' . $nbotellas . ' unidades');
		if($completar!=0)
		{
			error_log('Gran Coronas = ' . $completar . ' unidades');
		}
		error_log('Dirección = ' . $direccion);
		
		#Actualizar Array
		foreach ($arrayStock as $Stock)
		{
			if($Stock->nombre==$vino)
			{
				$Stock->stock = obtenerStock($vino, $arrayStock) - $nbotellas;
				error_log('Stock ' . $vino . ' = ' . $Stock->stock . ' unidades');
			}
			#Alternativa (Completar con Gran Coronas)
			if($Stock->nombre=='Gran Coronas' and $completar!=0)
			{
				$Stock->stock = obtenerStock('Gran Coronas', $arrayStock) - $completar;
				error_log('Stock Gran Coronas = ' . $Stock->stock . ' unidades');
			}
		}
		
		#Actualizar CSV
		actualizarCSV($arrayStock);
		#Comprobar de Nuevo
		mostrarCSV();
		
		#Mensaje (Text Response automático de API.AI no se envía en Twitter)
		$outputtext = 'Perfecto, su pedido se ha realizado. Gracias.';
		
        break;
}

$source = 'bodegastorres.php';
#Devolver JSON
$output['speech'] = $outputtext;
$output['displayText'] = $outputtext;
$output['contextOut'] = $contextout;
$output['source'] = $source;
$output['followupEvent'] = $followupEvent;
echo json_encode($output);
ob_end_clean();
echo json_encode($output);


function actualizarCSV($array)
{
	error_log('ACTUALIZANDO STOCK');
	global $BDstock;
	
	$fichero = fopen($BDstock, 'w');
	foreach($array as &$Stock)
	{
		#Conversión de Objeto (Stock) a Array
		$fila = (array)$Stock;
		fputcsv($fichero, $fila);
	}
	fclose($fichero);
}

function mostrarCSV()
{
	global $BDstock;
	if (($fichero = fopen($BDstock, "r")) !== FALSE) 
	{
		while (($data = fgetcsv($fichero, 1000, ",")) !== FALSE) 
		{
			error_log($data[0] . " -- " . $data[1] . " -- " . $data[2] . " -- " . $data[3]);
		}
		fclose($fichero);
	}
}

?>