<?php
/**
* This section of the code defines all required variables
* by getting information from Telegram Bot's update.
*
* @todo Adapt to our needs
* @todo Not the best way to proceed, use another method
* @todo Check for undefined variables with empty()
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

/**
* Check if the message has a text
*
* empty() check if the argument is empty
* 	''
* 	""
* 	'0'
* 	"0"
* 	0
* 	0.0
* 	NULL
* 	FALSE
* 	[]
* 	array()
*/
$text = empty($update['message']['text']) === FALSE ? trim($update['message']['text']) : '';
$callbackData = $update['callback_query']['data'];
$messageId = $update['callback_query']['message']['message_id'];
$callbackId = $update['callback_query']['id'];
$inlineQueryId = $update['inline_query']['id'];
$inlineQuery = $update['inline_query']['query'];

/**
* Check if the message is a CallbackQuery
*
* empty() check if the argument is empty
* 	''
* 	""
* 	'0'
* 	"0"
* 	0
* 	0.0
* 	NULL
* 	FALSE
* 	[]
* 	array()
*/
if (empty($update['callback_query']) === FALSE) {
	$user = $update['callback_query']['from'];
/**
* Check if the message is a InlineQuery
*
* empty() check if the argument is empty
* 	''
* 	""
* 	'0'
* 	"0"
* 	0
* 	0.0
* 	NULL
* 	FALSE
* 	[]
* 	array()
*/
} else if (empty($update['inline_query']) === FALSE) {
	$user = $update['inline_query']['from'];
/**
* Check if the message is a text message
*
* empty() check if the argument is empty
* 	''
* 	""
* 	'0'
* 	"0"
* 	0
* 	0.0
* 	NULL
* 	FALSE
* 	[]
* 	array()
*/
} else if (empty($update['message']) === FALSE) {
	$user = $update['message']['from'];
}

$userId = $user['id'];
$firstName = $user['first_name'];
/**
* Check if the user has a last name
*
* empty() check if the argument is empty
* 	''
* 	""
* 	'0'
* 	"0"
* 	0
* 	0.0
* 	NULL
* 	FALSE
* 	[]
* 	array()
*/
$lastName = empty($user['last_name']) === FALSE ? $user['last_name'] : '';
/**
* Check if the user has a username
*
* empty() check if the argument is empty
* 	''
* 	""
* 	'0'
* 	"0"
* 	0
* 	0.0
* 	NULL
* 	FALSE
* 	[]
* 	array()
*/
$username = empty($user['username']) === FALSE ? $user['username'] : '';
/**
* Check if the user has a language code
*
* empty() check if the argument is empty
* 	''
* 	""
* 	'0'
* 	"0"
* 	0
* 	0.0
* 	NULL
* 	FALSE
* 	[]
* 	array()
*/
$lang = empty($user['language_code']) === FALSE ? $user['language_code'] : '';

$completeName = $firstName . ' ' . $lastName;
