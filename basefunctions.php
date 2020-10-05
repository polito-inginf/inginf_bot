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
* Encodes a created URL to Telegram
*
* @param string $url the URL to encode
*
* @return mixed The result of the encode
*/
function request(string $url) {
	// Create the URL for the Telegram's Bot API
	$url = api . $url;

	// Replace the special character into the URL
	$url = str_replace([
		"\n",
		' ',
		'#',
		"'"
	], [
		'%0A%0D',
		'%20',
		'%23',
		'%27'
	], $url);

	// Open the cURL session
	$curlSession = curl_init($url);

	// Set the cURL session
	curl_setopt_array($curlSession, [
		CURLOPT_HEADER => FALSE,
		CURLOPT_RETURNTRANSFER => TRUE
	]);

	// Exec the request
	$result = curl_exec($curlSession);

	// Close the cURL session
	curl_close($curlSession);
	return $result;
}

/**
* Answers to a CallbackQuery
*
* @param int $callbackId CallbackQuery id
*
* @return mixed The result of the encode
*/
function answerCallbackQuery(int $callbackId) {
	return request("answerCallbackQuery?callback_query_id=$callbackId");
}

/**
* Answers to an InlineQuery
*
* @param int $queryId Query id
* @param array $ans The answers
*
* @return mixed The result of the encode
*/
function answerInlineQuery(int $queryId, array $ans) {
	/**
	* Encode the keyboard layout
	*
	* json_encode() Convert the PHP object to a JSON string
	*/
	$res = json_encode($ans);
	
	return request("answerInlineQuery?inline_query_id=$queryId&results=$res");
}

/**
* Updates the keyboard without sending a new message, but modifies the existing one
*
* @param array $keyboard Keyboard layout to send
* @param int/string $chatId The id/username of the chat/channel/user to where we want edit the InlineKeyboard
* @param int $messageId message id to modify
*
* @return mixed The result of the encode
*/
function editMessageReplyMarkup($chatId, array $keyboard, int $messageId) {
	/**
	* Encode the keyboard layout
	*
	* json_encode() Convert the PHP object to a JSON string
	*/
	$keyboard = json_encode([
		"inline_keyboard" => $keyboard
	]);
	
	return request("editMessageReplyMarkup?chat_id=$chatId&message_id=$messageId&reply_markup=$keyboard");
}

/**
* Edits a sent message (with or not the InlineKeyboard associated).
*
* @param int/string $chatId The id/username of the chat/channel/user where we want edit the message
* @param int $messageId The id of the message to modify
* @param string $text The message to send
* @param int $flags [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	ENABLE_PAGE_PREVIEW: enables preview for links
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param array $keyboard [Optional] Keyboard layout to send
*
* @return mixed The result of the encode
*/
function editMessageText($chatId, int $messageId, string $text, int $flags = 0, array $keyboard = []) {
	$parseMode = 'HTML';
	$disablePreview = TRUE;
	
	/**
	* Check if the URL must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($text, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$text = urlencode($text);
	}
	
	// Check if the parse mode must be setted to 'MarkdownV2'
	if ($flags & MARKDOWN) {
		$parseMode = 'MarkdownV2';
	}
	
	// Check if the preview for links must be enabled
	if ($flags & ENABLE_PAGE_PREVIEW) {
		$disablePreview = FALSE;
	}
	
	$url = "editMessageText?chat_id=$chatId&message_id=$messageId&text=$text&parse_mode=$parseMode&disable_web_page_preview=$disablePreview";

	/**
	* Check if the message have an InlineKeyboard
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
	if (empty($keyboard) === FALSE) {
		/**
		* Encode the keyboard layout
		*
		* json_encode() Convert the PHP object to a JSON string
		*/
		$keyboard = json_encode([
			"inline_keyboard" => $keyboard
		]);
		
		$url .= "&reply_markup=$keyboard";
	}
	
	return request($url);
}

