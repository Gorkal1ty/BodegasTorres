<?php
header('Content-Type: application/json');
ob_start();

#Parametros Ficticios (BD)
$stock = array( 'Celeste' => 10, 'Viña Esmeralda' => 10, 'Gran Coronas' => 10, 'Viña Sol' => 10);
$direccion = 'C/Luis Jorge Castaños, 23, 4º Dcha. 28999 Valdecillas de Jarama, Madrid';

#Obtener Info. Peticion
$json = file_get_contents('php://input'); 
$request = json_decode($json, true);
$action = $request['result']['action'];
$parameters = $request['result']['parameters'];

error_log($parameters);

switch ($action) 
{
    case 'nuevo.consultarStock':
		error_log('ACCION = Consultar Stock');
		#Parametros
		$vino = $parameters['vino'];
		$nbotellas = $parameters['nbotellas'];
		
		error_log('Petición: ' . $nbotellas . ' de ' . $vino);
		error_log($stock[$vino] . ' botellas en stock');
		
		#Consultar Stock
		if ($stock[$vino]<$nbotellas) 
		{
			$outputtext = 'Lo sentimos pero solamente nos quedan ' . $stock[$vino] . ' existencias de ' . $vino . ', Le recomendamos un vino similar como es el Gran Coronas. Puede completar el pedido con ' . ($nbotellas - $stock[$vino]) . ' unidades o sustituirlo por completo con ' . $nbotellas . ' botellas.';
			$contextout = array(array('name'=>'consultarAlternativa', 'lifespan'=>3, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas)));;
		} 
		else
		{
			#$followupEvent = array('name'=>'consultarDireccion','data'=>array('nBotellas'=>$nbotellas, 'vino'=>$vino, 'direccion'=>$direccion));
			#$contextout = array(array('name'=>'nuevopedido', 'lifespan'=>5, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'direccion'=>$direccion)));
			$outputtext = 'Perfecto, tenemos las ' . $nbotellas . ' botellas de ' . $vino . ' en stock. ¿Es ésta su dirección? = ' . $direccion;
			$contextout = array(array('name'=>'consultaDireccion', 'lifespan'=>3, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'direccion'=>$direccion)));
		}
        $source = 'bodegastorres.php';
		break;
    case 'nuevo.completarPedido':
	    error_log('ACCION = Completar Pedido');
		#Parametros
		$vino = $parameters['vino'];
		$nbotellas = $stock[$vino];
		$completar = $parameters['nbotellas'] - $stock[$vino];
		
		$outputtext = 'Perfecto, entonces serán ' . $nbotellas . ' botellas de ' . $vino . ' junto con ' . $completar . ' de Gran Coronas. ¿Es ésta su dirección? = ' . $direccion;
		$contextout = array(array('name'=>'consultaDireccion', 'lifespan'=>3, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'completar' => $completar, 'direccion'=>$direccion)));

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