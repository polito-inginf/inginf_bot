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
* @param string $text Text to be sent in the notification
* @param int $flags [Optional] Pipe to set more options
* 	SHOW_ALERT: enables alert instead of top notification
* @param string $url [Optional] Url to be opened
*
* @return mixed The result of the encode
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

	// Check if a url parameter is present
	if($url !== "") {
		$requestUrl .= "&url=$url";
	}
	
	return request($requestUrl);
}

/**
* Answers to an InlineQuery
* @todo Do we want to pass the results as a parameter or find the results inside the function?
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
* @param int $messageId [Optional] The id of the message you want to respond to
*
* @return mixed Result of the encode
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
	
	// Check if the message must reply to another one
	if($messageId !== 0) {
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

/**
* Deletes a message
* 
* @param int/string $chatId The id/username of the chat/channel/user where the message is located
* @param string $messageId The id of the message to delete
* 
* @return boolean TRUE on success
*/
function deleteMessage($chatId, int $messageId) {
	$response =  request("deleteMessage?chat_id=$chatId&message_id=$messageId");

	// Check if function must be logged
	if (LOG_LVL > 3){
		/**
		 * @todo improve this log, the response should print also the chatId and messageId
		 */
		sendLog(__FUNCTION__, $response);
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
* @param int/string $chatId The id/username of the chat/channel/user where we want edit the message
* @param int $messageId The id of the message to modify
* @param string $caption The caption to send
* @param int $flags [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* @param array $keyboard [Optional] Keyboard layout to send
*
* @return mixed The result of the encode
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
* Reply to a message
*
* @param int/string $chatId The id/username of the chat/channel/user where we want send the message
* @param string $text The message to send
* @param int $messageId The id of the message you want to respond to
* @param int $flags [Optional] Pipe to set more options
* 	MARKDOWN: enables Markdown parse mode
* 	ENABLE_PAGE_PREVIEW: enables preview for links
* 	DISABLE_NOTIFICATIONS: mutes notifications
* @param array $keyboard [Optional] Keyboard layout to send
*
* @return mixed Result of the encode
*/
function replyToMessage($chatId, string $text, int $messageId, int $flags = 0, array $keyboard = []) {
	return sendMessage($chatId, $text, $flags, $keyboard, $messageId);
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
		
	$url = "exportChatInviteLink?chat_id=$chatId";

	$inviteLink = request($url);
	
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

	$url = "getChatAdministrators?chat_id=$chatId";

	$adminsArray = request($url);
	
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

	$url = "getChatMember?chat_id=$chatId";

	$chatMember = request($url);
	
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

	$url = "getFile?file_id=$fileId";

	$file = request($url);
	
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