<?php
function filterLog($text) {
	// Change to REMOTE_ADDR if not using CLoudFlare
	if ($_SERVER['HTTP_X_FORWARDED_FOR'] == "iptolog") {
		file_put_contents("log.txt", $text, FILE_APPEND);
	}
}

function log_error($text) {
	file_put_contents("log-error.txt", $text . "\n", FILE_APPEND);
}

// Get the raw SOAP request
$Request = file_get_contents("php://input");

filterLog( print_r($_SERVER, true) );
filterLog( file_get_contents("php://input") );

function proxyNUSETicketRequest($ETicketRequest) {
	define('NUSUrl', "https://nus.c.shop.nintendowifi.net/nus/services/NetUpdateSOAP");
	define('ECSUrl', "https://ecs.c.shop.nintendowifi.net/ecs/services/ECommerceSOAP");

	define('CTRCommonCert', "ctrcert.pem");

	$ch = curl_init();

	$curlopt = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_USERAGENT => 'CTR NUP 040600 Mar 14 2012 13:32:39',
		CURLOPT_URL => NUSUrl,
		CURLOPT_SSLCERT => CTRCommonCert,
		CURLOPT_POST => 1,
		CURLOPT_POSTFIELDS => $ETicketRequest,
		CURLOPT_HTTPHEADER => ["SOAPAction: urn:nus.wsapi.broadon.com/GetSystemCommonETicket"]
	);

	curl_setopt_array($ch, $curlopt);

	$response = curl_exec($ch);

	if ( ! $response ) {
		// This should not contain personally identifiable information
		log_error(curl_error($ch));
	} else {
		echo $response;
	}	

}

if( isset($_SERVER['HTTP_SOAPACTION']) ) {
	header("Content-Type: text/xml");

	switch($_SERVER['HTTP_SOAPACTION']) {
		case "urn:nus.wsapi.broadon.com/GetSystemUpdate":
			filterLog("GetSystemUpdate");
			readfile("titleversion.xml");
			break;

		case "urn:nus.wsapi.broadon.com/GetSystemTitleHash":
			filterLog("GetSystemTitleHash");
			readfile("titlehash.xml");
			break;

		case "urn:ecs.wsapi.broadon.com/GetAccountStatus":
			filterLog("GetAccountStatus");
			readfile("getaccountstatus.xml");
			break;

		case "urn:nus.wsapi.broadon.com/GetSystemCommonETicket":
			filterLog("GetSystemCommonETicket");
			proxyNUSETicketRequest($Request);
			break;

		default:
			log_error("Unexpected method called: {$_SERVER['HTTP_SOAPACTION']}");
	}
} else {
	echo "This page meant to be accessed by 3DS.";
}