/**
* Forward a message from a chat/channel/user to another
* 
* @param int/string $toChatid The id/username of the chat/channel/user where we want send the message
* @param int/string $fromChatid The id/username of the chat/channel/user where the message we want to forward it's located
* @param int $messageId The id of the message to forward
* @param int $flag [Optional] Pipe to set more options
* 	DISABLE_NOTIFICATIONS: mutes notifications
* 
* @return mixed The forwarded message object if forwarding was successful, NULL otherwise.
*/
function forwardMessage($toChatid, $fromChatid, int $messageId, int $flag = 0) {
	$mute = FALSE;
	
	// Check if the message must be muted
	if ($flags & DISABLE_NOTIFICATION) {
		$mute = TRUE;
	}
	
	$msg = request("forwardMessage?chat_id=$toChatid&from_chat_id=$fromChatid&message_id=$messageId&disable_notification=$mute");

	// Check if function must be logged
	if (LOG_LVL > 3 && $toChatid != LOG_CHANNEL){
		sendLog(__FUNCTION__, $msg);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the output to a PHP object
	*/
	$msg = json_decode($msg, TRUE);

	return $msg['ok'] == TRUE ? $msg['result'] : NULL ;

}

/**
* Returns an up-to-date information about a chat/channel with a certain chat_id through a Chat object
* 
* @param int/string $chatId The id/username of the chat/channel we want to extract the information from
* 
* @return mixed The Chat object of the chat
*/
function getChat($chatId) {
	$chat = request("getChat?chat_id=$chatId");

	// Check if function must be logged
	if (LOG_LVL > 3){
		sendLog(__FUNCTION__, $chat);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the output to a PHP object
	*/
	$chat = json_decode($chat, TRUE);

	return $chat['ok'] == TRUE ? $chat['result'] : NULL ;
}

/**
* Pins a given message in a specific chat/channel where the bot it's an admin
* 
* @param int/string $chatId The id/username of the chat/channel where we want pin the message
* @param int $messageId  The id of the message to pin
* @param int $flag [Optional] Pipe to set more options
* 	DISABLE_NOTIFICATIONS: mutes notifications
* 
* @return boolean TRUE if the pinning operation was successful.
*/
function pinChatMessage($chatId, int $messageId, int $flag = 0) {
	$mute = FALSE;
	
	// Check if the message must be muted
	if ($flags & DISABLE_NOTIFICATION) {
		$mute = TRUE;
	}
	
	$result = request("pinChatMessage?chat_id=$chatId&message_id=$messageId&disable_notification=$mute");

	// Check if function must be logged
	if (LOG_LVL > 3 && $chatId != LOG_CHANNEL){
		sendLog(__FUNCTION__, $result);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the output to a PHP object
	*/
	$result = json_decode($result, TRUE);

	return $result['result'];
}

/**
* Send a message
*
* @param int/string $chatId The id/username of the chat/channel/user where we want send the message
* @param string $text The message to send
* @param int $flags [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	ENABLE_PAGE_PREVIEW: enables preview for links
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param array $keyboard [Optional] Keyboard layout to send
*
* @return mixed Result of the encode
*/
function sendMessage($chatId, string $text, int $flags = 0, array $keyboard = []) {
	$parseMode = 'HTML';
	$disablePreview = TRUE;
	$mute = FALSE;
	
	/**
	* Check if the URL must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($text, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$text = urlencode($text);
	}
	
	// Check if the parse mode must be setted to 'MarkdownV2'
	if ($flags & MARKDOWN) {
		$parseMode = 'MarkdownV2';
	}
	
	// Check if the preview for links must be enabled
	if ($flags & ENABLE_PAGE_PREVIEW) {
		$disablePreview = FALSE;
	}
	
	// Check if the message must be muted
	if ($flags & DISABLE_NOTIFICATION) {
		$mute = TRUE;
	}
	
	$url = "sendMessage?text=$text&chat_id=$chatId&parse_mode=$parseMode&disable_web_page_preview=$disablePreview&disable_notification=$mute";
	
	/**
	* Check if the message have an InlineKeyboard
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
	if (empty($keyboard) === FALSE) {
		/**
		* Encode the keyboard layout
		*
		* json_encode() Convert the PHP object to a JSON string
		*/
		$keyboard = json_encode([
			"inline_keyboard" => $keyboard
		]);
		
		$url .= "&reply_markup=$keyboard";
	}

	$msg = request($url);
	
	// Check if function must be logged
	if (LOG_LVL > 3 && $chatId != LOG_CHANNEL) {
		sendLog(__FUNCTION__, $msg);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the JSON string to a PHP object
	*/
	$msg = json_decode($msg, TRUE);

	return $msg['ok'] == TRUE ? $msg['result'] : NULL;
}

/**
* Sends a Photo (identified by a string that can be both a file_id or an HTTP URL of a pic on internet)
* to the chat pointed from $chatId and returns the Message object of the sent message
* 
* @param int/string $chatId The id/username of the chat/channel/user to send the message
* @param string $photo The URL that points to a photo from the web or a file_id of a photo already on the Telegram's servers
* @param int $flag [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param string $caption [Optional] the caption
* 
* @return mixed The Message sent by the method
*/
function sendPhoto($chatId, string $photo, int $flags = 0, string $caption = '') {
	$parseMode = 'HTML';
	$mute = FALSE;
	
	/**
	* Check if the photo must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($photo, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$photo = urlencode($photo);
	}
	
	/**
	* Check if the caption of the photo must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($caption, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$caption = urlencode($caption);
	}

	// Check if the parse mode must be setted to 'MarkdownV2'
	if ($flags & MARKDOWN) {
		$parseMode = 'MarkdownV2';
	}
	
	// Check if the message must be muted
	if ($flags & DISABLE_NOTIFICATION) {
		$mute = TRUE;
	}
	
	$msg = request("sendPhoto?chat_id=$chatId&photo=$photo&caption=$caption&parse_mode=$parseMode&disable_notification=$mute");

	// Check if function must be logged
	if (LOG_LVL > 3 && $chatId != LOG_CHANNEL){
		sendLog(__FUNCTION__, $msg);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the output to a PHP object
	*/
	$msg = json_decode($msg, TRUE);

	return $msg['ok'] == TRUE ? $msg['result'] : NULL ;
}
