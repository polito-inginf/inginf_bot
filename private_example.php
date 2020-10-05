<?php
/**
* These are all the private variables that will be used for communicating
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

// Bot token
define('token', '');

// DB credentials
define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASSWD', '');
define('DB_NAME', '');
define('DB_PORT', 3306);

// Log channel chat_id
define('LOG_CHANNEL', 0);

// Temp admins array (update with SQL table)
define('ADMINS', [
	0
]);
