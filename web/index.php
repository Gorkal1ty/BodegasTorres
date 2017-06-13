<?php
header('Content-Type: application/json');
ob_start();

#Parametros Globales
$vino = 'Ninguno';
$nBotellas = 0;

#Parametros Ficticios (BD)
$stock = array( 'Celeste' => 10, 'Viña Esmeralda' => 10, 'Gran Coronas' => 10, 'Viña Sol' => 10);
$direccion = 'C/Luis Jorge Castaños, 23, 4º Dcha. 28999 Valdecillas de Jarama, Madrid';

#Obtener Info. Peticion
$json = file_get_contents('php://input'); 
$request = json_decode($json, true);
$action = $request['result']['action'];
$parameters = $request['result']['parameters'];

switch ($action) 
{
    case 'nuevo.consultarStock':
		#Parametros
		$vinos = array($parameters['vino']);
		#Bucle Parámetros 
		foreach ($vino as $vinos) {
			error_log('Petición: ' . $vino);
		}
		#$vino = $parameters['vino'][0];
		#$nbotellas = $parameters['nbotellas'][0];
		
		error_log('Petición: ' . $nbotellas . ' de ' . $vino);
		error_log($stock[$vino] . ' botellas en stock');
		
		#Consultar Stock
		if ($stock[$vino]<$nbotellas) 
		{
			$outputtext = 'Lo sentimos pero solamente nos quedan ' . $stock[$vino] . ' existencias de ' . $vino . ', ¿Las quiere?';
		} 
		else
		{
			$followupEvent = array('name'=>'consultarDireccion','data'=>array('nBotellas'=>$nbotellas, 'vino'=>$vino, 'direccion'=>$direccion));
		}
		$contextout = array(array('name'=>'nuevopedido', 'lifespan'=>5, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'direccion'=>$direccion)));
        $source = 'bodegastorres.php';
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