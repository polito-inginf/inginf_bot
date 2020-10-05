<?php
/**
* These are all the preliminary operation that will be used for communicating
* with Telegram Bot.
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
* Gets the incoming JSON update
*
* file_get_contents() Reads the file into a string
*/
$content = file_get_contents('php://input');

/**
* Decode the update
*
* json_decode() Convert the output to a PHP object
*/
$update = json_decode($content, TRUE);

/**
* Opens a MySQL connection.
* Credentials are stored in private.php file
*
* mysqli_connect() Open the connection with the database
*/
$dbConnection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWD, DB_NAME, DB_PORT);

// Checking if the connection is failed
if ($dbConnection === FALSE) {
	exit(1);
}
