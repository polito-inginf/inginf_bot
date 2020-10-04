<?php
/**
 * These are all the base functions that will be used for communicating
 * with Telegram Bot.
 *
 * No libraries are used in this project.
 */

/**
 * Encodes a created URL to Telegram
 *
 * @param string $url the URL to encode
 *
 * @return mixed The result of the encode
 */
function request($url) {
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

	$curl_session = curl_init($url);

	curl_setopt_array($curl_session, [
		CURLOPT_HEADER => FALSE,
		CURLOPT_RETURNTRANSFER => TRUE
	]);

	$result = curl_exec($curl_session);

	curl_close($curl_session);
	return $result;
}

/**
 * Send a message using HTML parse mode.
 *
 * @param int $chat_id The userid
 * @param string $text The message to send
 * @param int $flags [Optional] Pipe to set more options.<br><br>
 * MARKDOWN: enables Markdown parse mode<br>
 * ENABLE_PAGE_PREVIEW: enables preview for links<br>
 * DISABLE_NOTIFICATIONS: mutes notifications
 *
 * @return mixed $result Result of the encode
 */
function sendMessage($chat_id, $text, $flags = 0) {
	if (strpos($text, "\n")) {
		$text = urlencode($text);
	}
	$parse_mode = "HTML";
	$disable_preview = TRUE;
	$mute = FALSE;
	if ($flags & MARKDOWN) {
		$parse_mode = "markdown";
	}
	if ($flags & ENABLE_PAGE_PREVIEW) {
		$disable_preview = FALSE;
	}
	if ($flags & DISABLE_NOTIFICATION) {
		$mute = TRUE;
	}
	$msg = request("sendMessage?text=$text&chat_id=$chat_id&parse_mode=$parse_mode&disable_web_page_preview=$disable_preview&disable_notification=$mute");

	if (LOG_LVL > 3 && $chat_id != LOG_CHANNEL) {
		sendDebugRes(__FUNCTION__, $msg);
	}
	return $msg;
}

/**
 * Gets the keyboard layout and send it to the user. A new message is created.
 *
 * @param array $layout Keyboard layout to send
 * @param int $id userid
 * @param string $msg_text Message text to send using HTML parse mode
 *
 * @return mixed The result of the encode
 */
function inlinekeyboard($layout, $id, $msg_text) {
	if (strpos($msg_text, "\n")) {
		$msg_text = urlencode($msg_text);
	}

	$keyboard = json_encode([
		"inline_keyboard" => $layout
	]);
	return request("sendMessage?text=$msg_text&parse_mode=HTML&chat_id=$id&reply_markup=$keyboard&disable_web_page_preview=TRUE");
}

/**
 * Gets the keyboard layout and send it to the user. A new message is created.
 *
 * @param array $layout Keyboard layout to send
 * @param int $id userid
 * @param string $msg_text Message text to send using Markdown parse mode
 *
 * @return mixed The result of the encode
 */
function inlinekeyboardMD($layout, $id, $msg_text) {
	if (strpos($msg_text, "\n")) {
		$msg_text = urlencode($msg_text);
	}

	$keyboard = json_encode([
		"inline_keyboard" => $layout
	]);
	return request("sendMessage?text=$msg_text&parse_mode=markdown&chat_id=$id&reply_markup=$keyboard&disable_web_page_preview=TRUE");
}

/**
 * Updates the keyboard without sending a new message, but modifies the existing one
 *
 * @param array $layout Keyboard layout to send
 * @param int $id user id
 * @param int $msg_id message id to modify
 *
 * @return mixed The result of the encode
 */
function updateKeyboard($layout, $id, $msg_id) {
	$keyboard = json_encode([
		"inline_keyboard" => $layout
	]);
	return request("editMessageReplyMarkup?chat_id=$id&message_id=$msg_id&reply_markup=$keyboard");
}

