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
* @license		https://choosealicense.com/licenses/lgpl-3.0/
*/

/**
* Execute a request to the Telegram's Bot API.
*
* @param string $urlt The Bot API endpoint.
*
* @return mixed The result of the request
*/
function request(string $url) {
	// Create the URL for the Telegram's Bot API
	$url = api . $url;

	/**
	* Replace the special character into the URL
	*
	* str_replace() Replace the first array of character with the second array
	*/
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

/**
* Answers to a CallbackQuery.
*
* @param int $callbackId The id of the CallbackQuery.
* @param string $text The text to be sent in the notification.
* @param int $flags [Optional] Pipe to set more options
* 	SHOW_ALERT: enables alert instead of top notification
* @param string $url [Optional] Url to be opened.
*
* @return boolean On success, TRUE.
*/
function answerCallbackQuery(int $callbackId, string $text, int $flags = 0, string $url = "") {
	$showAlert = FALSE;

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

	// Check if the CallbackQuery must produce an alert popup
	if($flags & SHOW_ALERT) {
		$showAlert = TRUE;
	}

	$requestUrl = "answerCallbackQuery?callback_query_id=$callbackId&text=$text&show_alert=$showAlert";

	/**
	* Check if a url parameter is present
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
	if(empty($url) === FALSE) {
		$requestUrl .= "&url=$url";
	}

	return request($requestUrl);
}

/**
* Answers to an InlineQuery.
* @todo Do we want to pass the results as a parameter or find the results inside the function ?
*
* @param int $queryId The id of the InlineQuery.
* @param array $ans The answers.
*
* @return boolean On success, TRUE.
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
* Deletes a message.
*
* @param int/string $chatId The id/username of the chat/channel/user where the message is located.
* @param string $messageId The id of the message to delete.
*
* @return boolean On success, TRUE.
*/
function deleteMessage($chatId, int $messageId) {
	$response =  request("deleteMessage?chat_id=$chatId&message_id=$messageId");

	// Check if function must be logged
	if (LOG_LVL > 3){
		sendLog(__FUNCTION__, [
			'chat_id' => $chatId,
			'message_id' => $messageId,
			'response' => $response
		]);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the output to a PHP object
	*/
	$response = json_decode($response, TRUE);

	return $response['ok'] == TRUE ? $response['result'] : NULL;
}

/**
* Edits a caption of a sent message (with or not the InlineKeyboard associated).
*
* @param int/string $chatId The id/username of the chat/channel/user where we want edit the message.
* @param int $messageId The id of the message to modify.
* @param string $caption The caption to send.
* @param int $flags [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* @param array $keyboard [Optional] The layout of the keyboard to send.
*
* @return mixed On success, the Message edited by the method.
*/
function editMessageCaption($chatId, int $messageId, string $caption, int $flags = 0, array $keyboard = []) {
	$parseMode = 'HTML';

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
		$caption = urlencode($caption);
	}

	// Check if the parse mode must be setted to 'MarkdownV2'
	if ($flags & MARKDOWN) {
		$parseMode = 'MarkdownV2';
	}

	$url = "editMessageCaption?chat_id=$chatId&message_id=$messageId&caption=$caption&parse_mode=$parseMode";

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

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the output to a PHP object
	*/
	$response = request($url);

	// Check if function must be logged
	if (LOG_LVL > 3){
		sendLog(__FUNCTION__, $response);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the JSON string to a PHP object
	*/
	$response = json_decode($response, TRUE);

	/**
	 * @todo test if this works when editing other people messages.
	 */
	return $response['ok'] == TRUE ? $response['result'] : NULL;
}

/**
* Updates the keyboard without sending a new message, but modifies the existing one.
*
* @param int/string $chatId The id/username of the chat/channel/user to where we want edit the InlineKeyboard.
* @param array $keyboard [Optional] The layout of the keyboard to send.
* @param int $messageId The id of the message to modify.
*
* @return mixed On success, the Message edited by the method.
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

	$response = request("editMessageReplyMarkup?chat_id=$chatId&message_id=$messageId&reply_markup=$keyboard");

	// Check if function must be logged
	if (LOG_LVL > 3){
		sendLog(__FUNCTION__, $response);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the output to a PHP object
	*/
	$response = json_decode($response, TRUE);

	/**
	 * @todo test if this works when editing other people messages.
	 */
	return $response['ok'] == TRUE ? $response['result'] : NULL;
}

/**
* Edits a sent message (with or not the InlineKeyboard associated).
*
* @param int/string $chatId The id/username of the chat/channel/user where we want edit the message.
* @param int $messageId The id of the message to modify.
* @param string $text The message to send.
* @param int $flags [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	ENABLE_PAGE_PREVIEW: enables preview for links
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param array $keyboard [Optional] The layout of the keyboard to send.
*
* @return mixed On success, the Message edited by the method.
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

	$response = request($url);

	// Check if function must be logged
	if (LOG_LVL > 3){
		sendLog(__FUNCTION__, $response);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the output to a PHP object
	*/
	$response = json_decode($response, TRUE);

	/**
	 * @todo test if this works when editing other people messages.
	 */
	return $response['ok'] == TRUE ? $response['result'] : NULL;
}

/**
* Forward a message from a chat/channel/user to another.
*
* @param int/string $toChatid The id/username of the chat/channel/user where we want send the message.
* @param int/string $fromChatid The id/username of the chat/channel/user where the message we want to forward it's located.
* @param int $messageId The id of the message to forward.
* @param int $flag [Optional] Pipe to set more options
* 	DISABLE_NOTIFICATIONS: mutes notifications
*
* @return mixed On success, the Message forwarded by the method.
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
* Returns an up-to-date information about a chat/channel with a certain chat_id through a Chat object.
*
* @param int/string $chatId The id/username of the chat/channel we want to extract the information from.
*
* @return mixed On success, the Chat retrieved by the method.
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
* Pins a given message in a specific chat/channel where the bot it's an admin.
*
* @param int/string $chatId The id/username of the chat/channel where we want pin the message.
* @param int $messageId  The id of the message to pin.
* @param int $flag [Optional] Pipe to set more options
* 	DISABLE_NOTIFICATIONS: mutes notifications
*
* @return boolean On success, TRUE.
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
* Reply to a message.
*
* @param int/string $chatId The id/username of the chat/channel/user where we want send the message.
* @param string $text The message to send.
* @param int $messageId The id of the message you want to respond to.
* @param int $flags [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	ENABLE_PAGE_PREVIEW: enables preview for links
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param array $keyboard [Optional] The layout of the keyboard to send.
*
* @return mixed On success, the Message edited by the method.
*/
function replyToMessage($chatId, string $text, int $messageId, int $flags = 0, array $keyboard = []) {
	return sendMessage($chatId, $text, $flags, $keyboard, $messageId);
}

/**
* Sends an Audio file, if you want Telegram clients to display them in the music player,
* to the chat pointed from $chatId and returns the Message object of the sent message.
* The audio is identified by a string that can be both a file_id or an HTTP URL of a pic on internet.
* Bots can, currently, send audio files of up to 50 MB in size, this limit may be changed in the future.
*
* @param int/string $chatId The id/username of the chat/channel/user to send the audio.
* @param string $audio The URL that points to an audio from the web or a file_id of an audio already on the Telegram's servers.
* @param string $caption [Optional] The caption of the audio.
* @param int $flag [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param int $duration [Optional] The duration of sent audio expressed in seconds.
* @param string $performer [Optional] The performer of the audio.
* @param string $title [Optional] The title of the audio.
* @param string $thumb [Optional] The URL that points to the thumbnail of the audio from the web or a file_id of a thumbnail already on the Telegram's servers.
* @param int $messageId [Optional] The id of the message you want to respond to.
* @param array $keyboard [Optional] The layout of the keyboard to send.
*
* @return mixed On success, the Message edited by the method.
*/
function sendAudio($chatId, string $audio, string $caption = '', int $flags = 0, int $duration = 0, string $performer = '', string $title = '', string $thumb = '', int $messageId = 0, array $keyboard = []) {
	$parseMode = 'HTML';
	$mute = FALSE;

	/**
	* Check if the audio must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($audio, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$audio = urlencode($audio);
	}

	/**
	* Check if the caption of the audio must be encoded
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

	/**
	* Check if the thumbnail of the audio must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($thumb, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$thumb = urlencode($thumb);
	}

	/**
	* Check if the performer of the audio must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($performer, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$performer = urlencode($performer);
	}

	/**
	* Check if the title of the audio must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($title, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$title = urlencode($title);
	}

	// Check if the parse mode must be setted to 'MarkdownV2'
	if ($flags & MARKDOWN) {
		$parseMode = 'MarkdownV2';
	}

	// Check if the message must be muted
	if ($flags & DISABLE_NOTIFICATION) {
		$mute = TRUE;
	}

	$url = "sendAudio?chat_id=$chatId&audio=$audio&parse_mode=$parseMode&disable_notification=$mute";

	/**
	* Check if the caption of the audio exists
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
	if(empty($caption) === FALSE) {
		$url .= "&caption=$caption";
	}

	/**
	* Check if the audio have a specific duration
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
	if(empty($duration) === FALSE) {
		$url .= "&duration=$duration";
	}

	/**
	* Check if the thumbnail of the audio exists
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
	if(empty($thumb) === FALSE) {
		$url .= "&thumb=$thumb";
	}

	/**
	* Check if the performer of the audio exists
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
	if(empty($performer) === FALSE) {
		$url .= "&performer=$performer";
	}

	/**
	* Check if the title of the audio exists
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
	if(empty($title) === FALSE) {
		$url .= "&title=$title";
	}

	/**
	* Check if the message must reply to another one
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
	if(empty($messageId) === FALSE) {
		$url .= "&reply_to_message_id=$messageId";
	}

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

/**
* Sends a group of photos and videos as an album to the chat pointed from $chatId and returns the Message object of the sent message.
*
* @param int/string $chatId The id/username of the chat/channel/user to send the album.
* @param array $media An array of InputMediaPhoto and InputMediaVideo that describe the photos and the videos to be sent; must include 2-10 items.
* @param int $flag [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param int $messageId [Optional] The id of the message you want to respond to.
*
* @return mixed On success, the Message edited by the method.
*/
function sendMediaGroup($chatId, array $media, int $flags = 0, int $messageId = 0) {
	$mute = FALSE;

	// Check if the message must be muted
	if ($flags & DISABLE_NOTIFICATION) {
		$mute = TRUE;
	}

	foreach ($media as $singleMedia) {
		/**
		* Check if the media haven't already a parse_mode setted
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
		$singleMedia['parse_mode'] = empty($singleMedia['parse_mode']) ? 'HTML' : $singleMedia['parse_mode'];

		// Check if the parse mode must be setted to 'MarkdownV2'
		if ($singleMedia['parse_mode'] !== 'MarkdownV2' && $flags & MARKDOWN) {
			$singleMedia['parse_mode'] = 'MarkdownV2';
		}

		/**
		* Check if the media must be encoded
		*
		* strpos() Check if the '\n' character is into the string
		*/
		if (strpos($singleMedia['media'], "\n")) {
			/**
			* Encode the URL
			*
			* urlencode() Encode the URL, converting all the special character to its safe value
			*/
			$singleMedia['media'] = urlencode($singleMedia['media']);
		}

		/**
		* Check if the caption of the media exists and if it must be encoded
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
		* strpos() Check if the '\n' character is into the string
		*/
		if (empty($singleMedia['caption']) === FALSE && strpos($singleMedia['caption'], "\n")) {
			/**
			* Encode the URL
			*
			* urlencode() Encode the URL, converting all the special character to its safe value
			*/
			$singleMedia['caption'] = urlencode($singleMedia['caption']);
		}

		/**
		* Check if the media is a video, if its thumbnail exists and if its thumbnail must be encoded
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
		* strpos() Check if the '\n' character is into the string
		*/
		if ($singleMedia['type'] === 'video' && empty($singleMedia['thumb']) === FALSE && strpos($singleMedia['thumb'], "\n")) {
			/**
			* Encode the URL
			*
			* urlencode() Encode the URL, converting all the special character to its safe value
			*/
			$singleMedia['thumb'] = urlencode($singleMedia['thumb']);
		}
	}

	$url = "sendMediaGroup?chat_id=$chatId&disable_notification=$mute&media=";

	/**
	* Appends the JSON version of the array of media to the URL
	*
	* json_encode() Convert the PHP object to a JSON string
	*/
	$url .= json_encode($media, JSON_UNESCAPED_SLASHES);

	/**
	* Check if the message must reply to another one
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
	if(empty($messageId) === FALSE) {
		$url .= "&reply_to_message_id=$messageId";
	}

	$msg = request($url);

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

/**
* Send a message.
*
* @param int/string $chatId The id/username of the chat/channel/user where we want send the message.
* @param string $text The message to send.
* @param int $flags [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	ENABLE_PAGE_PREVIEW: enables preview for links
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param array $keyboard [Optional] The layout of the keyboard to send.
* @param int $messageId [Optional] The id of the message you want to respond to.
*
* @return mixed On success, the Message edited by the method.
*/
function sendMessage($chatId, string $text, int $flags = 0, array $keyboard = [], int $messageId = 0) {
	$parseMode = 'HTML';
	$disablePreview = TRUE;
	$mute = FALSE;
	$functionToLog = __FUNCTION__;

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
	* Check if the message must reply to another one
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
	if(empty($messageId) === FALSE) {
		$url .= "&reply_to_message_id=$messageId";
		$functionToLog = "replyToMessage";
	}

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
		sendLog($functionToLog, $msg);
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
* to the chat pointed from $chatId and returns the Message object of the sent message.
*
* @param int/string $chatId The id/username of the chat/channel/user to send the photo.
* @param string $photo The URL that points to a photo from the web or a file_id of a photo already on the Telegram's servers.
* @param int $flag [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param string $caption [Optional] The caption of the photo.
*
* @return mixed On success, the Message edited by the method.
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

	$url = "sendPhoto?chat_id=$chatId&photo=$photo&parse_mode=$parseMode&disable_notification=$mute"

	/**
	* Check if the caption of the photo exists
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
	if(empty($caption) === FALSE) {
		$url .= "&caption=$caption";
	}

	$msg = request($url);

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

/**
* Sends a Video (identified by a string that can be both a file_id or an HTTP URL of a pic on internet)
* to the chat pointed from $chatId and returns the Message object of the sent message.
* Bots can, currently, send video files of up to 50 MB in size, this limit may be changed in the future.
*
* @param int/string $chatId The id/username of the chat/channel/user to send the video.
* @param string $video The URL that points to a video from the web or a file_id of a video already on the Telegram's servers.
* @param int $duration [Optional] The duration of sent video expressed in seconds.
* @param int $width [Optional] The video width.
* @param int $height [Optional] The video height.
* @param string $thumb [Optional] The URL that points to the thumbnail of the video from the web or a file_id of a thumbnail already on the Telegram's servers.
* @param int $flag [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	DISABLE_NOTIFICATIONS: mutes notifications
* 	SUPPORTS_STREAMING: tells if the video is suitable for streaming
* @param string $caption [Optional] The caption of the video.
* @param int $messageId [Optional] The id of the message you want to respond to.
* @param array $keyboard [Optional] The layout of the keyboard to send.
*
* @return mixed On success, the Message edited by the method.
*/
function sendVideo($chatId, string $video, int $duration = 0, int $width = 0, int $height = 0, string $thumb = '', int $flags = 0, string $caption = '', int $messageId = 0, array $keyboard = []) {
	$parseMode = 'HTML';
	$mute = FALSE;
	$streaming = FALSE;

	/**
	* Check if the video must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($video, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$video = urlencode($video);
	}

	/**
	* Check if the thumbnail of the video must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($thumb, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$thumb = urlencode($thumb);
	}

	/**
	* Check if the caption of the video must be encoded
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

	// Check if the video supports the streaming
	if ($flags & SUPPORTS_STREAMING) {
		$streaming = TRUE;
	}

	$url = "sendVideo?chat_id=$chatId&video=$video&parse_mode=$parseMode&supports_streaming=$streaming&disable_notification=$mute";

	/**
	* Check if the caption of the video exists
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
	if(empty($caption) === FALSE) {
		$url .= "&caption=$caption";
	}

	/**
	* Check if the thumbnail of the video exists
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
	if(empty($thumb) === FALSE) {
		$url .= "&thumb=$thumb";
	}

	/**
	* Check if the video have a specific duration
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
	if(empty($duration) === FALSE) {
		$url .= "&duration=$duration";
	}

	/**
	* Check if the video have a specific width
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
	if(empty($width) === FALSE) {
		$url .= "&width=$width";
	}

	/**
	* Check if the video have a specific height
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
	if(empty($height) === FALSE) {
		$url .= "&height=$height";
	}

	/**
	* Check if the message must reply to another one
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
	if(empty($messageId) === FALSE) {
		$url .= "&reply_to_message_id=$messageId";
	}

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

/**
* Sends an Audio file, if you want Telegram clients to display the audio as a playable voice message,
* to the chat pointed from $chatId and returns the Message object of the sent message.
* The audio is identified by a string that can be both a file_id or an HTTP URL of a pic on internet.
* Bots can, currently, send audio files of up to 50 MB in size, this limit may be changed in the future.
*
* @param int/string $chatId The id/username of the chat/channel/user to send the audio.
* @param string $voice The URL that points to an audio from the web or a file_id of an audio already on the Telegram's servers.
* @param string $caption [Optional] The caption of the audio.
* @param int $flag [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param int $duration [Optional] The duration of sent audio expressed in seconds.
* @param int $messageId [Optional] The id of the message you want to respond to.
* @param array $keyboard [Optional] The layout of the keyboard to send.
*
* @return mixed On success, the Message edited by the method.
*/
function sendVoice($chatId, string $voice, string $caption = '', int $flags = 0, int $duration = 0, int $messageId = 0, array $keyboard = []) {
	$parseMode = 'HTML';
	$mute = FALSE;

	/**
	* Check if the audio must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($voice, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$voice = urlencode($voice);
	}

	/**
	* Check if the caption of the audio must be encoded
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

	$url = "sendVoice?chat_id=$chatId&voice=$voice&parse_mode=$parseMode&disable_notification=$mute";

	/**
	* Check if the caption of the audio exists
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
	if(empty($caption) === FALSE) {
		$url .= "&caption=$caption";
	}

	/**
	* Check if the audio have a specific duration
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
	if(empty($duration) === FALSE) {
		$url .= "&duration=$duration";
	}

	/**
	* Check if the message must reply to another one
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
	if(empty($messageId) === FALSE) {
		$url .= "&reply_to_message_id=$messageId";
	}

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

/**
* Set the command of the Bot.
*
* @param array $commands The commands of the Bot.
*
* @return boolean On success, TRUE.
*/
function setMyCommands(array $commands) {
	foreach ($commands as $command) {
		/**
		* Check if the name of the command must be encoded
		*
		* strpos() Check if the '\n' character is into the string
		*/
		if (strpos($command['command'], "\n")) {
			/**
			* Encode the URL
			*
			* urlencode() Encode the URL, converting all the special character to its safe value
			*/
			$command['command'] = urlencode($command['command']);
		}

		/**
		* Check if the description of the command must be encoded
		*
		* strpos() Check if the '\n' character is into the string
		*/
		if (strpos($command['description'], "\n")) {
			/**
			* Encode the URL
			*
			* urlencode() Encode the URL, converting all the special character to its safe value
			*/
			$command['description'] = urlencode($command['description']);
		}
	}

	/**
	* Convert the array of the Bot's command to its JSON version
	*
	* json_encode() Convert the PHP object to a JSON string
	*/
	$commands = json_encode($commands, JSON_UNESCAPED_SLASHES);

	$result = request("setMyCommands?commands=$commands");

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
* Unban a user in a specific chat/channel where the bot it's an admin.
*
* @param int/string $chatId The id/username of the chat/channel where we want unban a user
* @param int/string $userId The id/username of the user we want unban.
*
* @return boolean On success, TRUE.
*/
function unbanChatMember($chatId, $userId) {
	$result = request("unbanChatMember?chat_id=$chatId&user_id=$userId");

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
* Unpins the message in a specific chat/channel where the bot it's an admin.
*
* @param int/string $chatId The id/username of the chat/channel where we want unpin the message.
*
* @return boolean On success, TRUE.
*/
function unpinChatMessage($chatId) {
	$result = request("unpinChatMessage?chat_id=$chatId");

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
* Edits (replaces) animation, audio, document, photo, or video messages (with or not the InlineKeyboard associated).
*
* @param int/string $chatId [Optional] The id/username of the chat/channel/user where we want edit the message
* @param int $messageId [Optional] The id of the message to modify
* @param string $inlineMessageId [Optional] necessary if $chatId and $messageId are not specified. Identifier of the inline message
* @param mixed $media the new media content of the message.
*	it should be one of the following InputMedia type:
*	InputMediaPhoto
*	InputMediaVideo
*	InputMediaAnimation
*	InputMediaAudio
*	InputMediaDocument
* @param array $keyboard [Optional] Keyboard layout to send
*
* @return mixed/bool If the edited message was originally sent by the bot, it returns the modified Message, 
*	otherwise returns True
*/
function editMessageMedia($chatId, int $messageId, string $inlineMessageId, $media, array $keyboard = []) {
	
	$url = "editMessageMedia?chat_id=$chatId&message_id=$messageId&inline_message_id=$inlineMessageId&media=$media";

	/**
	* Check if the message has an InlineKeyboard
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
	
	$response = request($url);


	// Check if function must be logged
	if (LOG_LVL > 3){
		sendLog(__FUNCTION__, $response);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the output to a PHP object
	*/
	$response = json_decode($response, TRUE);

	/**
	 * @todo test if this works when editing other people messages.
	 */
	return $response['ok'] == TRUE ? $response['result'] : NULL;
}

/**
* Gives the bot the possibility to generate his own (NEW) invite link to the chat and also makes it
* retrievable via the getChat method as it's stored into the [Optional] field invite_link of the Chat Object returned
* by that method.
* 	
* NB:
* 1) The bot must be an administrator since every admin has it's own invite link and bots cannot use
* invite links of other adiministrators.
* 2)Every time a new invite link is generated through this method the previous invite links stops working (are revoked).
*
* @param int/string $chatId The id/username of the targeted chat/channel
*
* @return string returns the new invite link on success
*/
function exportChatInviteLink($chatId) {

	$inviteLink = request("exportChatInviteLink?chat_id=$chatId");
	
	// Check if function must be logged
	if (LOG_LVL > 3 && $chatId != LOG_CHANNEL) {
		sendLog(__FUNCTION__, $inviteLink);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the JSON string to a PHP object
	*/
	$inviteLink = json_decode($inviteLink, TRUE);

	return $inviteLink['ok'] == TRUE ? $inviteLink['result'] : NULL;
}


/**
 * Used to know the list of administrators of a channel/group/supergroup. 
 * 
 * @param int/string $chatId the id of the targeted chat.
 * 
 * @return mixed $adminsArray is an array of ChatMember objects representing the Administrators, if no admins are setted
 * then only the the creator's ChatMember object will be returned.
 */
function getChatAdministrators($chatId){

	$adminsArray = request("getChatAdministrators?chat_id=$chatId");
	
	// Check if function must be logged
	if (LOG_LVL > 3 && $chatId != LOG_CHANNEL) {
		sendLog(__FUNCTION__, $adminsArray);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the JSON string to a PHP object
	*/
	$adminsArray = json_decode($adminsArray, TRUE);

	return $adminsArray['ok'] == TRUE ? $adminsArray['result'] : NULL;

}

/**
 * Used to get information about a specific member of a chat through his relative ChatMember Object. 
 * 
 * @param int/string $chatId the id of the chat.
 * @param int $userId the id of the targeted member of the chat.
 * 
 * @return mixed $chatMember returns the ChatMember Object of the targeted member.
 */
function getChatMember($chatId){

	$chatMember = request("getChatMember?chat_id=$chatId");
	
	// Check if function must be logged
	if (LOG_LVL > 3 && $chatId != LOG_CHANNEL) {
		sendLog(__FUNCTION__, $chatMember);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the JSON string to a PHP object
	*/
	$chatMember = json_decode($chatMember, TRUE);

	return $chatMember['ok'] == TRUE ? $chatMember['result'] : NULL;

}

/**
 * Used to get the File Object of a certain file already uploaded on telegram through its file_id. 
 * NB: 
 * 1) Bot download size limit for a file is 20MB.
 * 2) File Object contains the [Optional] file_path field where it's stored the <file_path> 
 * to concat to --> https://api.telegram.org/file/bot<token>/ to download via link the file.
 * the link it's guaranteed to be valid for at least one hour.
 * 3)This function may not preserve the original file name and MIME type.
 * 
 * @param string $fileId the id of the targeted file.
 * 
 * @return mixed $file the File Object of the targeted file
 */
function getFile($fileId){

	$file = request("getFile?file_id=$fileId");
	
	// Check if function must be logged
	if (LOG_LVL > 3) {
		sendLog(__FUNCTION__, $file);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the JSON string to a PHP object
	*/
	$file = json_decode($file, TRUE);

	return $file['ok'] == TRUE ? $file['result'] : NULL;

}


/**
 *  Returns basic information about the bot in form of a User object.
 * 
 * @return $myself is the User Object of the bot.
 */
function getMe(){

	//try at least, no one gets me, i'm such a sad bot
	$myself = request("getme");
	
	// Check if function must be logged
	if (LOG_LVL > 3) {
		sendLog(__FUNCTION__, $myself);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the JSON string to a PHP object
	*/
	$myself = json_decode($myself, TRUE);

	return $myself['ok'] == TRUE ? $myelf['result'] : NULL;

}