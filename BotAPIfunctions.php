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
* @return mixed The result of the request.
*/
function requestBotAPI(string $url) {
	// Create the URL for the Telegram's Bot API
	$url = api . $url;

	return request($url);
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
* @return bool On success, TRUE.
*/
function answerCallbackQuery(int $callbackId, string $text, int $flags = 0, string $url = "") : bool {
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

	return requestBotAPI($requestUrl);
}

/**
* Answers to an InlineQuery.
*
* @param int $queryId The id of the InlineQuery.
* @param array $ans The answers.
*
* @return bool On success, TRUE.
*/
function answerInlineQuery(int $queryId, array $ans) : bool {
	/**
	* Encode the keyboard layout
	*
	* json_encode() Convert the PHP object to a JSON string
	*/
	$res = json_encode($ans);

	return requestBotAPI("answerInlineQuery?inline_query_id=$queryId&results=$res");
}

/**
* Deletes a message.
*
* @param int/string $chatId The id/username of the chat/channel/user where the message is located.
* @param int $messageId The id of the message to delete.
*
* @return bool On success, TRUE.
*/
function deleteMessage($chatId, int $messageId) : bool {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return FALSE;
	}

	$response =  requestBotAPI("deleteMessage?chat_id=$chatId&message_id=$messageId");

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
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

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
	$response = requestBotAPI($url);

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
* Replaces the animation, audio, document, photo, or video messages (with or not the InlineKeyboard associated).
*
* @param mixed $media The new media content of the message
*	It should be one of the following InputMedia type:
*		InputMediaAnimation
*		InputMediaDocument
*		InputMediaAudio
*		InputMediaPhoto
*		InputMediaVideo
* @param int/string $chatId [Optional] The id/username of the chat/channel/user where we want edit the message.
* 	This parameter is necessary if $inlineMessageId isn't specified.
* @param int $messageId [Optional] The id of the message to modify.
* 	This parameter is necessary if $inlineMessageId isn't specified.
* @param string $inlineMessageId [Optional] The id of the inline message.
* 	This parameter is necessary if $chatId and $messageId aren't specified.
* @param array $keyboard [Optional] The layout of the keyboard to send.
*
* @return mixed If the edited message was originally sent by the bot, on success, the modified Message, otherwise, ever on success, TRUE.
*/
function editMessageMedia($media, $chatId, int $messageId = 0, string $inlineMessageId = '', array $keyboard = []) {
	/**
	* Check if the media isn't a supported object
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
	if (empty($media['type']) || empty($media['media'])) {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The media isn't a supported object."
			]);
		}
		return;
	/**
	* Check if the media isn't a supported media
	*
	* in_array() Checks if a value exists in an array
	*/
	} else if (in_array($media['type'], [
		'animation',
		'document',
		'audio',
		'photo',
		'video'
	]) === FALSE) {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The media type isn't supported."
			]);
		}
		return;
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	} else if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}


	/**
	* Encode the media
	*
	* json_encode() Convert the PHP object to a JSON string
	*/
	$media = json_encode($media);

	$url = "editMessageMedia?media=$media";

	/**
	* Check if the id of the inline message associated to the media exists
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
	if(empty($inlineMessageId) === FALSE) {
		$url .= "&inline_message_id=$inlineMessageId";
	/**
	* Check if the id of the message associated to the media exists
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
	} else if(empty($chatId) === FALSE && empty($messageId) === FALSE) {
		$url .= "&chat_id=$chatId&message_id=$messageId";
	} else {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "At least one of the necessary parameters isn't setted."
			]);
		}
		return;
	}

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

	$response = requestBotAPI($url);


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
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

	/**
	* Encode the keyboard layout
	*
	* json_encode() Convert the PHP object to a JSON string
	*/
	$keyboard = json_encode([
		"inline_keyboard" => $keyboard
	]);

	$response = requestBotAPI("editMessageReplyMarkup?chat_id=$chatId&message_id=$messageId&reply_markup=$keyboard");

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
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

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

	$response = requestBotAPI($url);

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
* Generate a new invite link in a specific chat/channel where the bot it's an admin; any previously generated link is revoked.
* N.B. The invite link in exam is the link associated to the bot; every other invite link isn't interested by this method.
*
* @param int/string $chatId The id/username of the chat/channel where we want generate the new link.
*
* @return string On success, the new invite link.
*/
function exportChatInviteLink($chatId) : ?string {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return NULL;
	}

	$inviteLink = requestBotAPI("exportChatInviteLink?chat_id=$chatId");

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
* Forward a message from a chat/channel/user to another.
*
* @param int/string $toChatId The id/username of the chat/channel/user where we want send the message.
* @param int/string $fromChatId The id/username of the chat/channel/user where the message we want to forward it's located.
* @param int $messageId The id of the message to forward.
* @param int $flag [Optional] Pipe to set more options
* 	DISABLE_NOTIFICATIONS: mutes notifications
*
* @return mixed On success, the Message forwarded by the method.
*/
function forwardMessage($toChatid, $fromChatId, int $messageId, int $flag = 0) {
	/**
	* Check if the id of the chat where we want send the message isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($toChatId) !== 'integer' && gettype($toChatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	/**
	* Check if the id of the chat where the message we want to forward it's located isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	} else if (gettype($fromChatId) !== 'integer' && gettype($fromChatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The from_chat_id isn't a supported object."
			]);
		}
		return;
	}

	$mute = FALSE;

	// Check if the message must be muted
	if ($flags & DISABLE_NOTIFICATION) {
		$mute = TRUE;
	}

	$msg = requestBotAPI("forwardMessage?chat_id=$toChatId&from_chat_id=$fromChatId&message_id=$messageId&disable_notification=$mute");

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
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

	$chat = requestBotAPI("getChat?chat_id=$chatId");

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
* Use this method to get a list of administrators in a specific chat/channel.
* N.B. If, into the chat/channel, there are some bots as administrators, they will not be returned.
*
* @param int/string $chatId The id/username of the chat/channel where we want retrieve the admins.
*
* @return mixed On success, an array of ChatMember objects; if no administrators were appointed, only the creator will be returned.
*/
function getChatAdministrators($chatId) {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

	$adminsArray = requestBotAPI("getChatAdministrators?chat_id=$chatId");

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
* Used to get information about a specific member of a specific chat.
*
* @param int/string $chatId The id/username of the chat/channel where we want search the user.
* @param int/string $userId The id/username of the user we want search.
*
* @return mixed On success, the ChatMember object.
*/
function getChatMember($chatId, $userId) {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	/**
	* Check if the id of the user isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	} else if (gettype($userId) !== 'integer' && gettype($userId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The user_id isn't a supported object."
			]);
		}
		return;
	}

	$chatMember = requestBotAPI("getChatMember?chat_id=$chatId&user_id=$userId");

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
* Use this method to get basic info about a file and prepare it for downloading.
* For the moment, bots can download files of up to 20MB in size.
* N.B.
* 	- The file can then be downloaded via the link 'https://api.telegram.org/file/bot' . token . '/' . $file['file_path'], where $file['file_path'] is taken from the response.
* 		It is guaranteed that the link will be valid for at least 1 hour.
* 	- This function may not preserve the original file name and MIME type.
*
* @param string $fileId The id of the file we want retrieve.
*
* @return mixed On success, the File object.
*/
function getFile(string $fileId) {
	$file = requestBotAPI("getFile?file_id=$fileId");

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
* @return mixed On success, the bot.
*/
function getMe() {
	// Try at least, no one gets me, I'm such a sad bot
	$mySelf = requestBotAPI("getMe");

	// Check if function must be logged
	if (LOG_LVL > 3) {
		sendLog(__FUNCTION__, $mySelf);
	}

	/**
	* Decode the output of the HTTPS query
	*
	* json_decode() Convert the JSON string to a PHP object
	*/
	$mySelf = json_decode($mySelf, TRUE);

	return $mySelf['ok'] == TRUE ? $mySelf['result'] : NULL;

}

/**
* Pins a given message in a specific chat/channel where the bot it's an admin.
*
* @param int/string $chatId The id/username of the chat/channel where we want pin the message.
* @param int $messageId  The id of the message to pin.
* @param int $flag [Optional] Pipe to set more options
* 	DISABLE_NOTIFICATIONS: mutes notifications
*
* @return bool On success, TRUE.
*/
function pinChatMessage($chatId, int $messageId, int $flag = 0) : bool {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return FALSE;
	}

	$mute = FALSE;

	// Check if the message must be muted
	if ($flags & DISABLE_NOTIFICATION) {
		$mute = TRUE;
	}

	$result = requestBotAPI("pinChatMessage?chat_id=$chatId&message_id=$messageId&disable_notification=$mute");

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
* @return mixed On success, the Message sent by the method.
*/
function replyToMessage($chatId, string $text, int $messageId, int $flags = 0, array $keyboard = []) {
	return sendMessage($chatId, $text, $flags, $keyboard, $messageId);
}

/**
* Sends an Animation file (GIF or H.264/MPEG-4 AVC video without sound)
* to the chat pointed from $chatId.
* The animation is identified by a string that can be both a file_id or an HTTP URL of an animation on internet.
* Bots can, currently, send animation files of up to 50 MB in size, this limit may be changed in the future.
*
* @param int/string $chatId The id/username of the chat/channel/user to send the animation.
* @param string $animation The URL that points to a animation from the web or a file_id of a animation already on the Telegram's servers.
* @param int $duration [Optional] The duration of sent animation expressed in seconds.
* @param int $width [Optional] The animation width.
* @param int $height [Optional] The animation height.
* @param string $thumb [Optional] The URL that points to the thumbnail of the animation from the web or a file_id of a thumbnail already on the Telegram's servers.
* @param int $flag [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param string $caption [Optional] The caption of the animation.
* @param int $messageId [Optional] The id of the message you want to respond to.
* @param array $keyboard [Optional] The layout of the keyboard to send.
*
* @return mixed On success, the Message sent by the method.
*/
function sendAnimation($chatId, string $animation, int $duration = 0, int $width = 0, int $height = 0, string $thumb = '', int $flags = 0, string $caption = '', int $messageId = 0, array $keyboard = []) {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

	$parseMode = 'HTML';
	$mute = FALSE;

	/**
	* Check if the animation must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($animation, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$animation = urlencode($animation);
	}

	/**
	* Check if the thumbnail of the animation must be encoded
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
	* Check if the caption of the animation must be encoded
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

	$url = "sendAnimation?chat_id=$chatId&animation=$animation&parse_mode=$parseMode&disable_notification=$mute";

	/**
	* Check if the caption of the animation exists
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
	* Check if the thumbnail of the animation exists
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
	* Check if the animation have a specific duration
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
	* Check if the animation have a specific width
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
	* Check if the animation have a specific height
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

	$msg = requestBotAPI($url);

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
* Sends an Audio file, if you want Telegram clients to display them in the music player,
* to the chat pointed from $chatId and returns the Message object of the sent message.
* The audio is identified by a string that can be both a file_id or an HTTP URL of an audio on internet.
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
* @return mixed On success, the Message sent by the method.
*/
function sendAudio($chatId, string $audio, string $caption = '', int $flags = 0, int $duration = 0, string $performer = '', string $title = '', string $thumb = '', int $messageId = 0, array $keyboard = []) {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

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

	$msg = requestBotAPI($url);

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
* @return mixed On success, the Message sent by the method.
*/
function sendMediaGroup($chatId, array $media, int $flags = 0, int $messageId = 0) {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

	$mute = FALSE;

	// Check if the message must be muted
	if ($flags & DISABLE_NOTIFICATION) {
		$mute = TRUE;
	}

	foreach ($media as $singleMedia) {
		/**
		* Check if the media isn't a supported object
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
		if (empty($singleMedia['type']) || empty($singleMedia['media'])) {
			// Check if function must be logged
			if (LOG_LVL > 3){
				sendLog(__FUNCTION__, [
					'error' => "A media into the array isn't a supported object."
				]);
			}
			continue;
		/**
		* Check if the media isn't a supported media
		*
		* in_array() Checks if a value exists in an array
		*/
		} else if (in_array($singleMedia['type'], [
			'photo',
			'video'
		]) === FALSE) {
			// Check if function must be logged
			if (LOG_LVL > 3){
				sendLog(__FUNCTION__, [
					'error' => "The type of a media into the array isn't supported."
				]);
			}
			continue;
		}

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

	$msg = requestBotAPI($url);

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
* @return mixed On success, the Message sent by the method.
*/
function sendMessage($chatId, string $text, int $flags = 0, array $keyboard = [], int $messageId = 0) {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

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

	$msg = requestBotAPI($url);

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
* Sends a Photo (identified by a string that can be both a file_id or an HTTP URL of a photo on internet)
* to the chat pointed from $chatId and returns the Message object of the sent message.
*
* @param int/string $chatId The id/username of the chat/channel/user to send the photo.
* @param string $photo The URL that points to a photo from the web or a file_id of a photo already on the Telegram's servers.
* @param int $flag [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param string $caption [Optional] The caption of the photo.
*
* @return mixed On success, the Message sent by the method.
*/
function sendPhoto($chatId, string $photo, int $flags = 0, string $caption = '') {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

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

	$url = "sendPhoto?chat_id=$chatId&photo=$photo&parse_mode=$parseMode&disable_notification=$mute";

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

	$msg = requestBotAPI($url);

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
* Sends a Video (identified by a string that can be both a file_id or an HTTP URL of a video on internet)
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
* @return mixed On success, the Message sent by the method.
*/
function sendVideo($chatId, string $video, int $duration = 0, int $width = 0, int $height = 0, string $thumb = '', int $flags = 0, string $caption = '', int $messageId = 0, array $keyboard = []) {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

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

	$msg = requestBotAPI($url);

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
* The audio is identified by a string that can be both a file_id or an HTTP URL of an audio on internet.
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
* @return mixed On success, the Message sent by the method.
*/
function sendVoice($chatId, string $voice, string $caption = '', int $flags = 0, int $duration = 0, int $messageId = 0, array $keyboard = []) {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

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

	$msg = requestBotAPI($url);

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
* @return bool On success, TRUE.
*/
function setMyCommands(array $commands) : bool {
	foreach ($commands as $command) {
		/**
		* Check if the command isn't a supported object
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
		if (empty($command['command']) || empty($command['description'])) {
			// Check if function must be logged
			if (LOG_LVL > 3){
				sendLog(__FUNCTION__, [
					'error' => "A command into the array isn't a supported object."
				]);
			}
			continue;
		}

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

	$result = requestBotAPI("setMyCommands?commands=$commands");

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
* @return bool On success, TRUE.
*/
function unbanChatMember($chatId, $userId) : bool {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return FALSE;
	/**
	* Check if the id of the user isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	} else if (gettype($userId) !== 'integer' && gettype($userId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The user_id isn't a supported object."
			]);
		}
		return FALSE;
	}

	$result = requestBotAPI("unbanChatMember?chat_id=$chatId&user_id=$userId");

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
* @return bool On success, TRUE.
*/
function unpinChatMessage($chatId) : bool {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return FALSE;
	}

	$result = requestBotAPI("unpinChatMessage?chat_id=$chatId");

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
* Sends a Documment (identified by a string that can be both a file_id or an HTTP URL of a document on internet)
* to the chat pointed from $chatId and returns the Message object of the sent message.
*
* @param int/string $chatId The id/username of the chat/channel/user to send the document.
* @param string $document The URL that points to a document from the web or a file_id of a document already on the Telegram's servers.
* @param int $flag [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param string $caption [Optional] The caption of the document.
* @param string $replyMessageId [Optional] The id of the message to respond to.
* 
* @return mixed On success, the Message sent by the method.
*/
function sendDocument($chatId, string $document, int $flags = 0, string $caption = '', int $replyMessageId = 0) {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return;
	}

	$parseMode = 'HTML';
	$mute = FALSE;

	/**
	* Check if the document must be encoded
	*
	* strpos() Check if the '\n' character is into the string
	*/
	if (strpos($document, "\n")) {
		/**
		* Encode the URL
		*
		* urlencode() Encode the URL, converting all the special character to its safe value
		*/
		$document = urlencode($document);
	}

	/**
	* Check if the caption of the document must be encoded
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

	$url = "sendDocument?chat_id=$chatId&document=$document&parse_mode=$parseMode&disable_notification=$mute";

	// Check if the document is a response to a message
	if ($replyMessageId !== 0) {
		$url .= "&reply_to_message_id=$replyMessageId";
	}

	/**
	* Check if the caption of the document exists
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

	$msg = requestBotAPI($url);

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
* Kick/Ban a member from a group, supergroup or a channel. In case of supergroups and channels, the user will not be able to 
* return on their own using invite links, unless unbanned first.
*
* @param int/string $chatId The id/handle of the group/supergroup/channel
* @param int $userId The id of the user to kick/ban
* @param int $untilDate [Optional] The Unix timestamp of the date in which the user will be unbanned. If not used, the ban si permanent.
* 
* @return bool On success, returns TRUE. FALSE otherwise.
*/
function kickChatMember($chatId, int $userId, int $untilDate = 0) : bool {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return FALSE;
	/**
	* Check if the id of the user isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	} else if (gettype($userId) !== 'integer' && gettype($userId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The user_id isn't a supported object."
			]);
		}
		return FALSE;
	}

	$url = "kickChatMember?chat_id=$chatId&user_id=$userId";

	// Check if an untilDate has been set
	if ($untilDate === 0) {
		// Current Unix timestamp + 20 seconds, telegram will see this as a permanent ban (becouse under 30 seconds from the request)
		$untilDate = time() + 20;
	}

	$url .= "&until_date=$untilDate";

	$result = requestBotAPI($url);

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
* Leave a specified group/supergroup/channel.
*
* @param int/string $chatId The id/username of the group/supergroup/channel that we want to leave.
*
* @return bool On success, TRUE.
*/
function leaveChat($chatId) : bool {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return FALSE;
	}

	$result = requestBotAPI("leaveChat?chat_id=$chatId");

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
* Sets chat members privileges.
*
* @param int/string $chatId The id/username of the group/supergroup/channel
* @param int $userId The id of the username we want to change the privileges to
* @param int $flags Pipe to set more options
*	CAN_CHANGE_INFO: User can change group/channel info
*	CAN_POST_MESSAGES: User can post messages
*	CAN_EDIT_MESSAGES: User can edit messages
*	CAN_DELETE_MESSAGES: User can delete messages
*	CAN_INVITE_USERS: User can invite other users to the chat
*	CAN_RESTRICT_MEMBERS: User can restrict, ban or unban chat members
*	CAN_PIN_MESSAGES: User can pin messages
*	CAN_PROMOTE_MEMBERS: User can promote other users to administrator role, with a subset of his own privileges
*
* @return bool On success, TRUE.
*/
function promoteChatMember($chatId, int $userId, int $flags) : bool {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return FALSE;
	/**
	* Check if the id of the user isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	} else if (gettype($userId) !== 'integer' && gettype($userId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The user_id isn't a supported object."
			]);
		}
		return FALSE;
	}

	$canChangeInfo = FALSE;
	$canPostMessages = FALSE;
	$canEditMessages = FALSE;
	$canDeleteMessages = FALSE;
	$canInviteUsers = FALSE;
	$canRestrictMembers = FALSE;
	$canPinMessages = FALSE;
	$canPromoteMembers = FALSE;

	// Check if permission is setted 
	if ($flags & CAN_CHANGE_INFO) {
		$canChangeInfo = TRUE;
	}

	// Check if permission is setted 
	if ($flags & CAN_POST_MESSAGES) {
		$canPostMessages = TRUE;
	}

	// Check if permission is setted 
	if ($flags & CAN_EDIT_MESSAGES) {
		$canEditMessages = TRUE;
	}

	// Check if permission is setted 
	if ($flags & CAN_DELETE_MESSAGES) {
		$canDeleteMessages = TRUE;
	}

	// Check if permission is setted 
	if ($flags & CAN_INVITE_USERS) {
		$canInviteUsers = TRUE;
	}

	// Check if permission is setted 
	if ($flags & CAN_RESTRICT_MEMBERS) {
		$canRestrictMembers = TRUE;
	}

	// Check if permission is setted 
	if ($flags & CAN_PIN_MESSAGES) {
		$canPinMessages = TRUE;
	}

	// Check if permission is setted 
	if ($flags & CAN_PROMOTE_MEMBERS) {
		$canPromoteMembers = TRUE;
	}

	$url = "promoteChatMember?chat_id=$chatId&user_id=$userId";
	$url .= "&can_change_info=$canChangeInfo";
	$url .= "&can_post_messages=$canPostMessages";
	$url .= "&can_edit_messages=$canEditMessages";
	$url .= "&can_delete_messages=$canDeleteMessages";
	$url .= "&can_invite_users=$canInviteUsers";
	$url .= "&can_restrict_members=$canRestrictMembers";
	$url .= "&can_pin_messages=$canPinMessages";
	$url .= "&can_promote_members=$canPromoteMembers";

	$result = requestBotAPI($url);

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
* Restrict chat member privileges.
*
* @param int/string $chatId The id/username of the supergroup
* @param int $userId The id of the user we want to change the privileges to
* @param array $permissions The array of permissions we want to set
* @param int $untilDate [Optional] The Unix timestamp of the date in which the restrictions will cease. If not used, the restrictions will be permanent.
*
* @return bool On success, TRUE.
*/
function restrictChatMember($chatId, int $userId, array $permissions, int $untilDate = 0) : bool {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return FALSE;
	/**
	* Check if the id of the user isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	} else if (gettype($userId) !== 'integer' && gettype($userId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The user_id isn't a supported object."
			]);
		}
		return FALSE;
	}

	$url = "restrictChatMember?chat_id=$chatId&user_id=$userId";

	/**
	* Check if the message have a permission array
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
	if (empty($permissions) === FALSE) {
		/**
		* Encode the keyboard layout
		*
		* json_encode() Convert the PHP object to a JSON string
		*/
		$permissions = json_encode($permissions);

		$url .= "&permissions=$permissions";
	} else {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "Permissions parameter can't be empty."
			]);
		}
		return FALSE;
	}

	// Check if an untilDate has been set
	if ($untilDate === 0) {
		// Current Unix timestamp + 20 seconds, telegram will see this as a permanent ban (becouse under 30 seconds from the request)
		$untilDate = time() + 20;
	}

	$url .= "&until_date=$untilDate";

	$result = requestBotAPI($url);

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
* Set chat permissions (if Bot is admin and has privileges).
*
* @param int/string $chatId The id/username of the group/supergroup
* @param array $permissions The array of permissions we want to set
*
* @return bool On success, TRUE.
*/
function setChatPermissions($chatId, array $permissions) : bool {
	/**
	* Check if the id of the chat isn't a supported object
	*
	* gettype() return the type of its argument
	* 	'boolean'
    * 	'integer'
    * 	'double' (for historical reasons 'double' is returned in case of a float, and not simply 'float')
    * 	'string'
    * 	'array'
    * 	'object'
    * 	'resource'
    * 	'resource (closed)'
    * 	'NULL'
    * 	'unknown type'
	*/
	if (gettype($chatId) !== 'integer' && gettype($chatId) !== 'string') {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "The chat_id isn't a supported object."
			]);
		}
		return FALSE;
	}

	$url = "setChatPermissions?chat_id=$chatId";

	/**
	* Check if the message have a permission array
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
	if (empty($permissions) === FALSE) {
		/**
		* Encode the keyboard layout
		*
		* json_encode() Convert the PHP object to a JSON string
		*/
		$permissions = json_encode($permissions);

		$url .= "&permissions=$permissions";
	} else {
		// Check if function must be logged
		if (LOG_LVL > 3){
			sendLog(__FUNCTION__, [
				'error' => "Permissions parameter can't be empty."
			]);
		}
		return FALSE;
	}

	$result = requestBotAPI($url);

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
