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
			"\t"
			"\n",
			' ',
			'"',
			'#',
			'$',
			'%',
			"'",
			',',
			';',
			'@',
		], [
			'%09',
			'%0A%0D',
			'%20',
			'%22',
			'%23',
			'%24',
			'%25',
			'%27',
			'%2C',
			'%3B',
			'%40'
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
	* @param int $chat_id the userid
	* @param string $text The message to send
	*
	* @return mixed $result Result of the encode
	*/
	function sendMessage($chat_id, $text) {
		if (strpos($text, "\n")) {
			$text = urlencode($text);
		}
		
		$msg = request("sendMessage?text=$text&chat_id=$chat_id&parse_mode=HTML&disable_web_page_preview=TRUE");
		
		if (LOG_LVL > 3 && $chat_id != LOG_CHANNEL) {
			sendDebugRes(__FUNCTION__,$msg);
		}
		return $msg;
	}

	/**
	* Send a message using Markdown parse mode.
	*
	* @param int $id the userid
	* @param string $url_text The message to send
	*
	* @return mixed The result of the encode
	*/
	function sendMessageMD($id, $url_text) {
		if (strpos($url_text, "\n")) {
			$url_text = urlencode($url_text);
		}
		
		return request("sendMessage?text=$url_text&parse_mode=markdown&chat_id=$id&disable_web_page_preview=TRUE");
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
?>
