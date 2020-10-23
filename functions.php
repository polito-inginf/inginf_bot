<?php
/**
* These are all the functions that will be used into the Bot's code.
*
* No libraries are used in this project.
*
* @author		Giorgio Pais
* @author		Giulio Coa
* @author		Simone Cosimo
* @author		Luca Zaccaria
* @author		Alessio Bincoletto
* @author		Marco Smorti
*
* @copyright	2020- Giorgio Pais <info@politoinginf.it>
*
* @license		https://choosealicense.com/licenses/lgpl-3.0/
*/

/**
* Execute an HTTP(S) request.
*
* @param string $urlt The HTTP(S) endpoint.
*
* @return mixed The result of the request.
*/
function request(string $url) {
	/**
	* Encode the URL
	*
	* urlencode() Encode the URL, converting all the special character to its safe value
	*/
	$url = urlencode($url);

	/**
	* Open the cURL session
	*
	* curl_init() Open the session
	*/
	$curlSession = curl_init($url);

	/**
	* Set the cURL session
	*
	* curl_setopt_array() Set the options for the session
	*/
	curl_setopt_array($curlSession, [
		CURLOPT_HEADER => FALSE,
		CURLOPT_RETURNTRANSFER => TRUE
	]);

	/**
	* Exec the request
	*
	* curl_exec() Execute the session
	*/
	$result = curl_exec($curlSession);

	/**
	* Close the cURL session
	*
	* curl_close() Close the session
	*/
	curl_close($curlSession);
	return $result;
}
