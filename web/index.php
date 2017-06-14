<?php
header('Content-Type: application/json');
ob_start();

#Constantes
$VINOS = 5;
$CSV = 'stock.csv';

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

#Parametros Ficticios (BD)
$direccion = 'C/Luis Jorge Castaños, 23, 4º Dcha. 28999 Valdecillas de Jarama, Madrid';

#Obtener Info. Peticion
$json = file_get_contents('php://input'); 
$request = json_decode($json, true);
$action = $request['result']['action'];
$parameters = $request['result']['parameters'];

#Obtener CSV Stock
$contenido = file_get_contents($CSV);
$filas = array_map("str_getcsv", explode("\n", $contenido));
for($i=1;$i<=$VINOS;$i++)
{
	$columnas = array(explode(';', $filas[$i][0]));
	$arrayStock[] = new Stock($columnas[0][0], $columnas[0][1], $columnas[0][2], $columnas[0][3]);
}

#LOG Stock
error_log("STOCK");
for($i=0;$i<$VINOS;$i++)
{
	error_log($arrayStock[$i]->nombre . ' (' . $arrayStock[$i]->tipo . ') = ' . $arrayStock[$i]->precio . '€ - ' . $arrayStock[$i]->stock . ' en Stock');
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
		if ($stock<$nbotellas) 
		{
			$outputtext = 'Lo sentimos pero solamente nos quedan ' . $stock . ' existencias de ' . $vino . ', Le recomendamos un vino similar como es el Gran Coronas. Puede completar el pedido con ' . ($nbotellas - $stock) . ' unidades o sustituirlo por completo con ' . $nbotellas . ' botellas.';
			$contextout = array(array('name'=>'consultarAlternativa', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas)));;
		} 
		else
		{
			#$followupEvent = array('name'=>'consultarDireccion','data'=>array('nBotellas'=>$nbotellas, 'vino'=>$vino, 'direccion'=>$direccion));
			#$contextout = array(array('name'=>'nuevopedido', 'lifespan'=>5, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'direccion'=>$direccion)));
			$outputtext = 'Perfecto, tenemos las ' . $nbotellas . ' botellas de ' . $vino . ' en stock. ¿Es ésta su dirección? = ' . $direccion;
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

		$outputtext = 'Perfecto, entonces serán ' . $nbotellas . ' botellas de ' . $vino . ' junto con ' . $completar . ' de Gran Coronas. ¿Es ésta su dirección? = ' . $direccion;
		$contextout = array(array('name'=>'consultaDireccion', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'completar' => $completar, 'direccion'=>$direccion)));

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
		
		#Calcular Stock
		$nuevoStock = obtenerStock($vino, $arrayStock) - $nbotellas;
		#Actualizar CSV
		actualizarStock($vino, $nuevoStock);
		
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


function actualizarStock($vino, $nuevoStock)
{
	error_log('ACTUALIZANDO STOCK: ' . $vino . ' = ' . $nuevoStock);
	global $CSV;
	global $VINOS;
	$fp = fopen($CSV, 'w');
	$contenido = file_get_contents($CSV);
	$filas = array_map("str_getcsv", explode("\n", $contenido));
	for($i=1;$i<=$VINOS;$i++)
	{
		$columnas = array(explode(';', $filas[$i][0]));
		if($columnas[0][0]==$vino)
		{
			$columnas[0][3] = $nuevoStock;
		}
		fputcsv($fp, $columnas);
	}
}

?>