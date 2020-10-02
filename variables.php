<?php
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
	
	$name_text = $firstname . " " . $lastname;
?>
