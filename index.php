<?php
/**
* This is the core of the bot.
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

// This file includes all the constants needed by the bot
include 'constants.php';

// This file contains all the preliminary operation needed by the bot
include 'header.php';

// This file contains all the base functions needed by the bot
include 'basefunctions.php';

// This file contains all the variables from the various update types
include 'variables.php';

/**
* Checks if user is an admin, otherwise exits
*
* @todo Update with a better solution (query)
* @todo Fix include order
* @todo Create one or more functions to check if a user is admin and related permissions
*
* in_array() Checks if a value exists in an array
*/
if (in_array($userId, ADMINS) === FALSE) {
	exit(1);
}

// This file contains logger code and variables
include 'logger.php';

/**
* Checking if is an @admin tag
*
* preg_match() perform a RegEx match
*/
if (preg_match('/^@admin([[:blank:]\n]((\n|.)*))?$/miu', $text, $matches)) {
	$textWithoutTag = $matches[2] ?? NULL;
/**
* Checking if is a bot command
*
* preg_match() perform a RegEx match
*/
} else if (preg_match('/^\/([[:alnum:]@]+)[[:blank:]]?([[:alnum:]]|[^\n]+)?$/miu', $text, $matches)) {
	/**
	* Retrieving the command
	*
	* explode() convert a string into an array
	*/
	$command = explode('@', $matches[1])[0];
	$args = $matches[2] ?? NULL;

	switch ($command) {
		case 'start':
			sendMessage($userId, 'Hi!');
			break;
	}
}

exit(0);

