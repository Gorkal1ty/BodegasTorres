<?php
header('Content-Type: application/json');
ob_start();

#Constantes
$BDstock = 'bd/stock.csv';
$BDpedidos = 'bd/pedidos.csv';

$FECHA_ENTREGA = '26/06/2017';			#Fecha estimada de entrega (MODIFICAR EN FUTURO)
$USUARIO = 'NTS';

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
#Funciones Stock
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

#Clase Pedido
class Pedido
{
	public $usuario;
	public $vino;
	public $unidades;
	public $completado;			#VARIABLE DEMO (GRAN CORONAS)
	public $coste;
	public $fecha_entrega;
	public $estado;
	
	public function __construct($u, $v, $uni, $comp, $cost, $f, $e)
	{
        $this->usuario = $u;
		$this->vino = $v;
		$this->unidades = $uni;
		$this->completado = $comp;
		$this->coste = $cost;
		$this->fecha_entrega = $f;
		$this->estado = $e;
    }
}

#Obtener Info. Peticion
$json = file_get_contents('php://input'); 
$request = json_decode($json, true);
$action = $request['result']['action'];
$parameters = $request['result']['parameters'];

#Obtener CSV Stock
if (($fichero = fopen($BDstock, 'r')) !== FALSE) 
{
	while (($data = fgetcsv($fichero, 1000, ',')) !== FALSE) 
	{
		$arrayStock[] = new Stock($data[0], $data[1], $data[2], $data[3]);
	}
	fclose($fichero);
}
#Obtener CSV Pedidos
if (($fichero = fopen($BDpedidos, 'r')) !== FALSE) 
{
	while (($data = fgetcsv($fichero, 1000, ',')) !== FALSE) 
	{
		$arrayPedidos[] = new Pedido($data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6]);
	}
	fclose($fichero);
}

