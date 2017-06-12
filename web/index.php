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

echo $parameters;

switch ($action) 
{
    case 'nuevo.consultarStock':
		#Parametros
		$vino = $parameters['vino'];
		$nbotellas = $parameters['nbotellas'];

		echo 'Petición: ' . $nbotellas . ' de ' . $vino;
		echo $stock[vino] . ' botellas en stock';
		
		if ($stock[vino] < $nbotellas) 
		{
			$outputtext = 'Lo sentimos pero solamente nos quedan ' . $stock[vino] . ' existencias de ' . $vino . ', ¿Las quiere?';
			$contextout = array(array('name'=>'nuevopedido', 'lifespan'=>5, 'parameters'=>array('vino'=>$vino, 'nBotellas'=>$nbotellas, 'direccion'=>$direccion,)));
			$source = 'bodegastorres.php';
		} 
		else
		{
			#return 
			#{
				#'contextOut': [{'name':'nuevopedido', 'lifespan':5, 'parameters':{'vino': vino, 'nBotellas':nbotellas, 'direccion':direccion}}],
				#'source': 'BodegaTorres',
				#'followupEvent':{'name':'consultarDireccion','data':{'nBotellas':nbotellas, 'vino':vino, 'direccion': direccion}}
			#}
		}
        break;
    case 'nuevo.confirmarDireccion':
        echo 'Confirmar Direccion';
        break;
}

$output['contextOut'] = $contextout;
$output['speech'] = $outputtext;
$output['displayText'] = $outputtext;
$output['source'] = $source;
ob_end_clean();
echo json_encode($output);
?>