/**
 * Edits a sent message (including Keyboard Layout).
 *
 * @param array $layout Keyboard layout to send
 * @param int $id user id
 * @param int $msg_id Message text to modify
 * @param string $msg_text Message text to send
 *
 * @return mixed The result of the encode
 */
function editText($layout, $id, $msg_id, $msg_text) {
	$keyboard = json_encode([
		"inline_keyboard" => $layout
	]);
	return request("editMessageText?chat_id=$id&message_id=$msg_id&reply_markup=$keyboard&text=$msg_text&parse_mode=HTML&disable_web_page_preview=TRUE");
}

/**
 * Answers to an InlineQuery
 *
 * @param int $query_id Query id
 * @param array $ans The answers
 *
 * @return mixed The result of the encode
 */
function ansquery($query_id, $ans) {
	$res = json_encode($ans);
	return request("answerInlineQuery?inline_query_id=$query_id&results=$res");
}

/**
 * Answers to a CallbackQuery
 *
 * @param int $callback_id CallbackQuery id
 *
 * @return mixed The result of the encode
 */
function answerCallbackQuery($callback_id) {
	return request("answerCallbackQuery?callback_query_id=$callback_id");
}

/**
 * Sends a Photo (identified by a string that can be both a file_id or an HTTP URL of a pic on internet)
 *  to the chat pointed from $chatid and returns the Message object of the sent message
 * 
 * @param int $chatid  the user id
 * @param string $photo it's an URL that points to a photo from the web OR a file_id of a photo already on telegram
 * @param int $flag [Optional] Pipe to set more options.<br><br>
 * MARKDOWN: enables Markdown parse mode<br>
 * DISABLE_NOTIFICATIONS: mutes notifications
 * @param string $caption [Optional] the caption 					
 * 
 * @return mixed The Message sent by the method
 */
function sendPhoto($chatid, $photo, $flags = 0, $caption = "") {
	//encoding $photo (a string) to be sent through url call to sendPhoto method
	if (strpos($photo, "\n")){
		$photo = urlencode($photo);
	}
	//encoding $caption (a string) to be sent through url call to sendPhoto method
	if (strpos($caption, "\n")){
		$caption = urlencode($caption);
	}

	$parse_mode = "HTML";
	$mute = FALSE;
	if($flags & DISABLE_NOTIFICATIONS){
		$mute = TRUE;
	}
	if($flags & MARKDOWN){
		$parse_mode = "markdown";
	}
	
	//calls telegram API's sendPhoto method with selected optional parameters
	$msg = request("sendPhoto?chat_id=$chatid&photo=$photo&caption=$caption&parse_mode=$parse_mode&disable_notification=$mute");

	//checks for the debug level and eventually calls the logger
	if (LOG_LVL > 3 && $chatid != LOG_CHANNEL){
		sendDebugRes(__FUNCTION__, $msg);
	}

	//decodes the JSON OBJECT returned from the telegram method
	$decodedAnswer = json_decode($msg, TRUE);
	//returns the Message object if the operation succeded, returns NULL otherwise
	return $decodedAnswer['ok'] == TRUE ? $decodedAnswer['result'] : NULL ;
}

/**
 * Returns an up-to-date information about a chat with a certain chat_id through a Chat object
 * 
 * @param int/string $chatid  the chat id (int) or
 * the user/channel username (string) in the format @username
 * of the chat we want to extract the information from
 * 
 * @return mixed The Chat object of the chat
 */
function getChat($chatid) {
	//calls telegram API's getChat method
	$chat = request("getChat?chat_id=$chatid");

	//checks for the debug level and eventually calls the logger
	if (LOG_LVL > 3){
		sendDebugRes(__FUNCTION__,$chat);
	}

	//decodes the JSON OBJECT returned from the telegram method
	$decodedAnswer = json_decode($chat, TRUE);
	//returns the Chat object	 if the operation succeded, returns NULL otherwise
	return $decodedAnswer['ok'] == TRUE ? $decodedAnswer['result'] : NULL ;
}
