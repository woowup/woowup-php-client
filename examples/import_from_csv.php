<?php
require_once '../vendor/autoload.php';

$apikey = 'APIKEY';
$sales = './ventas.csv';

/**
 * Este script toma las ventas desde un archivo csv, en donde se tiene una fila por cada item comprado.
 *
 * Los pasos de la importación son:
 * 		1) Se recorre el archivo y se importan los clientes
 * 		2) Se recorre el archivo, se van armando las ventas (una venta puede estar compuesta por mas de una fila) y se arma la
 * 			estructura de datos de la venta que entiende la API de WoowUp
 */

define('DUPLICATED_PURCHASE', 'duplicated_purchase_number');
define('INTERNAL_ERROR', 'internal_error');

$woowup = new \WoowUp\Client($apikey);
$imported = [];

/**
 * Primero iteramos todo el csv para ir cargando los clientes
 */
logMessage("Importación de clientes");

if (($fh = fopen($sales, "r")) !== false) {
	while (($row = fgetcsv($fh)) !== false) {
		$email = $row[15];
		if (validEmail($email)) {
			if (in_array($email, $imported)) continue;

			$parts = explode(' ', $row[13]);

			$user = [
				'service_uid' => $email,
				'email' => $email,
				'first_name' => isset($parts[0]) ? $parts[0] : '',
				'last_name' => isset($parts[1]) ? $parts[1] : ''
			];

			try {
				if (!$woowup->users->exist($email)) {
					$response = $woowup->users->create($user);

					logMessage("creado: {$email}");
				} else {
					logMessage("existente: {$email}");
				}

				$imported[] = $email;
			} catch (\GuzzleHttp\Exception\RequestException $e) {
				$response = json_decode($e->getResponse()->getBody());

				if ($response->code == INTERNAL_ERROR && $response->message == 'usuario existente') {
					continue;
				} else {
					logMessage($e->getResponse()->getBody());
					die();
				}
			}
		}
	}

	fclose($fh);
}

logMessage("Importación de ventas");

if (($fh = fopen($sales, "r")) !== false) {

	$invoice_number = null;
	while (($row = fgetcsv($fh)) !== false) {
		$email = $row[15];

		if (!validEmail($email)) {
			continue;
		}

		if (!is_null($invoice_number) && $invoice_number != $row[2]) {
			$order = buildOrder($orders);

			try {
				$response = $woowup->purchases->create($order);
				$orders = [];

				logMessage("creada: {$row[2]}");
			} catch (\GuzzleHttp\Exception\RequestException $e) {
				$response = json_decode($e->getResponse()->getBody());
				if ($response->code == DUPLICATED_PURCHASE) {
					logMessage("duplicada: {$row[2]}");
				} else {
					logMessage($e->getResponse()->getBody());
					die();
				}
			}
		}

		$orders[] = $row;
		$invoice_number = $row[2];
	}

	fclose($fh);
}

/**
 * Chequea si un email es válido
 *
 * @param  string $email
 * @return bool
 */
function validEmail($email) {
	return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Transforma las filas del csv que representan a la venta en el formato aceptado por la api
 * de WoowUp.
 *
 * @param  Array $orders Listado de ventas
 * @return Array]
 */
function buildOrder($orders) {
	$order = [
		"service_uid" => $orders[0][15],
		"points" => 0,
		"invoice_number" => $orders[0][2],
		"purchase_detail" => [],
		"prices" => [],
		"branch_name" => $orders[0][0],
		"createtime" => date('Y-m-d H:i:s')
	];

	$total = 0;
	$discount = 0;
	foreach ($orders as $o) {
		$order['purchase_detail'][] = [
			"sku" => $o[3],
			"product_name" => $o[4],
			"category" => [$o[5], $o[6]],
			"quantity" => (int) abs($o[16]),
			"unit_price" => (float) $o[17],
			"variations" => [
				['name' => 'Linea', 'value' => $o[5]],
				['name' => 'Color', 'value' => $o[10]],
				['name' => 'Talle', 'value' => $o[11]],
			]
		];

		$total += (int) abs($o[16]) * (float) $o[17];
		$discount += abs($o[19]);
	}

	$order['prices'] = [
		"gross" => $total,
		"discount" => $discount,
		"total" => $total - $discount
	];

	return $order;
}

/**
 * Dummy log de info
 * @param  string $message Mensaje a loguear
 * @return void
 */
function logMessage($message) {
	echo date('Y-m-d H:i:s').": $message\n";
}

?>