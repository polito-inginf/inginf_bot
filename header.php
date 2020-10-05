<?php
/**
* These are all the base functions that will be used for communicating
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
* @license		https://choosealicense.com/licenses/gpl-3.0/
*/

// Gets the incoming JSON update
$content = file_get_contents("php://input");

// Decodes update into an array
$update = json_decode($content, TRUE);

// Opens a MySQL connection. Credentials are stored in private.php file
$db_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWD, DB_NAME, DB_PORT);

// Checking if the connection is failed
if ($db_conn === FALSE) {
	exit(1);
}
