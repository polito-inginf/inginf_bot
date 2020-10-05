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

/**
* This section of the code defines all required variables
* by getting information from Telegram Bot's update.
*
* @todo Adapt to our needs
* @todo Not the best way to proceed, use another method
* @todo Check for undefined variables with empty()
*/

$text = empty($update['message']['text']) === FALSE ? trim($update['message']['text']) : "";
$callback_data = $update['callback_query']['data'];
$msg_id = $update['callback_query']['message']['message_id'];
$callback_id = $update['callback_query']['id'];
$inline_query_id = $update["inline_query"]["id"];
$inline_query = $update["inline_query"]["query"];

if (empty($update['callback_query']) === FALSE) {
	$user = $update['callback_query']['from'];
} else if (empty($update['inline_query']) === FALSE) {
	$user = $update['inline_query']['from'];
} else if (empty($update['message']) === FALSE) {
	$user = $update['message']['from'];
}

$user_id = $user['id'];
$first_name = $user['first_name'];
$last_name = empty($user['last_name']) === FALSE ? $user['last_name'] : "";
$username = empty($user['username']) === FALSE ? $user['username'] : "";
$lang = empty($user['language_code']) === FALSE ? $user['language_code'] : "";

$name_text = $first_name . " " . $last_name;
