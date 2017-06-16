<?php
header('Content-Type: application/json');
ob_start();

#Constantes
$BDstock = 'bd/stock.csv';
$BDpedidos = 'bd/pedidos.csv';

$FECHA_ENTREGA = '26/06/2017';			#Fecha estimada de entrega (MODIFICAR EN FUTURO)

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

function obtenerPrecio($u, $n, $lista)
{
	foreach ($lista as &$Stock)
	{
		if($Stock->nombre==$n)
		{
			return $Stock->precio * $u;
		}
	}
}

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
#Obtener CSV Pedidos
if (($fichero = fopen($BDpedidos, "r")) !== FALSE) 
{
	while (($data = fgetcsv($fichero, 1000, ",")) !== FALSE) 
	{
		$arrayPedidos[] = new Stock($data[0], $data[1], $data[2], $data[3]);
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
	#--------CONSULTAR STOCK------------ Consulta el Stock para el nuevo pedido
    case 'nuevo.consultarStock':
		error_log('ACCION = CONSULTAR STOCK');
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
			$outputtext = 'Lo sentimos pero no nos quedan existencias de ' . $vino . ', Le recomendamos un vino similar como es el Gran Coronas. Disponemos de las ' . $nbotellas . ' botellas y tu precio sería de ' . obtenerPrecio($completar, 'Gran Coronas', $arrayStock) . '€. ¿Las quieres?';
			$contextout = array(array('name'=>'consultarCambio', 'lifespan'=>2, 'parameters'=>array('vino'=>'Gran Coronas', 'nBotellas'=>$nbotellas)));
		}
		else if ($stock<$nbotellas) 
		{
			#STOCK INSUFICIENTE > Completar Pedido o Sustituir por Gran Coronas
			$outputtext = 'Lo sentimos pero solamente nos quedan ' . $stock . ' existencias de ' . $vino . '. Te recomendamos un vino similar como es el Gran Coronas. Puedes COPMLETAR el pedido con ' . ($nbotellas - $stock) . ' unidades o SUSTITUIRLO con ' . $nbotellas . ' botellas.';
			$contextout = array(array('name'=>'consultarAlternativa', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas)));
		} 
		else
		{
			#STOCK OK
			$outputtext = 'Perfecto, tenemos las ' . $nbotellas . ' botellas de ' . $vino . ' en stock. Tu precio será de ' . obtenerPrecio($nbotellas, $vino, $arrayStock) . '€ ¿Estás de acuerdo?';
			$contextout = array(array('name'=>'confirmacionPedido', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas)));
		}
		break;
		
	#--------COMPLETAR PEDIDO------------ Completa un Pedido cuyo Stock no es suficiente con botellas de Gran Coronas
    case 'nuevo.completarPedido':
	    error_log('ACCION = COMPLETAR PEDIDO');
		#Parametros
		$vino = $parameters['vino'];
		$stock = obtenerStock($vino, $arrayStock);
		$nbotellas = $stock;
		$completar = $parameters['nbotellas'] - $stock;
		
		$outputtext = 'Perfecto, entonces serán ' . $nbotellas . ' botellas de ' . $vino . ' junto con ' . $completar . ' de Gran Coronas. Tu precio total es de ' . obtenerPrecio($nbotellas, $vino, $arrayStock) + obtenerPrecio($completar, 'Gran Coronas', $arrayStock) . '€ ¿Estás de acuerdo?';
		$contextout = array(array('name'=>'confirmacionPedido', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'completar' => $completar)));
        break;
		
	#-------- CAMBIAR PEDIDO------------ Cambia las botellas del vino cuyo stock es insuficiente por Gran Coronas (recomendación)
	case 'nuevo.cambiarPedido':
		error_log('ACCION = CAMBIAR PEDIDO');
		#Parametros
		$vino = $parameters['vino'];
		$nbotellas = $parameters['nbotellas'];
		$outputtext = 'Perfecto, entonces serán ' . $nbotellas . ' botellas de Gran Coronas y tu precio queda en ' . obtenerPrecio($completar, 'Gran Coronas', $arrayStock) . '€. ¿Todo bien?';
		$contextout = array(array('name'=>'confirmacionPedido', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'completar' => 0)));
		break;
		
	#------CONFIRMAR DIRECCION --------- Almacena el pedido (actualiza bbddd) y se despide
	case 'nuevo.confirmarPedido':
        error_log('ACCION = CONFIRMAR PEDIDO');
		#Parametros
		$vino = $parameters['vino'];
		$nbotellas = $parameters['nbotellas'];
		$completar = $parameters['completar'];
		
		#Almacenar Pedido
		error_log($vino . ' = ' . $nbotellas . ' unidades');
		if($completar!=0)
		{
			error_log('Gran Coronas = ' . $completar . ' unidades');
		}
		
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
		
		#Actualizar CSVs
		actualizarCSV($BDstock, $arrayStock);
		actualizarCSV($BDpedidos, $arrayPedidos);
		
		#Comprobar de Nuevo
		mostrarCSV();
		
		#Mensaje (Text Response automático de API.AI no se envía en Twitter ¿?)
		$outputtext = 'Perfecto, hemos registrado tu pedido. Llegará el ' . $FECHA_ENTREGA . '. Gracias.';
        break;
		
	#-------- CONSULTAR PEDIDOS------------ Redacta breve resumen de los pedidos pendientes				PENDIENTE
	case 'consulta.Pedidos':
        error_log('ACCION = CONSULTAR PEDIDOS');
		

		break;
		
	#-------- CONSULTAR CATALOGO------------ Redacta breve resumen de los vinos con su tipo y precio	PENDIENTE
	case 'consulta.Catalogo':
		error_log('ACCION = CONSULTAR CATALOGO');
		
		
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


function actualizarCSV($csv, $array)
{
	error_log('ACTUALIZANDO CSV');
	$fichero = fopen($csv, 'w');
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
	global $BDpedidos;
	error_log("TABLA STOCK");
	if (($fichero = fopen($BDstock, "r")) !== FALSE) 
	{
		while (($data = fgetcsv($fichero, 1000, ",")) !== FALSE) 
		{
			error_log($data[0] . "(" . $data[1] . ") = " . $data[2] . "€ - " . $data[3] . 'u');
		}
		fclose($fichero);
	}
	error_log("TABLA PEDIDOS");
	if (($fichero = fopen($BDpedidos, "r")) !== FALSE) 
	{
		while (($data = fgetcsv($fichero, 1000, ",")) !== FALSE) 
		{
			error_log($data[0] . " = " . $data[1] . " = " . $data[2] . "u + " . $data[3] . ' = ' . $data[4] . '€ --> ' . $data[5]);
		}
		fclose($fichero);
	}
}

?>