#LOG Stock
error_log('STOCK');
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
			$outputtext = 'Lo sentimos pero no nos quedan existencias de ' . $vino . ', Le recomendamos un vino similar como es el Gran Coronas. Disponemos de las ' . $nbotellas . ' botellas y tu precio sería de ' . obtenerPrecio($nbotellas, 'Gran Coronas', $arrayStock) . '€. ¿Las quieres?';
			$contextout = array(array('name'=>'consultarCambio', 'lifespan'=>2, 'parameters'=>array('vino'=>'Gran Coronas', 'nBotellas'=>$nbotellas)));
		}
		else if ($stock<$nbotellas) 
		{
			#STOCK INSUFICIENTE > Completar Pedido o Sustituir por Gran Coronas
			$outputtext = 'Lo sentimos pero solamente nos quedan ' . $stock . ' existencias de ' . $vino . '. Te recomendamos un vino similar como es el Gran Coronas. Puedes COMPLETAR el pedido con ' . ($nbotellas - $stock) . ' unidades o SUSTITUIRLO con ' . $nbotellas . ' botellas.';
			$contextout = array(array('name'=>'consultarAlternativa', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas)));
		} 
		else
		{
			$coste = obtenerPrecio($nbotellas, $vino, $arrayStock);
			#STOCK OK
			$outputtext = 'Perfecto, tenemos las ' . $nbotellas . ' botellas de ' . $vino . ' en stock. Tu precio será de ' . $coste . '€ ¿Estás de acuerdo?';
			$contextout = array(array('name'=>'confirmacionPedido', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'coste' => $coste)));
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
		$coste = obtenerPrecio($nbotellas, $vino, $arrayStock) + obtenerPrecio($completar, 'Gran Coronas', $arrayStock);
		
		$outputtext = 'Perfecto, entonces serán ' . $nbotellas . ' botellas de ' . $vino . ' junto con ' . $completar . ' de Gran Coronas. Tu precio total es de ' . $coste . '€ ¿Estás de acuerdo?';
		$contextout = array(array('name'=>'confirmacionPedido', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'completar' => $completar, 'coste' => $coste)));
        break;
		
	#-------- CAMBIAR PEDIDO------------ Cambia las botellas del vino cuyo stock es insuficiente por Gran Coronas (recomendación)
	case 'nuevo.cambiarPedido':
		error_log('ACCION = CAMBIAR PEDIDO');
		#Parametros
		$vino = $parameters['vino'];
		$nbotellas = $parameters['nbotellas'];
		$coste = obtenerPrecio($nbotellas, 'Gran Coronas', $arrayStock);
		$outputtext = 'Perfecto, entonces serán ' . $nbotellas . ' botellas de Gran Coronas y tu precio queda en ' . $coste . '€. ¿Todo bien?';
		$contextout = array(array('name'=>'confirmacionPedido', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'completar' => 0, 'coste' => $coste)));
		break;
		
	#------CONFIRMAR PEDIDO --------- Almacena el pedido (actualiza bbddd), muestra fecha entrega y se despide
	case 'nuevo.confirmarPedido':
        error_log('ACCION = CONFIRMAR PEDIDO');
		#Parametros
		$vino = $parameters['vino'];
		$nbotellas = $parameters['nbotellas'];
		$completar = $parameters['completar'];
		$coste = $parameters['coste'];
		
		#LOG
		error_log($vino . ' = ' . $nbotellas . ' unidades');
		if($completar!=0)
		{
			error_log('Gran Coronas = ' . $completar . ' unidades');
		}
		
		#Actualizar Array Stock
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
		
		#Actualizar Array Pedidos
		$arrayPedidos[] = new Pedido($USUARIO, $vino, $nbotellas, $completar, $coste, $FECHA_ENTREGA, 'EN PREPARACION');
		
		#Actualizar CSVs
		actualizarStock($arrayStock);
		actualizarPedidos($arrayPedidos);
		
		#Comprobar de Nuevo
		mostrarCSV();
		
		#Mensaje (Text Response automático de API.AI no se envía en Twitter ¿?)
		$outputtext = 'Hecho, hemos registrado tu pedido. Llegará el ' . $FECHA_ENTREGA . '. Puedes consultarnos su estado cuando quieras. ¡Gracias!';
        break;
		
	#-------- CONSULTAR PEDIDOS------------ Redacta breve resumen de los pedidos pendientes				
	case 'consulta.Pedidos':
        error_log('ACCION = CONSULTAR PEDIDOS');
		#Listar Pedidos
		$contPedidos=0;
		foreach ($arrayPedidos as $Pedido)
		{
			if($Pedido->usuario==$USUARIO and $Pedido->estado!='ENTREGADO')
			{
				$infoPedidos .= $Pedido->unidades . ' x ' . $Pedido->vino . ' = ' . $Pedido->coste . '€ --> ' . $Pedido->estado . ' (' . $Pedido->fecha_entrega . ')               ';#SALTO LINEA?!?!?!?!?!
				$contPedidos++;
			}
		}
		#Mostrar
		if($contPedidos>0)
		{
			$outputtext = 'Aquí tienes los detalles: ';
			$outputtext .= $infoPedidos;
		}
		else
		{
			$outputtext = 'No parece que tengas ningún pedido pendiente. Si no has recibido un envío, por favor ponte en contacto con nosotros en ...';
		}

		break;
		
	#-------- CONSULTAR CATALOGO------------ Redacta breve resumen de los vinos con su tipo y precio	
	case 'consulta.Catalogo':
		error_log('ACCION = CONSULTAR CATALOGO');
		#Listar Vinos
		foreach ($arrayStock as $Stock)
		{
			if($Stock->stock>0)
			{
				$infoCatalogo .= $Stock->nombre . '(' . $Stock->tipo . ') = ' . $Stock->precio . '€    ||    ';  #SALTO LINEA?!?!?!?!?!
			}
		}
		#Mostrar
		$outputtext = 'Puedes pedir alguno de nuestros vinos más exitosos: ';
		$outputtext .= $infoCatalogo;
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


function actualizarStock($array)
{
	global $BDstock;
	error_log('ACTUALIZANDO STOCK');
	$fichero = fopen($BDstock, 'w');
	foreach($array as &$Stock)
	{
		#Conversión de Objeto (Stock) a Array
		$fila = (array)$Stock;
		fputcsv($fichero, $fila);
	}
	fclose($fichero);
}

function actualizarPedidos($array)
{
	global $BDpedidos;
	error_log('ACTUALIZANDO PEDIDOS');
	$fichero = fopen($BDpedidos, 'w');
	foreach($array as &$Pedido)
	{
		#Conversión de Objeto (Stock) a Array
		$fila = (array)$Pedido;
		fputcsv($fichero, $fila);
	}
	fclose($fichero);
}

function mostrarCSV()
{
	global $BDstock;
	global $BDpedidos;
	error_log('TABLA STOCK');
	if (($fichero = fopen($BDstock, 'r')) !== FALSE) 
	{
		while (($data = fgetcsv($fichero, 1000, ',')) !== FALSE) 
		{
			error_log($data[0] . '(' . $data[1] . ') = ' . $data[2] . '€ - ' . $data[3] . 'u');
		}
		fclose($fichero);
	}
	error_log('TABLA PEDIDOS');
	if (($fichero = fopen($BDpedidos, 'r')) !== FALSE) 
	{
		while (($data = fgetcsv($fichero, 1000, ',')) !== FALSE) 
		{
			error_log($data[0] . ' = ' . $data[1] . ' = ' . $data[2] . 'u + ' . $data[3] . ' = ' . $data[4] . '€ --> ' . $data[5]);
		}
		fclose($fichero);
	}
}

?>