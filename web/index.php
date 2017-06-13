<?php
header('Content-Type: application/json');
ob_start();

#Parametros Ficticios (BD)
$stock = array( 'Celeste' => 10, 'Viña Esmeralda' => 10, 'Gran Coronas' => 10, 'Viña Sol' => 10);
$direccion = 'C/Luis Jorge Castaños, 23, 4º Dcha. 28999 Valdecillas de Jarama, Madrid';

#Clase Pedido
class Pedido
{
	public $vino;
	public $unidades;
	public $stock;		#OK en caso bueno
	#public $coste;
	#public $estado;
	
	public function __construct($v, $u, $s) #$c, $e
	{
        $this->vino = $v;
		$this->unidades = $u;
		$this->stock = $s;
		#$this->coste = $c;
		#$this->estado = $e;
    }
}

#Coleccion Global
$pedidos[] = array();

#Obtener Info. Peticion
$json = file_get_contents('php://input'); 
$request = json_decode($json, true);
$action = $request['result']['action'];
$parameters = $request['result']['parameters'];

switch ($action) 
{
	#------------------------------- Consultar Stock --------------------------
    case 'nuevo.consultarStock':
		#Variable Global
		global $pedidos;
		#Parametros
		$vinos = array($parameters['vino']);
		$nbotellas = array($parameters['nbotellas']);
		#Recorrer Vinos > #Generar Pedido (key > vino)
		for ($i = 0; $i <= count($vinos); $i++) 
		{
			$pedidos[] = new Pedido($vinos[0][$i], $nbotellas[0][$i], '');
		}
		#Comprobar Stock
		$stockTodos = 'OK';
		foreach ($pedidos as &$Pedido)
		{
			error_log('PEDIDO = ' . $Pedido->vino . ' -> ' . $Pedido->unidades);
			if ($stock[$Pedido->vino] >= $Pedido->unidades)
			{
				#Existe Stock
				$Pedido->stock = 'OK';
			} 
			else
			{
				$stockTodos = '';
			}
		}
		if($stockTodos == 'OK')
		{
			#TODOS STOCK OK -> Consultar Direccion
			$followupEvent = array('name'=>'consultarDireccion','data'=>array('nBotellas'=>$nbotellas, 'vino'=>$vino, 'direccion'=>$direccion));
			$contextout = array(array('name'=>'nuevopedido', 'lifespan'=>3, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'direccion'=>$direccion)));
		}
		else
		{
			foreach ($pedidos as &$Pedido)
			{
				#Localizar pedido sin Stock y consultar
				if ($Pedido->stock != 'OK')
				{
					$outputtext = 'Lo sentimos pero solamente nos quedan ' . $stock[$Pedido->vino] . ' botellas de ' . $Pedido->vino . '. Le recomendamos un vino similar como es el Gran Coronas. Puede completar el pedido con ' . ($Pedido->unidades - $stock[$Pedido->vino]) . ' unidades o sustituirlo por completo con ' . $Pedido->unidades . ' botellas.';
					$contextout = array(array('name'=>'consultarAlternativa', 'lifespan'=>2, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'direccion'=>$direccion)));
				}
			}
		}		
        $source = 'bodegastorres.php';
		break;
    #------------------------------- Confirmar Direccion --------------------------
	case 'nuevo.completarPedido':
		#Variable Global
		global $pedidos;
		error_log('PEDIDO COMPLETADO');
		$outputtext = "¡Perfecto! Le adjunto un resumen del pedido: ...";
		#$contextout = array(array('name'=>'resumen', 'lifespan'=>3, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'direccion'=>$direccion)));
		foreach ($pedidos as &$Pedido)
		{
			#$outputtext = $outputtext . $Pedido->unidades . ' x ' . $Pedido-vino . ' = ' . ' X €';
		}
		#$outputtext = $outputtext . '             Total = X €';
		break;
	case 'nuevo.confirmarDireccion':
        error_log('Confirmar Direccion');
        break;
}

#Devolver JSON
$output['speech'] = $outputtext;
$output['displayText'] = $outputtext;
$output['contextOut'] = $contextout;
$output['source'] = $source;
$output['followupEvent'] = $followupEvent;
echo json_encode($output);
ob_end_clean();
echo json_encode($output);
?>