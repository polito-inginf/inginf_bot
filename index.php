<?php
	/**
	* Template for creating a Telegram bot in PHP.
	*
	* This template can be reused in accordance with the GNU GPLv3 license.
	*
	* @author		Giorgio Pais
	* @author		Giulio Coa
	* @copyright	2020- Giorgio Pais <info@politoinginf.it>
	* @license		https://choosealicense.com/licenses/gpl-3.0/
	*/

	// Installing the libraries
	require_once 'vendor/autoload.php';

	// Creating the bot
	class inginf_bot extends danog\MadelineProto\EventHandler {
		private $DB;
		private $tmp;
		private $button_InlineKeyboard;

		/**
		* Get peer(s) where to report errors
		*
		* @return array
		*/
		public function getReportPeers() : array {
			return [
				0		// The log channel
			];
		}

		/**
		* Called on startup, can contain async calls for initialization of the bot
		*
		* @return void
		*/
		public function onStart() : void {
			// Setting the database
			try {
				$this -> DB = yield Amp\Mysql\connect(Amp\Mysql\ConnectionConfig::fromString('host=localhost;user=username;pass=password;db=database_name'));
			} catch (Amp\Sql\ConnectionException $e) {
				$this -> logger('The connection with the MySQL database is failed for ' . $e -> getMessage() . '.', danog\MadelineProto\Logger::FATAL_ERROR);
				exit(1);
			}

			// Set the character set
			$this -> DB -> setCharset('utf8', 'utf8_general_ci');

			$this -> tmp = [];

			// Setting how many buttons an InlineKeyboard must contains (#row = button_InlineKeyboard / 2)
			$this -> button_InlineKeyboard = 8;
		}

		/**
		* Handle updates from CallbackQuery
		*
		* @param array $update Update
		*
		* @return Generator
		*/
		public function onUpdateBotCallbackQuery(array $update) : Generator {
			// Retrieving the data of the user that pressed the button
			$sender = yield $this -> getInfo($update['user_id']);
			$sender = $sender['User'] ?? NULL;

			/**
			* Checking if the sender is a normal user
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
			if (empty($sender) || $sender['_'] !== 'user') {
				$this -> logger('The CallbackQuery ' . $update['query_id'] . ' wasn\'t managed because the sender isn\'t a normal user.');
				return;
			/**
			* Checking if the query is empty
			*
			* empty() check if the query is empty
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
			} else if (empty($callback_data)) {
				$this -> logger('The CallbackQuery ' . $update['query_id'] . ' wasn\'t managed because was empty.');
				return;
			}

			/**
			* Retrieving the callback data
			*
			* trim() strip whitespaces from the begin and the end of the string
			* base64_decode() decode the string
			*/
			$callback_data = trim(base64_decode($update['data']));

			/**
			* Retrieving the command that have generated the CallbackQuery
			*
			* explode() convert a string into an array
			*/
			$command = explode('/', $callback_data)[0];

			// Retrieving the message associated to the CallbackQuery
			$message = yield $this -> messages -> getMessages([
				'id' => [
					$update['msg_id']
				]
			]);

			// Checking if the result is valid
			if ($message['_'] === 'messages.messagesNotModified' || $message['messages'][0]['_'] !== 'message') {
				$this -> logger('The CallbackQuery ' . $update['query_id'] . ' wasn\'t managed because isn\'t associated to a message.');
				return;
			}

			$message = $message['messages'][0];

			// Retrieving the language of the user
			$language = isset($sender['lang_code']) ? $sender['lang_code'] : 'en';

			// Checking if the language is supported
			try {
				yield $this -> DB -> execute('SELECT NULL FROM `Languages` WHERE `lang_code`=?;', [
					$language
				]);
			} catch (Amp\Sql\QueryError $e) {
				$this -> logger('Failed to make the query, because ' . $e -> getMessage(), \danog\MadelineProto\Logger::ERROR);
				$language = 'en';
			} catch (Amp\Sql\FailureException $e) {
				$language = 'en';
			}

			// Setting the new keyboard
			switch ($command) {
				case 'staff_group':
					// Checking if the sender is a bot's admin
					try {
						yield $this -> DB -> execute('SELECT NULL FROM `Admins` WHERE `id`=?;', [
							$sender['id']
						]);
					} catch (Amp\Sql\QueryError $e) {
						$this -> logger('Failed to make the query, because ' . $e -> getMessage(), \danog\MadelineProto\Logger::ERROR);
						return;
					} catch (Amp\Sql\FailureException $e) {
						$this -> logger('The CallbackQuery ' . $update['query_id'] . ' wasn\'t managed because was a message from an unauthorized user (/' . $command . ' section).');
						return;
					}

					// Retrieving the chats' list
					try {
						$result = yield $this -> DB -> query('SELECT `id`, `title` FROM `Chats`;');
					} catch (Amp\Sql\FailureException $e) {
						$this -> logger('Failed to make the query, because ' . $e -> getMessage(), \danog\MadelineProto\Logger::ERROR);
						return;
					}

					$chats = [];

					// Cycle on the result
					while (yield $result -> advance()) {
						$chats []= $result -> getCurrent();
					}

					/**
					* Retrieving the query
					*
					* explode() convert a string into an array
					*/
					$query = explode('/', $callback_data)[1];

					switch ($query) {
						case 'page':
							/**
							* Retrieving the page
							*
							* explode() convert a string into an array
							*/
							$actual_page = (int) explode('/', $callback_data)[2];

							/**
							* Retrieving the button in the page
							*
							* array_splice() extract the sub-array from the main array
							*/
							$chats = array_slice($chats, $actual_page * $this -> button_InlineKeyboard,  $this -> button_InlineKeyboard);

							/**
							* Setting the InlineKeyboard
							*
							* array_map() convert each chat to a keyboardButtonCallback
							*/
							$chats = array_map(function ($n) {
								return [
									'_' => 'keyboardButtonCallback',
									'text' => $n['title'],
									/**
									* Generating the keyboardButtonCallback data
									*
									* base64_encode() encode the string
									*/
									'data' => base64_encode($command . '/' . $n['id'] . '/no')
								];
							}, $chats);

							$row = [
								'_' => 'keyboardButtonRow',
								'buttons' => []
							];
							$keyboard = [
								'_' => 'replyInlineMarkup',
								'rows' => []
							];

							// Cycle on the buttons' list
							foreach ($chats as $button) {
								/**
								* Retrieving the length of the row
								*
								* count() retrieve the length of the array
								*/
								if (count($row['buttons']) == 2) {
									// Saving the row
									$keyboard['rows'] []= $row;

									// Creating a new row
									$row['buttons'] = [];
								}
								// Adding a button to the row
								$row['buttons'] []= $button;
							}

							// Setting the page
							$keyboard['rows'] []= [
								'_' => 'keyboardButtonRow',
								'buttons' => [
									[
										'_' => 'keyboardButtonCallback',
										'text' => $actual_page != 0 ? 'Previous page' : '',
										/**
										* Generating the keyboardButtonCallback data
										*
										* base64_encode() encode the string
										*/
										'data' => base64_encode($actual_page != 0 ? $command . '/page/' . $actual_page - 1 : '')
									],
									[
										'_' => 'keyboardButtonCallback',
										'text' => ($actual_page + 1) * $this -> button_InlineKeyboard > $total ? 'Next page' : '',
										/**
										* Generating the keyboardButtonCallback data
										*
										* base64_encode() encode the string
										*/
										'data' => base64_encode(($actual_page + 1) * $this -> button_InlineKeyboard > $total ? $command . '/page/' . $actual_page + 1 : '')
									]
								]
							];

							// Setting the confirm buttons
							$keyboard['rows'] []= [
								'_' => 'keyboardButtonRow',
								'buttons' => [
									[
										'_' => 'keyboardButtonCallback',
										'text' => 'Reject',
										/**
										* Generating the keyboardButtonCallback data
										*
										* base64_encode() encode the string
										*/
										'data' => base64_encode($command . '/reject')
									],
									[
										'_' => 'keyboardButtonCallback',
										'text' => 'Confirm',
										/**
										* Generating the keyboardButtonCallback data
										*
										* base64_encode() encode the string
										*/
										'data' => base64_encode($command . '/confirm')
									]
								]
							];
							break;
						case 'reject':
							// Retrieving the reject message
							try {
								$answer = yield $this -> DB -> execute('SELECT `reject_message` FROM `Languages` WHERE `lang_code`=?;', [
									$language
								]);
							} catch (Amp\Sql\QueryError $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage(), \danog\MadelineProto\Logger::ERROR);
								$answer = 'Operation deleted.';
							} catch (Amp\Sql\FailureException $e) {
								$answer = 'Operation deleted.';
							}

							// Checking if the query has product a result
							if ($answer instanceof Amp\Mysql\ResultSet) {
								yield $answer -> advance();
								$answer = $answer -> getCurrent();
								$answer = $answer['reject_message'];
							}

							/**
							* Checking if the reject message isn't setted
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
							if (empty($answer)) {
								$answer = 'Operation deleted.';
							}

							/**
							* Checking if is an abort
							*
							* array_key_exists() check if the key exists
							*/
							if (array_key_exists('staff_group', $this -> tmp) && array_key_exists($update['peer'], $this -> tmp['staff_group'])) {
								/**
								* Removing the id from the array
								*
								* array_search() search the id into the array
								* array_splice() extract the sub-array from the main array
								*/
								array_splice($this -> tmp, array_search($update['peer'], $this -> tmp['staff_group']), 1);

								/**
								* Checking if there isn't other request for the /staff_group command
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
								if (empty($this -> tmp['staff_group'])) {
									/**
									* Removing the 'staff_group' key from the array
									*
									* array_search() search the 'staff_group' key into the array
									* array_splice() extract the sub-array from the main array
									*/
									array_splice($this -> tmp, array_search('staff_group', $this -> tmp), 1);
								}
							}

							yield $this -> messages -> editMessage([
								'no_webpage' => TRUE,
								'peer' => $update['peer'],
								'id' => $message['id'],
								'message' => $answer,
								'reply_markup' => [],
								'parse_mode' => 'HTML'
							]);
							return;
						case 'confirm':
							/**
							* Checking if the confirm is pressed for error
							*
							* array_key_exists() check if the key exists
							*/
							if (array_key_exists('staff_group', $this -> tmp) == FALSE || array_key_exists($update['peer'], $this -> tmp['staff_group']) == FALSE) {
								$this -> logger('The CallbackQuery ' . $update['query_id'] . ' wasn\'t managed because the sender have pressed the wrong button (/' . $command . ' section).');
								return;
							}

							// Opening a transaction
							$transaction = yield $this -> DB -> beginTransaction();

							// Updating the staff_group for the selected chats
							try {
								$statement = yield $transaction -> prepare('UPDATE `Chats` SET `staff_group`=? WHERE `id`=?;');
							} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
								return;
							}

							// Cycle on the selected chats
							foreach ($this -> tmp['staff_group'][$update['peer']] as $id) {
								try {
									yield $statement -> execute([
										$update['peer'],
										$id
									]);
								} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
									$this -> logger('Failed to make the query, because ' . $e -> getMessage(), \danog\MadelineProto\Logger::ERROR);
								}
							}

							// Closing the statement
							$statement -> close();

							// Commit the change
							yield $transaction -> commit();

							// Closing the transaction
							yield $transaction -> close();

							/**
							* Removing the id from the array
							*
							* array_search() search the id into the array
							* array_splice() extract the sub-array from the main array
							*/
							array_splice($this -> tmp, array_search($update['peer'], $this -> tmp['staff_group']), 1);

							/**
							* Checking if there isn't other request for the /staff_group command
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
							if (empty($this -> tmp['staff_group'])) {
								/**
								* Removing the 'staff_group' key from the array
								*
								* array_search() search the 'staff_group' key into the array
								* array_splice() extract the sub-array from the main array
								*/
								array_splice($this -> tmp, array_search('staff_group', $this -> tmp), 1);
							}

							// Retrieving the confirm message
							try {
								$answer = yield $this -> DB -> execute('SELECT `confirm_message` FROM `Languages` WHERE `lang_code`=?;', [
									$language
								]);
							} catch (Amp\Sql\QueryError $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage(), \danog\MadelineProto\Logger::ERROR);
								$answer = 'Operation completed.';
							} catch (Amp\Sql\FailureException $e) {
								$answer = 'Operation completed.';
							}

							// Checking if the query has product a result
							if ($answer instanceof Amp\Mysql\ResultSet) {
								yield $answer -> advance();
								$answer = $answer -> getCurrent();
								$answer = $answer['confirm_message'];
							}

							/**
							* Checking if the confirm message isn't setted
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
							if (empty($answer)) {
								$answer = 'Operation completed.';
							}

							yield $this -> messages -> editMessage([
								'no_webpage' => TRUE,
								'peer' => $update['peer'],
								'id' => $message['id'],
								'message' => $answer,
								'reply_markup' => [],
								'parse_mode' => 'HTML'
							]);
							break;
						default:
							// Retrieving the InlineKeyboard
							$keyboard = $message['reply_markup'];

							/**
							* Retrieving the type of the request
							*
							* explode() convert a string into an array
							*/
							$type = explode('/', $callback_data)[2];

							// Cycle on the rows
							foreach ($keyboard['rows'] as $row) {
								// Cycle on the buttons
								foreach ($row['buttons'] as $button) {
									// Checking if the button is what is pressed
									if ($button['data'] == $update['data']) {
										/**
										* Commuting the button
										*
										* base64_encode() encode the string
										* str_replace() replace the special characters with their code
										*/
										$button['data'] = base64_encode($type == 'yes' ? $command . '/' . $query . '/no' : $command . '/' . $query . '/yes');

										$button['text'] = $type == 'yes' ? str_replace(' ✅', '', $button['text']) : $button['text'] . ' ✅';

										// Checking if is a select request
										if ($type == 'yes') {
											/**
											* Checking if the first /staff_group request
											*
											* array_key_exists() check if the 'staff_group' key exists
											*/
											if (array_key_exists('staff_group', $this -> tmp)) {
												/**
												* Checking if the first /staff_group request for this staff group
												*
												* array_key_exists() check if the id exists
												*/
												if (array_key_exists($update['peer'], $this -> tmp['staff_group'])) {
													$this -> tmp['staff_group'][$update['peer']] []= $query;
												} else {
													$this -> tmp['staff_group'] []= [
														$update['peer'] => [
															$query
														]
													];
												}
											} else {
												$this -> tmp []= [
													'staff_group' => [
														$update['peer'] => [
															$query
														]
													]
												];
											}
										} else {
											/**
											* Removing the query from the array
											*
											* array_search() search the query into the array
											* array_splice() extract the sub-array from the main array
											*/
											array_splice($this -> tmp['staff_group'][$update['peer']], array_search($query, $this -> tmp['staff_group'][$update['peer']]), 1);
										}
										break;
									}
								}
								if ($button['data'] == $update['data']) {
									break;
								}
							}
							break;
					}

					yield $this -> messages -> editMessage([
						'peer' => $update['peer'],
						'id' => $message['id'],
						'reply_markup' => $keyboard
					]);
					break;
				default:
					break;
			}
		}

		/**
		* Handle updates from InlineQuery
		*
		* @param array $update Update
		*
		* @return Generator
		*/
		public function onUpdateBotInlineQuery(array $update) : Generator {
			/**
			* Encode the text
			*
			* trim() strip whitespaces from the begin and the end of the string
			* htmlentities() convert all HTML character to its safe value
			*/
			$inline_query = trim($update['query']);
			$inline_query = htmlentities($inline_query, ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_HTML5, 'UTF-8');

			// Retrieving the data of the user that sent the query
			$sender = yield $this -> getInfo($update['user_id']);
			$sender = $sender['User'] ?? NULL;

			/**
			* Checking if the user is a normal user
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
			if (empty($sender) || $sender['_'] !== 'user') {
				$this -> logger('The InlineQuery ' . $update['query_id'] . ' wasn\'t managed because the sender isn\'t a normal user.');
				return;
			/**
			* Checking if the query is empty
			*
			* empty() check if the query is empty
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
			} else if (empty($inline_query)) {
				$this -> logger('The InlineQuery ' . $update['query_id'] . ' wasn\'t managed because was empty.');
				return;
			/**
			* Checking if the query is long enough
			*
			* strlen() return the length of the string
			*/
			} else if (strlen($inline_query) < 3) {
				$this -> logger('The InlineQuery ' . $update['query_id'] . ' wasn\'t managed because was too short.');
				return;
			}


			// Retrieving the language of the user
			$language = isset($sender['lang_code']) ? $sender['lang_code'] : 'en';

			// Checking if the language is supported
			try {
				yield $this -> DB -> execute('SELECT NULL FROM `Languages` WHERE `lang_code`=?;', [
					$language
				]);
			} catch (Amp\Sql\QueryError $e) {
				$this -> logger('Failed to make the query, because ' . $e -> getMessage(), \danog\MadelineProto\Logger::ERROR);
				$language = 'en';
			} catch (Amp\Sql\FailureException $e) {
				$language = 'en';
			}

			//Setting the answer
			$answer = [
				[
					'_' => 'inputBotInlineResult',
					/**
					* Generating the inputBotInlineResult id
					*
					* uniqid() generate a random string
					*/
					'id' => uniqid(),
					'type' => 'article',
					'title' => '',
					'description' => '',
					'url' => '',
					'send_message' => [
						'_' => 'inputBotInlineMessageText',
						'no_webpage' => TRUE,
						'message' => '',
						'reply_markup' => []
					]
				]
			];

			yield $this -> messages -> setInlineBotResults([
				/**
				* Generating the query id
				*
				* mt_rand() generate a random integer number
				*/
				'query_id' => mt_rand(),
				'results' => $answer,
				'cache_time' => 1,
				'switch_pm' => [
					'_' => 'inlineBotSwitchPM',
					'text' => '',
					'start_param' => ''
				]
			]);
		}

		/**
		* Handle updates about edited message from supergroups and channels
		*
		* @param array $update Update
		*
		* @return Generator
		*/
		public function onUpdateEditChannelMessage(array $update) : Generator {
			return $this -> onUpdateNewMessage($update);
		}

		/**
		* Handle updates about edited message from users
		*
		* @param array $update Update
		*
		* @return Generator
		*/
		public function onUpdateEditMessage(array $update) : Generator {
			return $this -> onUpdateNewMessage($update);
		}

		/**
		* Handle updates from supergroups and channels
		*
		* @param array $update Update
		*
		* @return Generator
		*/
		public function onUpdateNewChannelMessage(array $update) : Generator {
			return $this -> onUpdateNewMessage($update);
		}

		/**
		* Handle updates from users and groups
		*
		* @param array $update Update
		*
		* @return Generator
		*/
		public function onUpdateNewMessage(array $update) : Generator {
			$message = $update['message'];

			// Checking if the message is a normal message
			if ($message['_'] === 'messageEmpty') {
				$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was empty.');
				return;
			// Checking if the message is an incoming message
			} else if ($message['out'] ?? FALSE) {
				$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was an incoming message.');
				return;
			}

			// Retrieving the chat's data
			$chat = yield $this -> getPwrChat($message['to_id']['_'] === 'peerUser' ? $message['from_id'] : ($message['to_id']['_'] === 'peerChat' ? $message['to_id']['chat_id'] : $message['to_id']['channel_id']));

			// Retrieving the data of the sender
			$sender = yield $this -> getInfo($message['from_id']);
			$sender = $sender['User'] ?? NULL;

			/**
			* Checking if the user is a normal user
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
			if (empty($sender) || $sender['_'] !== 'user') {
				$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because the sender isn\'t a normal user.');
				return;
			}

			// Checking if the message is a service message
			if ($message['_'] === 'messageService') {
				$answer = NULL;

				// Retrieving the welcome message
				try {
					$answer = yield $this -> DB -> execute('SELECT `welcome` FROM `Chats` WHERE `id`=?;', [
						$chat['id']
					]);
				} catch (Amp\Sql\QueryError $e) {
					$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
				} catch (Amp\Sql\FailureException $e) {
				}

				// Checking if the query has product a result
				if ($answer instanceof Amp\Mysql\ResultSet) {
					yield $answer -> advance();
					$answer = $answer -> getCurrent();
					$answer = $answer['reject_message'];
				}

				// Checking if the service message is about new members
				if ($message['action']['_'] === 'messageActionChatAddUser') {
					$banned = [
						'multiple' = TRUE
					];

					/**
					* 
					* Checking if the welcome message is setted
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
					if (empty($answer) == FALSE) {
						$members = [];
					}

					// Retrieving the bot's data
					$bot = yield $this -> getSelf();

					/**
					* Checking if the bot have joined the (super)group
					*
					* in_array() check if the bot id is into the array
					*/
					if (in_array($bot['id'], $message['action']['users'])) {
						/**
						* Removing the bot id from the new members list
						*
						* array_search() search the bot id into the array
						* array_splice() extract the sub-array from the main array
						*/
						array_splice($message['action']['users'], array_search($bot['id'], $message['action']['users']), 1);

						// Checking if who added the bot is a bot's admin
						try {
							yield $this -> DB -> execute('SELECT NULL FROM `Admins` WHERE `id`=?;', [
								$sender['id']
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							return;
						} catch (Amp\Sql\FailureException $e) {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from an unauthorized chat.');

							// Leaving the chat
							if ($chat['type'] == 'chat') {
								$this -> messages -> deleteChatUser([
									'chat_id' => $chat['id'],
									'user_id' => $bot['id']
								]);
							} else if ($chat['type'] == 'channel' || $chat['type'] == 'supergroup') {
								$this -> channels -> leaveChannel([
									'channel' => $chat['id']
								]);
							}

							$this -> logger('The bot have lefted the unauthorized chat.');
							return;
						}
					}

					// Cycle on the list of the new member
					foreach ($message['action']['users'] as $new_member) {
						/**
						* Downloading the user's informations from the Combot Anti-Spam API
						*
						* json_decode() convert a JSON string into a PHP variables
						*/
						$result = yield $this -> getHttpClient() -> request(new Amp\Http\Client\Request('https://api.cas.chat/check?user_id=' . $new_member));

						// Retrieving the result
						$result = yield $result -> getBody() -> buffer();

						$result = json_decode($result, TRUE);

						// Retrieving the data of the new member
						$new_member = yield $this -> getInfo($new_member);
						$new_member = $new_member['User'] ?? NULL;

						/**
						* Checking if the user isn't a spammer, isn't a deleted account and is a normal user
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
						if ($result['ok'] == FALSE && empty($new_member) && $new_member['_'] === 'user' && $new_member['scam'] == FALSE && $new_member['deleted'] == FALSE) {
							/**
							* Checking if the welcome message is setted
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
							if (empty($answer) == FALSE) {
								$members []= $new_member;
							}
							continue;
						}

						$banned []= [
							'channel' => $update['chat_id'],
							'user_id' => $new_member['id'],
							'banned_rights' => [
								'_' => 'chatBannedRights',
								'view_messages' => TRUE,
								'send_messages' => TRUE,
								'send_media' => TRUE,
								'send_stickers' => TRUE,
								'send_gifs' => TRUE,
								'send_games' => TRUE,
								'send_inline' => TRUE,
								'embed_links' => TRUE,
								'send_polls' => TRUE,
								'change_info' => TRUE,
								'invite_users' => TRUE,
								'pin_messages' => TRUE,
								'until_date' => 0
							]
						];
					}

					yield $this -> channels -> editBanned($banned);

					/**
					* Checking if the welcome message is setted
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
					if (empty($answer) == FALSE) {
						/**
						* Personalizing the message
						*
						* array_map() convert each new member into it's tag
						* implode() convert the array into a string
						* str_replace() replace the 'mentions' tag with the string
						*/
						$members = array_map(function ($n) {
							return '<a href=\"mention:' . $n['id'] . '\" >' . $n['first_name'] . '</a>';
						}, $members);
						$answer = str_replace('${mentions}', implode(', ', $members), $answer);

						yield $this -> messages -> sendMessage([
							'no_webpage' => TRUE,
							'peer' => $update['chat_id'],
							'message' => $answer,
							'parse_mode' => 'HTML'
						]);
					}
				} else if ($message['action']['_'] === 'messageActionChatJoinedByLink') {
					/**
					* Downloading the user's informations from the Combot Anti-Spam API
					*
					* json_decode() convert a JSON string into a PHP variables
					*/
					$result = yield $this -> getHttpClient() -> request(new Amp\Http\Client\Request('https://api.cas.chat/check?user_id=' . $message['from_id']));

					// Retrieving the result
					$result = yield $result -> getBody() -> buffer();

					$result = json_decode($result, TRUE);

					// Retrieving the data of the new member
					$new_member = yield $this -> getInfo($message['from_id']);
					$new_member = $new_member['User'] ?? NULL;

					/**
					* Checking if the user is a spammer, is a deleted account or isn't a normal user
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
					if ($result['ok'] || empty($new_member) || $new_member['_'] !== 'user' || $new_member['scam'] || $new_member['deleted']) {
						yield $this -> channels -> editBanned([
							'channel' => $update['chat_id'],
							'user_id' => $message['from_id'],
							'banned_rights' => [
								'_' => 'chatBannedRights',
								'view_messages' => TRUE,
								'send_messages' => TRUE,
								'send_media' => TRUE,
								'send_stickers' => TRUE,
								'send_gifs' => TRUE,
								'send_games' => TRUE,
								'send_inline' => TRUE,
								'embed_links' => TRUE,
								'send_polls' => TRUE,
								'change_info' => TRUE,
								'invite_users' => TRUE,
								'pin_messages' => TRUE,
								'until_date' => 0
							]
						]);
					}

					/**
					* Checking if the welcome message is setted
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
					if (empty($answer) == FALSE) {
						/**
						* Personalizing the message
						*
						* str_replace() replace the 'mentions' tag with the string
						*/
						$answer = str_replace('${mentions}', '<a href=\"mention:' . $new_member['id'] . '\" >' . $new_member['first_name'] . '</a>', $answer);

						yield $this -> messages -> sendMessage([
							'no_webpage' => TRUE,
							'peer' => $update['chat_id'],
							'message' => $answer,
							'parse_mode' => 'HTML'
						]);
					}
				} else if ($message['action']['_'] === 'messageActionChatMigrateTo') {
					// Opening a transaction
					$transaction = yield $this -> DB -> beginTransaction();

					// Updating the chat's data
					try {
						yield $transaction -> execute('UPDATE `Chats` SET `id`=? WHERE `id`=?;', [
							$message['action']['channel_id'],
							$chat['id']
						]);
					} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
						$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

						// Closing the transaction
						yield $transaction -> close();
						return;
					}

					// Commit the change
					yield $transaction -> commit();

					// Closing the transaction
					yield $transaction -> close();
				} else if ($message['action']['_'] === 'messageActionChannelMigrateFrom') {
					// Opening a transaction
					$transaction = yield $this -> DB -> beginTransaction();

					// Updating the chat's data
					try {
						yield $transaction -> execute('UPDATE `Chats` SET `id`=? WHERE `id`=?;', [
							$chat['id'],
							$message['action']['chat_id']
						]);
					} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
						$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

						// Closing the transaction
						yield $transaction -> close();
						return;
					}

					// Commit the change
					yield $transaction -> commit();

					// Closing the transaction
					yield $transaction -> close();
				}

				yield $this -> channels -> deleteMessages([
					'revoke' => TRUE,
					'id' => [
						$message['id']
					]
				]);
				return;
			}

			// Checking if the chat is an allowed chat
			try {
				yield $this -> DB -> execute('SELECT NULL FROM `Chats` WHERE `id`=?;', [
					$chat['id']
				]);
			} catch (Amp\Sql\QueryError $e) {
				$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
				return;
			} catch (Amp\Sql\FailureException $e) {
				// Checking if the chat is a (super)group or a channel
				if ($chat['type'] != 'user' && $chat['type'] != 'bot') {
					$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from an unauthorized chat.');

					// Leaving the chat
					if ($chat['type'] == 'chat') {
						$bot = yield $this -> getSelf();

						$this -> messages -> deleteChatUser([
							'chat_id' => $chat['id'],
							'user_id' => $bot['id']
						]);
					} else {
						$this -> channels -> leaveChannel([
							'channel' => $chat['id']
						]);
					}

					$this -> logger('The bot have lefted the unauthorized chat.');
				}

				$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
				return;
			}

			// Checking if the chat is a (super)group or a private chat
			if ($chat['type'] != 'user' && $chat['type'] != 'supergroup' && $chat['type'] != 'chat') {
				$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from a bot or a channel.');
				return;
			}

			/**
			* Encode the text
			*
			* trim() strip whitespaces from the begin and the end of the string
			* htmlentities() convert all HTML character to its safe value
			*/
			$message['message'] = trim($message['message']);
			$message['message'] = htmlentities($message['message'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_HTML5, 'UTF-8');

			$result = NULL;

			// Checking if the user is in the bot's blacklist
			try {
				$result = yield $this -> DB -> execute('SELECT NULL FROM `Blacklist` WHERE `id`=?;', [
					$sender['id']
				]);
			} catch (Amp\Sql\QueryError $e) {
				$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
				return;
			} catch (Amp\Sql\FailureException $e) {
			}

			// Checking if the query has product a result
			if ($result instanceof Amp\Mysql\ResultSet) {
				$this -> logger('<a href=\"mention:' . $sender['id'] . '\" >' . $sender['first_name'] . '</a> tried to use the bot.');
				return;
			}

			// Retrieving the language of the user
			$language = isset($sender['lang_code']) ? $sender['lang_code'] : 'en';

			// Checking if the language is supported
			try {
				yield $this -> DB -> execute('SELECT NULL FROM `Languages` WHERE `lang_code`=?;', [
					$language
				]);
			} catch (Amp\Sql\QueryError $e) {
				$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
				$language = 'en';
			} catch (Amp\Sql\FailureException $e) {
				$language = 'en';
			}

			/**
			* Checking if is an @admin tag
			*
			* preg_match() perform a RegEx match
			*/
			if (preg_match('/^\@admin([[:blank:]\n]{1}((\n|.)*))?$/miu', $message['message'], $matches)) {
				// Checking if the chat is a private chat
				if ($message['to_id']['_'] === 'peerUser') {
					$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from a private chat (@admin section).');
					return;
				}

				/**
				* Retrieving the admins list
				*
				* array_filter() filters the array by the role of each member
				* array_map() convert each admins to its id
				*/
				$admins = array_filter($chat['participants'], function ($n) {
					return $n['role'] == 'admin' || $n['role'] == 'creator';
				});
				$admins = array_map(function ($n) {
					return $n['user'];
				}, $admins);

				// Retrieving the admin message
				try {
					$answer = yield $this -> DB -> execute('SELECT `admin_message` FROM `Languages` WHERE `lang_code`=?;', [
						$language
					]);
				} catch (Amp\Sql\QueryError $e) {
					$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
					$answer = '<a href=\"mention:${admin_id}\" >${admin_first_name}</a>,\n<a href=\"mention:${sender_id}\" >${sender_first_name}</a> needs your help${motive} into <a href=\"${chat_invite}\" >${chat_title}</a>.';
				} catch (Amp\Sql\FailureException $e) {
					$answer = '<a href=\"mention:${admin_id}\" >${admin_first_name}</a>,\n<a href=\"mention:${sender_id}\" >${sender_first_name}</a> needs your help${motive} into <a href=\"${chat_invite}\" >${chat_title}</a>.';
				}

				// Checking if the query has product a result
				if ($answer instanceof Amp\Mysql\ResultSet) {
					yield $answer -> advance();
					$answer = $answer -> getCurrent();
					$answer = $answer['admin_message'];
				}

				/**
				* Checking if the admin message isn't setted
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
				if (empty($answer)) {
					$answer = '<a href=\"mention:${admin_id}\" >${admin_first_name}</a>,\n<a href=\"mention:${sender_id}\" >${sender_first_name}</a> needs your help${motive} into <a href=\"${chat_invite}\" >${chat_title}</a>.';
				}

				/**
				* Personalizing the admin message
				*
				* str_replace() replace the tags with their value
				*/
				$answer = str_replace('${sender_id}', $sender['id'], $answer);
				$answer = str_replace('${sender_first_name}', $sender['first_name'], $answer);
				$answer = str_replace('${motive}', ($matches[2] ?? FALSE) ? ' for ' . $matches[2] : '', $answer);
				$answer = str_replace('${chat_invite}', $chat['invite'], $answer);
				$answer = str_replace('${chat_title}', $chat['title'], $answer);

				$message = [
					'multiple' => true
				];

				foreach ($admins as $user) {
					$message []= [
						'no_webpage' => TRUE,
						'peer' => $user['id'],
						'message' => str_replace('${admin_id}', $user['id'], str_replace('${admin_first_name}', $user['first_name'], $answer)),
						'parse_mode' => 'HTML'
					];
				}

				yield $this -> messages -> sendMessage($message);

				// Sending the report to the channel
				$this -> report('<a href=\"mention:' . $sender['id'] . '\" >' . $sender['first_name'] . '</a> has sent an @admin request into <a href=\"' . $chat['exported_invite'] . '\" >' . $chat['title'] . '</a>.');
			/**
			* Checking if is a bot command
			*
			* preg_match() perform a RegEx match
			*/
			} else if (preg_match('/^\/([[:alnum:]\@]+)[[:blank:]]?([[:alnum:]]|[^\n]+)?$/miu', $message['message'], $matches)) {
				/**
				* Retrieving the command
				*
				* explode() convert a string into an array
				*/
				$command = explode('@', $matches[1])[0];
				$args = $matches[2] ?? NULL;

				switch ($command) {
					case 'add':
					case 'remove':
						// Checking if the sender is a bot's admin
						try {
							yield $this -> DB -> execute('SELECT NULL FROM `Admins` WHERE `id`=?;', [
								$sender['id']
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							return;
						} catch (Amp\Sql\FailureException $e) {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from an unauthorized user (/' . $command . ' section).');
							return;
						}

						// Checking if the chat is a private chat
						if ($message['to_id']['_'] === 'peerUser') {
							// Checking if is an add request
							if ($command == 'add') {
								// Retrieving the add_lang message
								try {
									$answer = yield $this -> DB -> execute('SELECT `add_lang_message` FROM `Languages` WHERE `lang_code`=?;', [
										$language
									]);
								} catch (Amp\Sql\QueryError $e) {
									$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
									$answer = 'Send me a message with this format:' . "\n\n" . '<code>lang_code: &lt;insert here the lang_code of the language&gt;' . "\n" . 'add_lang_message: &lt;insert here the message for the /add command when a user want add a language&gt;' . "\n" . 'admin_message: &lt;insert here the message for the @admin tag&gt;' . "\n" . 'confirm_message: &lt;insert here a generic confirm message&gt;' . "\n" . 'help_message: &lt;insert here the message for the /help command&gt;' . "\n" . 'invalid_parameter_message: &lt;insert here the message that will be sent when a user insert an invalid parameter into a command&gt;' . "\n" . 'invalid_syntax_message: &lt;insert here the message that will be sent when a user send a command with an invalid syntax&gt;' . "\n" . 'mute_message: &lt;insert here the message for the /mute command&gt;' . "\n" . 'mute_advert_message: &lt;insert here the message for when the /mute command is used with time set to forever&gt;' . "\n" . 'link_message: &lt;insert here the message for the /link command&gt;' . "\n" . 'reject_message: &lt;insert here a generic reject message&gt;' . "\n" . 'staff_group_message: &lt;insert here the message for the /staff_group command&gt;' . "\n" . 'start_message: &lt;insert here the message for the /start command&gt;' . "\n" . 'unknown_message: &lt;insert here the message for the unknown commands&gt;' . "\n" . 'update_message: &lt;insert here the message for the /update command&gt;</code>' . "\n\n" . '<b>N.B.</b>: If you want insert a new line in the messages, you must codify it as <code>\n</code>.';
								} catch (Amp\Sql\FailureException $e) {
									$answer = 'Send me a message with this format:' . "\n\n" . '<code>lang_code: &lt;insert here the lang_code of the language&gt;' . "\n" . 'add_lang_message: &lt;insert here the message for the /add command when a user want add a language&gt;' . "\n" . 'admin_message: &lt;insert here the message for the @admin tag&gt;' . "\n" . 'confirm_message: &lt;insert here a generic confirm message&gt;' . "\n" . 'help_message: &lt;insert here the message for the /help command&gt;' . "\n" . 'invalid_parameter_message: &lt;insert here the message that will be sent when a user insert an invalid parameter into a command&gt;' . "\n" . 'invalid_syntax_message: &lt;insert here the message that will be sent when a user send a command with an invalid syntax&gt;' . "\n" . 'mute_message: &lt;insert here the message for the /mute command&gt;' . "\n" . 'mute_advert_message: &lt;insert here the message for when the /mute command is used with time set to forever&gt;' . "\n" . 'link_message: &lt;insert here the message for the /link command&gt;' . "\n" . 'reject_message: &lt;insert here a generic reject message&gt;' . "\n" . 'staff_group_message: &lt;insert here the message for the /staff_group command&gt;' . "\n" . 'start_message: &lt;insert here the message for the /start command&gt;' . "\n" . 'unknown_message: &lt;insert here the message for the unknown commands&gt;' . "\n" . 'update_message: &lt;insert here the message for the /update command&gt;</code>' . "\n\n" . '<b>N.B.</b>: If you want insert a new line in the messages, you must codify it as <code>\n</code>.';
								}

								// Checking if the query has product a result
								if ($answer instanceof Amp\Mysql\ResultSet) {
									yield $answer -> advance();
									$answer = $answer -> getCurrent();
									$answer = $answer['add_lang_message'];
								}

								/**
								* Checking if the add_lang message is setted
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
								if (empty($answer)) {
									$answer = 'Send me a message with this format:' . "\n\n" . '<code>lang_code: &lt;insert here the lang_code of the language&gt;' . "\n" . 'add_lang_message: &lt;insert here the message for the /add command when a user want add a language&gt;' . "\n" . 'admin_message: &lt;insert here the message for the @admin tag&gt;' . "\n" . 'confirm_message: &lt;insert here a generic confirm message&gt;' . "\n" . 'help_message: &lt;insert here the message for the /help command&gt;' . "\n" . 'invalid_parameter_message: &lt;insert here the message that will be sent when a user insert an invalid parameter into a command&gt;' . "\n" . 'invalid_syntax_message: &lt;insert here the message that will be sent when a user send a command with an invalid syntax&gt;' . "\n" . 'mute_message: &lt;insert here the message for the /mute command&gt;' . "\n" . 'mute_advert_message: &lt;insert here the message for when the /mute command is used with time set to forever&gt;' . "\n" . 'link_message: &lt;insert here the message for the /link command&gt;' . "\n" . 'reject_message: &lt;insert here a generic reject message&gt;' . "\n" . 'staff_group_message: &lt;insert here the message for the /staff_group command&gt;' . "\n" . 'start_message: &lt;insert here the message for the /start command&gt;' . "\n" . 'unknown_message: &lt;insert here the message for the unknown commands&gt;' . "\n" . 'update_message: &lt;insert here the message for the /update command&gt;</code>' . "\n\n" . '<b>N.B.</b>: If you want insert a new line in the messages, you must codify it as <code>\n</code>.';
								}

								yield $this -> messages -> sendMessage([
									'no_webpage' => TRUE,
									'peer' => $sender['id'],
									'message' => $answer,
									'reply_to_msg_id' => $message['id'],
									'parse_mode' => 'HTML'
								]);
							} else {
								/**
								* Checking if the command have arguments
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
								if (empty($args) == FALSE) {
									// Checking if the language is into the database
									try {
										yield $this -> DB -> execute('SELECT NULL FROM `Languages` WHERE `lang_code`=?;', [
											$args
										]);
									} catch (Amp\Sql\QueryError $e) {
										$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
										return;
									} catch (Amp\Sql\FailureException $e) {
										// Retrieving the invalid_parameter message
										try {
											$answer = yield $this -> DB -> execute('SELECT `invalid_parameter_message` FROM `Languages` WHERE `lang_code`=?;', [
												$language
											]);
										} catch (Amp\Sql\QueryError $e) {
											$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
											$answer = 'The ${parameter} is invalid.';
										} catch (Amp\Sql\FailureException $e) {
											$answer = 'The ${parameter} is invalid.';
										}

										// Checking if the query has product a result
										if ($answer instanceof Amp\Mysql\ResultSet) {
											yield $answer -> advance();
											$answer = $answer -> getCurrent();
											$answer = $answer['invalid_parameter_message'];
										}

										/**
										* Checking if the invalid_parameter message isn't setted
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
										if (empty($answer)) {
											$answer = 'The ${parameter} is invalid.';
										}

										/**
										* Personalizing the invalid_parameter message
										*
										* str_replace() replace the tags with their value
										*/
										$answer = str_replace('${parameter}', 'lang_code', $answer);

										yield $this -> messages -> sendMessage([
											'no_webpage' => TRUE,
											'peer' => $sender['id'],
											'message' => $answer,
											'reply_to_msg_id' => $message['id'],
											'parse_mode' => 'HTML'
										]);

										$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because the command have a wrong syntax (/' . $command . ' section).');
										return;
									}

									// Opening a transaction
									$transaction = yield $this -> DB -> beginTransaction();

									// Removing the language
									try {
										yield $transaction -> execute('DELETE FROM `Languages` WHERE `lang_code`=?;', [
											$args
										]);
									} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
										$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

										// Retrieving the reject message
										try {
											$answer = yield $this -> DB -> execute('SELECT `reject_message` FROM `Languages` WHERE `lang_code`=?;', [
												$language
											]);
										} catch (Amp\Sql\QueryError $e) {
											$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
											$answer = 'Operation deleted.';
										} catch (Amp\Sql\FailureException $e) {
											$answer = 'Operation deleted.';
										}

										// Checking if the query has product a result
										if ($answer instanceof Amp\Mysql\ResultSet) {
											yield $answer -> advance();
											$answer = $answer -> getCurrent();
											$answer = $answer['reject_message'];
										}

										/**
										* Checking if the reject message isn't setted
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
										if (empty($answer)) {
											$answer = 'Operation deleted.';
										}

										yield $this -> messages -> sendMessage([
											'no_webpage' => TRUE,
											'peer' => $sender['id'],
											'message' => $answer,
											'reply_to_msg_id' => $message['id'],
											'parse_mode' => 'HTML'
										]);

										return;
									}

									// Commit the change
									yield $transaction -> commit();

									// Closing the transaction
									yield $transaction -> close();

									// Retrieving the confirm message
									try {
										$answer = yield $this -> DB -> execute('SELECT `confirm_message` FROM `Languages` WHERE `lang_code`=?;', [
											$language
										]);
									} catch (Amp\Sql\QueryError $e) {
										$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
										$answer = 'Operation completed.';
									} catch (Amp\Sql\FailureException $e) {
										$answer = 'Operation completed.';
									}

									// Checking if the query has product a result
									if ($answer instanceof Amp\Mysql\ResultSet) {
										yield $answer -> advance();
										$answer = $answer -> getCurrent();
										$answer = $answer['confirm_message'];
									}

									/**
									* Checking if the confirm message isn't setted
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
									if (empty($answer)) {
										$answer = 'Operation completed.';
									}

									yield $this -> messages -> sendMessage([
										'no_webpage' => TRUE,
										'peer' => $sender['id'],
										'message' => $answer,
										'reply_to_msg_id' => $message['id'],
										'parse_mode' => 'HTML'
									]);
								} else {
									// Retrieving the invalid_syntax message
									try {
										$answer = yield $this -> DB -> execute('SELECT `invalid_syntax_message` FROM `Languages` WHERE `lang_code`=?;', [
											$language
										]);
									} catch (Amp\Sql\QueryError $e) {
										$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
										$answer = 'The syntax of the command is: <code>${syntax}</code>.';
									} catch (Amp\Sql\FailureException $e) {
										$answer = 'The syntax of the command is: <code>${syntax}</code>.';
									}

									// Checking if the query has product a result
									if ($answer instanceof Amp\Mysql\ResultSet) {
										yield $answer -> advance();
										$answer = $answer -> getCurrent();
										$answer = $answer['invalid_syntax_message'];
									}

									/**
									* Checking if the invalid_syntax message isn't setted
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
									if (empty($answer)) {
										$answer = 'The syntax of the command is: <code>${syntax}</code>.';
									}

									/**
									* Personalizing the invalid_syntax message
									*
									* str_replace() replace the tags with their value
									*/
									$answer = str_replace('${syntax}', '/' . $command . ' &lt;lang_code&gt;', $answer);

									yield $this -> messages -> sendMessage([
										'no_webpage' => TRUE,
										'peer' => $sender['id'],
										'message' => $answer,
										'reply_to_msg_id' => $message['id'],
										'parse_mode' => 'HTML'
									]);

									$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because the command have a wrong syntax (/' . $command . ' section).');
								}
							}
							return;
						}

						/**
						* Checking if the message isn't a message that replies to another message
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
						if (empty($message['reply_to_msg_id'])) {
							// Retrieving the query
							$sql_query = $command == 'add' ? 'INSERT INTO `Chats` (`id`, `type`, `title`, `username`, `invite_link`) VALUES (?, ?, ?, ?, ?);' : 'DELETE FROM `Chats` WHERE `id`=?;';

							// Opening a transaction
							$transaction = yield $this -> DB -> beginTransaction();

							try {
								yield $transaction -> execute($sql_query, $command == 'add' ? [
									$chat['id'],
									$chat['type'],
									$chat['title'],
									$chat['username'],
									$chat['invite']
								] : [
									$chat['id']
								]);
							} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

								// Retrieving the reject message
								try {
									$answer = yield $this -> DB -> execute('SELECT `reject_message` FROM `Languages` WHERE `lang_code`=?;', [
										$language
									]);
								} catch (Amp\Sql\QueryError $e) {
									$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
									$answer = 'Operation deleted.';
								} catch (Amp\Sql\FailureException $e) {
									$answer = 'Operation deleted.';
								}

								// Checking if the query has product a result
								if ($answer instanceof Amp\Mysql\ResultSet) {
									yield $answer -> advance();
									$answer = $answer -> getCurrent();
									$answer = $answer['reject_message'];
								}

								/**
								* Checking if the reject message isn't setted
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
								if (empty($answer)) {
									$answer = 'Operation deleted.';
								}

								yield $this -> messages -> sendMessage([
									'no_webpage' => TRUE,
									'peer' => $chat['id'],
									'message' => $answer,
									'reply_to_msg_id' => $message['id'],
									'parse_mode' => 'HTML'
								]);
								return;
							}

							// Commit the change
							yield $transaction -> commit();

							// Closing the transaction
							yield $transaction -> close();

							// Retrieving the confirm message
							try {
								$answer = yield $this -> DB -> execute('SELECT `confirm_message` FROM `Languages` WHERE `lang_code`=?;', [
									$language
								]);
							} catch (Amp\Sql\QueryError $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
								$answer = 'Operation completed.';
							} catch (Amp\Sql\FailureException $e) {
								$answer = 'Operation completed.';
							}

							// Checking if the query has product a result
							if ($answer instanceof Amp\Mysql\ResultSet) {
								yield $answer -> advance();
								$answer = $answer -> getCurrent();
								$answer = $answer['confirm_message'];
							}

							/**
							* Checking if the confirm message isn't setted
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
							if (empty($answer)) {
								$answer = 'Operation completed.';
							}

							yield $this -> messages -> sendMessage([
								'no_webpage' => TRUE,
								'peer' => $chat['id'],
								'message' => $answer,
								'reply_to_msg_id' => $message['id'],
								'parse_mode' => 'HTML'
							]);

							// Sending the report to the channel
							$this -> report('<a href=\"mention:' . $sender['id'] . '\" >' . $sender['first_name'] . '</a> ' . $command . ($command == 'add' ? 'e' : '') . 'd <a href=\"' . $chat['exported_invite'] . '\" >' . $chat['title'] . '</a> ' . ($command == 'add' ? 'into' : 'from') . ' the database.');
							return;
						}

						// Retrieving the message this message replies to
						$reply_message = yield $this -> messages -> getMessages([
							'id' => [
								$message['reply_to_msg_id']
							]
						]);

						// Checking if the result is valid
						if ($reply_message['_'] === 'messages.messagesNotModified' || $reply_message['messages'][0]['_'] !== 'message') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because wasn\'t a message that replies to another message (/' . $command . ' section).');
							return;
						}

						$reply_message = $reply_message['messages'][0];

						// Retrieving the data of the user
						$user = yield $this -> getInfo($reply_message['from_id']);
						$user = $user['User'] ?? NULL;

						/**
						* Checking if the user is a normal user
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
						if (empty($user) || $user['_'] !== 'user') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because the user of the reply_message isn\'t a normal user (/' . $command . ' section).');
							return;
						}

						// Retrieving the query
						$sql_query = $command == 'add' ? 'INSERT INTO `Admins` (`id`, `first_name`, `last_name`) VALUES (?, ?, ?);' : 'DELETE FROM `Admins` WHERE `id`=?;';

						// Opening a transaction
						$transaction = yield $this -> DB -> beginTransaction();

						try {
							yield $transaction -> execute($sql_query, $command == 'add' ? [
								$user['id'],
								$user['first_name'],
								$user['last_name']
							] : [
								$user['id']
							]);
						} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

							// Retrieving the reject message
							try {
								$answer = yield $this -> DB -> execute('SELECT `reject_message` FROM `Languages` WHERE `lang_code`=?;', [
									$language
								]);
							} catch (Amp\Sql\QueryError $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
								$answer = 'Operation deleted.';
							} catch (Amp\Sql\FailureException $e) {
								$answer = 'Operation deleted.';
							}

							// Checking if the query has product a result
							if ($answer instanceof Amp\Mysql\ResultSet) {
								yield $answer -> advance();
								$answer = $answer -> getCurrent();
								$answer = $answer['reject_message'];
							}

							/**
							* Checking if the reject message isn't setted
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
							if (empty($answer)) {
								$answer = 'Operation deleted.';
							}

							yield $this -> messages -> sendMessage([
								'no_webpage' => TRUE,
								'peer' => $chat['id'],
								'message' => $answer,
								'reply_to_msg_id' => $message['id'],
								'parse_mode' => 'HTML'
							]);
							return;
						}

						// Commit the change
						yield $transaction -> commit();

						// Closing the transaction
						yield $transaction -> close();

						// Retrieving the confirm message
						try {
							$answer = yield $this -> DB -> execute('SELECT `confirm_message` FROM `Languages` WHERE `lang_code`=?;', [
								$language
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							$answer = 'Operation completed.';
						} catch (Amp\Sql\FailureException $e) {
							$answer = 'Operation completed.';
						}

						// Checking if the query has product a result
						if ($answer instanceof Amp\Mysql\ResultSet) {
							yield $answer -> advance();
							$answer = $answer -> getCurrent();
							$answer = $answer['confirm_message'];
						}

						/**
						* Checking if the confirm message isn't setted
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
						if (empty($answer)) {
							$answer = 'Operation completed.';
						}

						yield $this -> messages -> sendMessage([
							'no_webpage' => TRUE,
							'peer' => $chat['id'],
							'message' => $answer,
							'reply_to_msg_id' => $message['id'],
							'parse_mode' => 'HTML'
						]);

						// Sending the report to the channel
						$this -> report('<a href=\"mention:' . $sender['id'] . '\" >' . $sender['first_name'] . '</a> ' . $command == 'add' ? 'assigned' : 'removed' . ' <a href=\"mention:' . $user['id'] . '\" >' . $user['first_name'] . '</a> as bot\'s admin.');
						break;
					case 'announce':
						// Checking if the chat is a private chat
						if ($message['to_id']['_'] === 'peerUser') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from a private chat (/' . $command . ' section).');
							return;
						}

						/**
						* Checking if the command have arguments
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
						if (empty($args) == FALSE) {
							// Checking if is a serious use of the /announce command (command runned by a bot's admin)
							try {
								yield $this -> DB -> execute('SELECT NULL FROM `Admins` WHERE `id`=?;', [
									$sender['id']
								]);
							} catch (Amp\Sql\QueryError $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
								return;
							} catch (Amp\Sql\FailureException $e) {
								/**
								* Retrieving the admins list
								*
								* array_filter() filters the array by the role of each member
								* array_map() convert each admins to its id
								*/
								$admins = array_filter($chat['participants'], function ($n) {
									return $n['role'] == 'admin' || $n['role'] == 'creator';
								});
								$admins = array_map(function ($n) {
									return $n['user']['id'];
								}, $admins);

								/**
								* Checking if the user is an admin and if the command has arguments
								*
								* in_array() check if the array contains an item that match the element
								*/
								if (in_array($sender['id'], $admins) == FALSE) {
									$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because the sender isn\'t an admin of the chat (/' . $command . ' section).');
									return;
								}

								yield $this -> messages -> sendMessage([
									'no_webpage' => TRUE,
									'peer' => $chat['id'],
									'message' => $args,
									'parse_mode' => 'HTML'
								]);
								return;
							}

							// Checking if is a serious use of the /announce command (command runned into the staff group)
							try {
								$result = yield $this -> DB -> execute('SELECT `id` FROM `Chats` WHERE `staff_group`=?;', [
									$chat['id']
								]);
							} catch (Amp\Sql\QueryError $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
								return;
							} catch (Amp\Sql\FailureException $e) {
								/**
								* Retrieving the admins list
								*
								* array_filter() filters the array by the role of each member
								* array_map() convert each admins to its id
								*/
								$admins = array_filter($chat['participants'], function ($n) {
									return $n['role'] == 'admin' || $n['role'] == 'creator';
								});
								$admins = array_map(function ($n) {
									return $n['user']['id'];
								}, $admins);

								/**
								* Checking if the user is an admin and if the command has arguments
								*
								* in_array() check if the array contains an item that match the element
								*/
								if (in_array($sender['id'], $admins) == FALSE) {
									$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because the sender isn\'t an admin of the chat (/' . $command . ' section).');
									return;
								}

								yield $this -> messages -> sendMessage([
									'no_webpage' => TRUE,
									'peer' => $chat['id'],
									'message' => $args,
									'parse_mode' => 'HTML'
								]);
								return;
							}

							$messages = [
								'multiple' => true
							];

							$chats = [];

							// Cycle on the result
							while (yield $result -> advance()) {
								$chats []= $result -> getCurrent();
							}

							// Cycle on the chats that have this staff group
							foreach ($chats as $sub_chat) {
								$messages []= [
									'no_webpage' => TRUE,
									'peer' => $sub_chat['id'],
									'message' => $args,
									'parse_mode' => 'HTML'
								];
							}

							yield $this -> messages -> sendMessage($messages);
						}
						break;
					case 'ban':
					case 'kick':
					case 'mute':
					case 'silence':
					case 'unban':
					case 'unmute':
					case 'unsilence':
						// Checking if the chat is a private chat
						if ($message['to_id']['_'] === 'peerUser') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from a private chat (/' . $command . ' section).');
							return;
						}

						// Checking if the sender is a bot's admin
						try {
							yield $this -> DB -> execute('SELECT NULL FROM `Admins` WHERE `id`=?;', [
								$sender['id']
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							return;
						} catch (Amp\Sql\FailureException $e) {
							// Setting limit to forever
							$limit = 0;

							/**
							* Checking if the command is /mute, if it has arguments and if the arguments are correct
							*
							* preg_match() perform a RegEx match
							* empty() check if the argument is empty
							* 	''
							* 	""
							* 	'0'
							* 	"0"
							* 	0
							* 	0.0
							* 	NULL
							* 	FALSE
							* 	[]s
							* 	array()
							*/
							if ($command == 'mute' && empty($args) == FALSE && preg_match('/^([[:digit:]]+)[[:blank:]]?([[:alpha:]]+)$/miu', $args, $matches)) {
								$limit = $matches[1];

								// Converting the units to seconds
								switch ($matches[2]) {
									case 'm':
									case 'min':
									case 'minuto':
									case 'minute':
									case 'minuti':
									case 'minutes':
										$limit *= 60;
										break;
									case 'h':
									case 'ora':
									case 'hour':
									case 'ore':
									case 'hours':
										$limit *= 60 * 60;
										break;
									case 'g':
									case 'd':
									case 'giorno':
									case 'day':
									case 'giorni':
									case 'days':
										$limit *= 60 * 60 * 24;
										break;
									case 'M':
									case 'mese':
									case 'month':
									case 'mesi':
									case 'months':
										$limit *= 60 * 60 * 24 * 30;
									case 'a':
									case 'y':
									case 'anno':
									case 'year':
										// $limit *= 60 * 60 * 24 * 30 * 12;
										$limit *= 60 * 60 * 24 * 365;
										break;
									default:
										// Retrieving the mute message
										try {
											$answer = yield $this -> DB -> execute('SELECT `mute_message` FROM `Languages` WHERE `lang_code`=?;', [
												$language
											]);
										} catch (Amp\Sql\QueryError $e) {
											$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
											$answer = 'The syntax of the command is: <code>/mute [time]</code>.\nThe <code>time</code> option must be more then 30 seconds and less of 366 days.';
										} catch (Amp\Sql\FailureException $e) {
											$answer = 'The syntax of the command is: <code>/mute [time]</code>.\nThe <code>time</code> option must be more then 30 seconds and less of 366 days.';
										}

										// Checking if the query has product a result
										if ($answer instanceof Amp\Mysql\ResultSet) {
											yield $answer -> advance();
											$answer = $answer -> getCurrent();
											$answer = $answer['mute_message'];
										}

										/**
										* Checking if the mute message is setted
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
										if (empty($answer)) {
											$answer = 'The syntax of the command is: <code>/mute [time]</code>.\nThe <code>time</code> option must be more then 30 seconds and less of 366 days.';
										}

										yield $this -> messages -> sendMessage([
											'no_webpage' => TRUE,
											'peer' => $chat['id'],
											'message' => $answer,
											'reply_to_msg_id' => $message['id'],
											'parse_mode' => 'HTML'
										]);
										break;
								}
							}

							/**
							* Checking if the message isn't a message that replies to another message
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
							if (empty($message['reply_to_msg_id'])) {
								$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because wasn\'t a message that replies to another message (/' . $command . ' section).');
								return;
							}

							// Retrieving the message this message replies to
							$reply_message = yield $this -> messages -> getMessages([
								'id' => [
									$message['reply_to_msg_id']
								]
							]);

							// Checking if the result is valid
							if ($reply_message['_'] === 'messages.messagesNotModified' || $reply_message['messages'][0]['_'] !== 'message') {
								$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because wasn\'t a message that replies to another message (/' . $command . ' section).');
								return;
							}

							$reply_message = $reply_message['messages'][0];

							// Retrieving the data of the user
							$user = yield $this -> getInfo($reply_message['from_id']);
							$user = $user['User'] ?? NULL;

							/**
							* Checking if the user is a normal user
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
							if (empty($user) || $user['_'] !== 'user') {
								$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because the user of the reply_message isn\'t a normal user (/' . $command . ' section).');
								return;
							}

							// Checking if the command is one of: /ban, /kick or /mute
							if ($command == 'ban' || $command == 'kick' || $command == 'mute') {
								yield $this -> channels -> editBanned([
									'channel' => $chat['id'],
									'user_id' => $reply_message['from_id'],
									'banned_rights' => [
										'_' => 'chatBannedRights',
										'view_messages' => $command == 'mute' ? FALSE : TRUE,
										'send_messages' => TRUE,
										'send_media' => TRUE,
										'send_stickers' => TRUE,
										'send_gifs' => TRUE,
										'send_games' => TRUE,
										'send_inline' => TRUE,
										'embed_links' => TRUE,
										'send_polls' => TRUE,
										'change_info' => TRUE,
										'invite_users' => $command == 'mute' ? FALSE : TRUE,
										'pin_messages' => TRUE,
										'until_date' => $limit
									]
								]);
							}

							// Checking if the command is one of: /kick, /unban or /unmute
							if ($command == 'kick' || $command == 'unban' || $command == 'unmute') {
								yield $this -> channels -> editBanned([
									'channel' => $chat['id'],
									'user_id' => $reply_message['from_id'],
									'banned_rights' => $chat['default_banned_rights']
								]);
							}

							/**
							* Checking if is a /(un)silence command
							*
							* preg_match() perform a RegEx match
							*/
							if (preg_match('/^(un)?silence/miu', $command)) {
								yield $this -> messages -> editChatDefaultBannedRights([
									'peer' => $chat['id'],
									'banned_rights' => [
										'_' => 'chatBannedRights',
										'view_messages' => FALSE,
										'send_messages' => $command == 'unsilence' ? FALSE : TRUE,
										'send_media' => $command == 'unsilence' ? FALSE : TRUE,
										'send_stickers' => $command == 'unsilence' ? FALSE : TRUE,
										'send_gifs' => $command == 'unsilence' ? FALSE : TRUE,
										'send_games' => $command == 'unsilence' ? FALSE : TRUE,
										'send_inline' => $command == 'unsilence' ? FALSE : TRUE,
										'embed_links' => $command == 'unsilence' ? FALSE : TRUE,
										'send_polls' => $command == 'unsilence' ? FALSE : TRUE,
										'change_info' => TRUE,
										'invite_users' => FALSE,
										'pin_messages' => TRUE,
										'until_date' => 0
									]
								]);
							}

							// Checking if is a permanent /mute command
							if ($command == 'mute' && ($limit < 30 || $limit > 60 * 60 * 24 * 366)) {
								// Retrieving the mute_advert message
								try {
									$answer = yield $this -> DB -> execute('SELECT `mute_advert_message` FROM `Languages` WHERE `lang_code`=?;', [
										$language
									]);
								} catch (Amp\Sql\QueryError $e) {
									$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
									$answer = 'You have muted <a href=\"mention:${sender_id}\" >${sender_first_name}</a> forever.';
								} catch (Amp\Sql\FailureException $e) {
									$answer = 'You have muted <a href=\"mention:${sender_id}\" >${sender_first_name}</a> forever.';
								}

								// Checking if the query has product a result
								if ($answer instanceof Amp\Mysql\ResultSet) {
									yield $answer -> advance();
									$answer = $answer -> getCurrent();
									$answer = $answer['mute_advert_message'];
								}

								/**
								* Checking if the mute_advert message isn't setted
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
								if (empty($answer)) {
									$answer = 'You have muted <a href=\"mention:${sender_id}\" >${sender_first_name}</a> forever.';
								}

								/**
								* Personalizing the message
								*
								* str_replace() replace the tags with their value
								*/
								$answer = str_replace('${sender_first_name}', $sender['first_name'], $answer);
								$answer = str_replace('${sender_id}', $sender['id'], $answer);

								yield $this -> messages -> sendMessage([
									'no_webpage' => TRUE,
									'peer' => $chat['id'],
									'message' => $answer,
									'reply_to_msg_id' => $message['id'],
									'parse_mode' => 'HTML'
								]);
							}

							// Setting the verb of the report
							$verb = $command;

							/**
							* Checking if is a /(un)ban command
							*
							* preg_match() perform a RegEx match
							*/
							if (preg_match('/^(un)?ban/miu', $command)) {
								$verb .= 'ne';
							// Checking if is a /kick command
							} else if ($command == 'kick') {
								$verb .= 'e';
							}

							$verb .= 'd';

							// Sending the report to the channel
							$this -> report('<a href=\"mention:' . $sender['id'] . '\" >' . $sender['first_name'] . '</a> ' . $verb . ' <a href=\"mention:' . $user['id'] . '\" >' . $user['first_name'] . '</a>' . ($command == 'mute' && $limit > 30 && $limit < 60 * 60 * 24 * 366 ? ' for ' . $args : '') . '.');
							return;
						}

						/**
						* Checking if is a serious use of the /(un)ban command (command runned by a bot's admin)
						*
						* preg_match() perform a RegEx match
						*/
						if (preg_match('/^(un)?ban/miu', $command)) {
							// Checking if is a serious use of the /(un)ban command (command runned in the staff group)
							try {
								$result = yield $this -> DB -> execute('SELECT `id` FROM `Chats` WHERE `staff_group`=?;', [
									$chat['id']
								]);
							} catch (Amp\Sql\QueryError $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
								return;
							} catch (Amp\Sql\FailureException $e) {
								return;
							}

							/**
							* Checking if the command haven't arguments
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
							if (empty($args)) {
								// Retrieving the invalid_syntax message
								try {
									$answer = yield $this -> DB -> execute('SELECT `invalid_syntax_message` FROM `Languages` WHERE `lang_code`=?;', [
										$language
									]);
								} catch (Amp\Sql\QueryError $e) {
									$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
									$answer = 'The syntax of the command is: <code>${syntax}</code>.';
								} catch (Amp\Sql\FailureException $e) {
									$answer = 'The syntax of the command is: <code>${syntax}</code>.';
								}

								// Checking if the query has product a result
								if ($answer instanceof Amp\Mysql\ResultSet) {
									yield $answer -> advance();
									$answer = $answer -> getCurrent();
									$answer = $answer['invalid_syntax_message'];
								}

								/**
								* Checking if the invalid_syntax message isn't setted
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
								if (empty($answer)) {
									$answer = 'The syntax of the command is: <code>${syntax}</code>.';
								}

								/**
								* Personalizing the message
								*
								* str_replace() replace the tag with its value
								*/
								$answer = str_replace('${syntax}', '/' . $command . ' &lt;user_id|username&gt;', $answer);

								yield $this -> messages -> sendMessage([
									'no_webpage' => TRUE,
									'peer' => $sender['id'],
									'message' => $answer,
									'reply_to_msg_id' => $message['id'],
									'parse_mode' => 'HTML'
								]);

								$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because the command have a wrong syntax (/' . $command . ' section).');
								return;
							}

							// Retrieving the data of the user
							$user = yield $this -> getInfo($args);
							$user = $user['User'] ?? NULL;

							/**
							* Checking if the user isn't a normal user
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
							if (empty($user) || $user['_'] !== 'user') {
								// Retrieving the invalid_parameter message
								try {
									$answer = yield $this -> DB -> execute('SELECT `invalid_parameter_message` FROM `Languages` WHERE `lang_code`=?;', [
										$language
									]);
								} catch (Amp\Sql\QueryError $e) {
									$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
									$answer = 'The ${parameter} is invalid.';
								} catch (Amp\Sql\FailureException $e) {
									$answer = 'The ${parameter} is invalid.';
								}

								// Checking if the query has product a result
								if ($answer instanceof Amp\Mysql\ResultSet) {
									yield $answer -> advance();
									$answer = $answer -> getCurrent();
									$answer = $answer['invalid_parameter_message'];
								}

								/**
								* Checking if the invalid_parameter message isn't setted
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
								if (empty($answer)) {
									$answer = 'The ${parameter} is invalid.';
								}

								/**
								* Personalizing the message
								*
								* str_replace() replace the tag with its value
								*/
								$answer = str_replace('${parameter}', 'username/id', $answer);

								yield $this -> messages -> sendMessage([
									'no_webpage' => TRUE,
									'peer' => $sender['id'],
									'message' => $answer,
									'reply_to_msg_id' => $message['id'],
									'parse_mode' => 'HTML'
								]);

								$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because the command have a wrong syntax (/' . $command . ' section).');
								return;
							}

							$chats = [];

							// Cycle on the result
							while (yield $result -> advance()) {
								$chats []= $result -> getCurrent();
							}

							// Cycle on the chats that have this staff group
							foreach ($chats as $sub_chat) {
								// Retrieving the data of the chat
								$sub_chat = yield $this -> getInfo($sub_chat['id']);
								$sub_chat = $sub_chat['Chat'] ?? NULL;

								/**
								* Checking if the chat isn't setted
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
								* 	[]s
								* 	array()
								*/
								if (empty($sub_chat) || ($sub_chat['_'] != 'chat' && $sub_chat['_'] != 'channel') || ($sub_chat['_'] == 'channel' && $sub_chat['_']['broadcast'] == TRUE)) {
									continue;
								}

								yield $this -> channels -> editBanned([
									'channel' => $sub_chat['id'],
									'user_id' => $user['id'],
									'banned_rights' => $command == 'unban' ? $sub_chat['default_banned_rights'] : [
										'_' => 'chatBannedRights',
										'view_messages' => TRUE,
										'send_messages' => TRUE,
										'send_media' => TRUE,
										'send_stickers' => TRUE,
										'send_gifs' => TRUE,
										'send_games' => TRUE,
										'send_inline' => TRUE,
										'embed_links' => TRUE,
										'send_polls' => TRUE,
										'change_info' => TRUE,
										'invite_users' => TRUE,
										'pin_messages' => TRUE,
										'until_date' => 0
									]
								]);
							}

							// Sending the report to the channel
							$this -> report('<a href=\"mention:' . $sender['id'] . '\" >' . $sender['first_name'] . '</a> ' . $command . 'ned <a href=\"mention:' . $user['id'] . '\" >' . $user['first_name'] . '</a> from all chats.');
						}
						break;
					case 'blacklist':
					case 'unblacklist':
						// Checking if the chat is a private chat
						if ($message['to_id']['_'] === 'peerUser') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from a private chat (/' . $command . ' section).');
							return;
						}

						// Checking if the sender is a bot's admin
						try {
							yield $this -> DB -> execute('SELECT NULL FROM `Admins` WHERE `id`=?;', [
								$sender['id']
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							return;
						} catch (Amp\Sql\FailureException $e) {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from an unauthorized user (/' . $command . ' section).');
							return;
						}

						/**
						* Checking if the message isn't a message that replies to another message
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
						if (empty($message['reply_to_msg_id'])) {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because wasn\'t a message that replies to another message (/' . $command . ' section).');
							return;
						}

						// Retrieving the message this message replies to
						$reply_message = yield $this -> messages -> getMessages([
							'id' => [
								$message['reply_to_msg_id']
							]
						]);

						// Checking if the result is valid
						if ($reply_message['_'] === 'messages.messagesNotModified' || $reply_message['messages'][0]['_'] !== 'message') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because wasn\'t a message that replies to another message (/' . $command . ' section).');
							return;
						}

						$reply_message = $reply_message['messages'][0];

						// Retrieving the data of the user
						$user = yield $this -> getInfo($reply_message['from_id']);
						$user = $user['User'] ?? NULL;

						/**
						* Checking if the user is a normal user
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
						if (empty($user) || $user['_'] !== 'user') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because the user of the reply_message isn\'t a normal user (/' . $command . ' section).');
							return;
						}

						// Opening a transaction
						$transaction = yield $this -> DB -> beginTransaction();

						// Insert the user into the blacklist
						try {
							yield $transaction -> execute('INSERT INTO `Blacklist` (`id`) VALUES (?);', [
								$user['id']
							]);
						} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
							// Closing the transaction
							yield $transaction -> close();

							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

							// Retrieving the reject message
							try {
								$answer = yield $this -> DB -> execute('SELECT `reject_message` FROM `Languages` WHERE `lang_code`=?;', [
									$language
								]);
							} catch (Amp\Sql\QueryError $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
								$answer = 'Operation deleted.';
							} catch (Amp\Sql\FailureException $e) {
								$answer = 'Operation deleted.';
							}

							// Checking if the query has product a result
							if ($answer instanceof Amp\Mysql\ResultSet) {
								yield $answer -> advance();
								$answer = $answer -> getCurrent();
								$answer = $answer['reject_message'];
							}

							/**
							* Checking if the reject message isn't setted
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
							if (empty($answer)) {
								$answer = 'Operation deleted.';
							}

							yield $this -> messages -> sendMessage([
								'no_webpage' => TRUE,
								'peer' => $sender['id'],
								'message' => $answer,
								'reply_to_msg_id' => $message['id'],
								'parse_mode' => 'HTML'
							]);
							return;
						}

						// Commit the change
						yield $transaction -> commit();

						// Closing the transaction
						yield $transaction -> close();

						// Retrieving the confirm message
						try {
							$answer = yield $this -> DB -> execute('SELECT `confirm_message` FROM `Languages` WHERE `lang_code`=?;', [
								$language
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							$answer = 'Operation completed.';
						} catch (Amp\Sql\FailureException $e) {
							$answer = 'Operation completed.';
						}

						// Checking if the query has product a result
						if ($answer instanceof Amp\Mysql\ResultSet) {
							yield $answer -> advance();
							$answer = $answer -> getCurrent();
							$answer = $answer['confirm_message'];
						}

						/**
						* Checking if the confirm message isn't setted
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
						if (empty($answer)) {
							$answer = 'Operation completed.';
						}

						yield $this -> messages -> sendMessage([
							'no_webpage' => TRUE,
							'peer' => $sender['id'],
							'message' => $answer,
							'reply_to_msg_id' => $message['id'],
							'parse_mode' => 'HTML'
						]);

						// Sending the report to the channel
						$this -> report('<a href=\"mention:' . $sender['id'] . '\" >' . $sender['first_name'] . '</a> ' . $command . 'ed <a href=\"mention:' . $user['id'] . '\" >' . $user['first_name'] . '</a>.');
						break;
					case 'help':
						// Checking if the chat isn't a private chat
						if ($message['to_id']['_'] !== 'peerUser') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because wasn\'t a message from a private chat (/' . $command . ' section).');
							return;
						}

						// Retrieving the help message
						try {
							$answer = yield $this -> DB -> execute('SELECT `help_message` FROM `Languages` WHERE `lang_code`=?;', [
								$language
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							$answer = '<b>FREQUENTLY ASKED QUESTION<\b>\n(FAQ list)\n\n<a href=\"(link to the manual, without brackets)\" >TELEGRAM GUIDE</a>\n\n<b>INLINE COMMANDS<\b>\n(Inline mode description)';
						} catch (Amp\Sql\FailureException $e) {
							$answer = '<b>FREQUENTLY ASKED QUESTION<\b>\n(FAQ list)\n\n<a href=\"(link to the manual, without brackets)\" >TELEGRAM GUIDE</a>\n\n<b>INLINE COMMANDS<\b>\n(Inline mode description)';
						}

						// Checking if the query has product a result
						if ($answer instanceof Amp\Mysql\ResultSet) {
							yield $answer -> advance();
							$answer = $answer -> getCurrent();
							$answer = $answer['help_message'];
						}

						/**
						* Checking if the help message is setted
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
						if (empty($answer)) {
							$answer = '<b>FREQUENTLY ASKED QUESTION<\b>\n(FAQ list)\n\n<a href=\"(link to the manual, without brackets)\" >TELEGRAM GUIDE</a>\n\n<b>INLINE COMMANDS<\b>\n(Inline mode description)';
						}

						yield $this -> messages -> sendMessage([
							'no_webpage' => TRUE,
							'peer' => $sender['id'],
							'message' => $answer,
							'parse_mode' => 'HTML'
						]);
						break;
					case 'link':
						// Checking if the chat is a private chat
						if ($message['to_id']['_'] === 'peerUser') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from a private chat (/' . $command . ' section).');
							return;
						}

						// Retrieving the link message
						try {
							$answer = yield $this -> DB -> execute('SELECT `link_message` FROM `Languages` WHERE `lang_code`=?;', [
								$language
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							$answer = '<a href=\"${invite_link}\" >This</a> is the invite link to this chat.';
						} catch (Amp\Sql\FailureException $e) {
							$answer = '<a href=\"${invite_link}\" >This</a> is the invite link to this chat.';
						}

						// Checking if the query has product a result
						if ($answer instanceof Amp\Mysql\ResultSet) {
							yield $answer -> advance();
							$answer = $answer -> getCurrent();
							$answer = $answer['link_message'];
						}

						/**
						* Checking if the link message isn't setted
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
						if (empty($answer)) {
							$answer = '<a href=\"${invite_link}\" >This</a> is the invite link to this chat.';
						}

						/**
						* Personalizing the message
						*
						* str_replace() replace the tag with its value
						*/
						$answer = str_replace('${invite_link}', $chat['invite'], $answer);

						yield $this -> messages -> sendMessage([
							'no_webpage' => TRUE,
							'peer' => $chat['id'],
							'message' => $answer,
							'reply_to_msg_id' => $message['id'],
							'parse_mode' => 'HTML'
						]);
						break;
					case 'report':
						// Checking if the chat isn't a private chat
						if ($message['to_id']['_'] !== 'peerUser') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because wasn\'t a message from a private chat (/' . $command . ' section).');
							return;
						}

						// Checking if the sender is a bot's admin
						try {
							yield $this -> DB -> execute('SELECT NULL FROM `Admins` WHERE `id`=?;', [
								$sender['id']
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							return;
						} catch (Amp\Sql\FailureException $e) {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from an unauthorized user (/' . $command . ' section).');
							return;
						}

						yield $this -> bots -> setCommands([
							'commands' => [
								[
									'_' => 'botCommand',
									'command' => 'add',
									'description' => 'Assign a user as bot\'s admin or add a chat or a language to the database'
								],
								[
									'_' => 'botCommand',
									'command' => 'announce',
									'description' => 'If it\'s used into the staff group, send an announce in all the (super)groups, otherwise only in the (super)group where it\'s used'
								],
								[
									'_' => 'botCommand',
									'command' => 'ban',
									'description' => 'If it\'s used into the staff group, ban a user from all the (super)groups, otherwise only from the (super)group where it\'s used'
								],
								[
									'_' => 'botCommand',
									'command' => 'blacklist',
									'description' => 'Insert a user in the bot\'s blacklist'
								],
								[
									'_' => 'botCommand',
									'command' => 'help',
									'description' => 'Send the help of the bot'
								],
								[
									'_' => 'botCommand',
									'command' => 'kick',
									'description' => 'Kick a user from a (super)group'
								],
								[
									'_' => 'botCommand',
									'command' => 'link',
									'description' => 'Send the invite link of the (super)group'
								],
								[
									'_' => 'botCommand',
									'command' => 'mute',
									'description' => 'Mute a user in a (super)group'
								],
								[
									'_' => 'botCommand',
									'command' => 'remove',
									'description' => 'Remove a user as bot\'s admin or a chat or a language to the database'
								],
								[
									'_' => 'botCommand',
									'command' => 'report',
									'description' => 'Set the bot commands'
								],
								[
									'_' => 'botCommand',
									'command' => 'silence',
									'description' => 'Mute a (super)group, except the admins'
								],
								[
									'_' => 'botCommand',
									'command' => 'staff_group',
									'description' => 'Set the staff group for one or more chats'
								],
								[
									'_' => 'botCommand',
									'command' => 'start',
									'description' => 'Starts the bot'
								],
								[
									'_' => 'botCommand',
									'command' => 'unban',
									'description' => 'If it\'s used into the staff group, unban a user from all the (super)groups, otherwise only from the (super)group where it\'s used'
								],
								[
									'_' => 'botCommand',
									'command' => 'unblacklist',
									'description' => 'Remove a user from the bot\'s blacklist'
								],
								[
									'_' => 'botCommand',
									'command' => 'unmute',
									'description' => 'Unmute a user from a (super)group'
								],
								[
									'_' => 'botCommand',
									'command' => 'unsilence',
									'description' => 'Unmute a (super)group'
								],
								[
									'_' => 'botCommand',
									'command' => 'update',
									'description' => 'Update the database'
								]
							]
						]);

						// Retrieving the confirm message
						try {
							$answer = yield $this -> DB -> execute('SELECT `confirm_message` FROM `Languages` WHERE `lang_code`=?;', [
								$language
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							$answer = 'Operation completed.';
						} catch (Amp\Sql\FailureException $e) {
							$answer = 'Operation completed.';
						}

						// Checking if the query has product a result
						if ($answer instanceof Amp\Mysql\ResultSet) {
							yield $answer -> advance();
							$answer = $answer -> getCurrent();
							$answer = $answer['confirm_message'];
						}

						/**
						* Checking if the confirm message isn't setted
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
						if (empty($answer)) {
							$answer = 'Operation completed.';
						}

						yield $this -> messages -> sendMessage([
							'no_webpage' => TRUE,
							'peer' => $chat['id'],
							'message' => $answer,
							'reply_to_msg_id' => $message['id'],
							'parse_mode' => 'HTML'
						]);
						break;
					case 'staff_group':
						// Checking if the chat is a private chat
						if ($message['to_id']['_'] === 'peerUser') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from a private chat (/' . $command . ' section).');
							return;
						}

						// Checking if the sender is a bot's admin
						try {
							yield $this -> DB -> execute('SELECT NULL FROM `Admins` WHERE `id`=?;', [
								$sender['id']
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							return;
						} catch (Amp\Sql\FailureException $e) {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from an unauthorized user (/' . $command . ' section).');
							return;
						}

						// Retrieving the chats' list
						try {
							yield $this -> DB -> query('SELECT `id`, `title` FROM `Chats`;');
						} catch (Amp\Sql\FailureException $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							return;
						}

						$chats = [];

						// Cycle on the result
						while (yield $result -> advance()) {
							$chats []= $result -> getCurrent();
						}

						/**
						* Retrieving the length of the chats' list
						*
						* count() retrieve the length of the array
						*/
						$total = count($chats);

						/**
						* Setting the Inline Keyboard
						*
						* array_splice() extract the sub-array from the main array
						* array_map() convert each chat to a keyboardButtonCallback
						*/
						$chats = array_slice($chats, 0,  $this -> button_InlineKeyboard);
						$chats = array_map(function ($n) {
							return [
								'_' => 'keyboardButtonCallback',
								'text' => $n['title'],
								/**
								* Generating the keyboardButtonCallback data
								*
								* base64_encode() encode the string
								*/
								'data' => base64_encode($command . '/' . $n['id'] . '/no')
							];
						}, $chats);

						$row = [
							'_' => 'keyboardButtonRow',
							'buttons' => []
						];
						$keyboard = [
							'_' => 'replyInlineMarkup',
							'rows' => []
						];

						// Cycle on the buttons' list
						foreach ($chats as $button) {
							/**
							* Retrieving the length of the row
							*
							* count() retrieve the length of the array
							*/
							if (count($row['buttons']) == 2) {
								// Saving the row
								$keyboard['rows'] []= $row;

								// Creating a new row
								$row['buttons'] = [];
							}
							// Adding a button to the row
							$row['buttons'] []= $button;
						}

						// Setting the page
						if ($total > $this -> button_InlineKeyboard) {
							$keyboard['rows'] []= [
								'_' => 'keyboardButtonRow',
								'buttons' => [
									[
										'_' => 'keyboardButtonCallback',
										'text' => '',
										/**
										* Generating the keyboardButtonCallback data
										*
										* base64_encode() encode the string
										*/
										'data' => base64_encode('')
									],
									[
										'_' => 'keyboardButtonCallback',
										'text' => 'Next page',
										/**
										* Generating the keyboardButtonCallback data
										*
										* base64_encode() encode the string
										*/
										'data' => base64_encode($command . '/page/1')
									]
								]
							];
						}

						// Setting the confirm buttons
						$keyboard['rows'] []= [
							'_' => 'keyboardButtonRow',
							'buttons' => [
								[
									'_' => 'keyboardButtonCallback',
									'text' => 'Reject',
									/**
									* Generating the keyboardButtonCallback data
									*
									* base64_encode() encode the string
									*/
									'data' => base64_encode($command . '/reject')
								],
								[
									'_' => 'keyboardButtonCallback',
									'text' => 'Confirm',
									/**
									* Generating the keyboardButtonCallback data
									*
									* base64_encode() encode the string
									*/
									'data' => base64_encode($command . '/confirm')
								]
							]
						];

						// Retrieving the staff_group message
						try {
							$answer = yield $this -> DB -> execute('SELECT `staff_group_message` FROM `Languages` WHERE `lang_code`=?;', [
								$language
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							$answer = 'For what chats do you want set this staff group ?';
						} catch (Amp\Sql\FailureException $e) {
							$answer = 'For what chats do you want set this staff group ?';
						}

						// Checking if the query has product a result
						if ($answer instanceof Amp\Mysql\ResultSet) {
							yield $answer -> advance();
							$answer = $answer -> getCurrent();
							$answer = $answer['staff_group_message'];
						}

						/**
						* Checking if the staff_group message isn't setted
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
						if (empty($answer)) {
							$answer = 'For what chats do you want set this staff group ?';
						}

						yield $this -> messages -> sendMessage([
							'no_webpage' => TRUE,
							'peer' => $chat['id'],
							'message' => $answer,
							'reply_to_msg_id' => $message['id'],
							'parse_mode' => 'HTML'
						]);
						break;
					case 'start':
						// Checking if the chat isn't a private chat
						if ($message['to_id']['_'] !== 'peerUser') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because wasn\'t a message from a private chat (/' . $command . ' section).');
							return;
						}

						// Retrieving the start message
						try {
							$answer = yield $this -> DB -> execute('SELECT `start_message` FROM `Languages` WHERE `lang_code`=?;', [
								$language
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							$answer = 'Hello <a href=\"mention:${sender_id}\" >${sender_first_name}</a>, welcome !\n\n(Rest of the message to be sent upon receipt of the start command)';
						} catch (Amp\Sql\FailureException $e) {
							$answer = 'Hello <a href=\"mention:${sender_id}\" >${sender_first_name}</a>, welcome !\n\n(Rest of the message to be sent upon receipt of the start command)';
						}

						// Checking if the query has product a result
						if ($answer instanceof Amp\Mysql\ResultSet) {
							yield $answer -> advance();
							$answer = $answer -> getCurrent();
							$answer = $answer['start_message'];
						}

						/**
						* Checking if the start message isn't setted
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
						if (empty($answer)) {
							$answer = 'Hello <a href=\"mention:${sender_id}\" >${sender_first_name}</a>, welcome !\n\n(Rest of the message to be sent upon receipt of the start command)';
						}

						/**
						* Personalizing the message
						*
						* str_replace() replace the tags with their value
						*/
						$answer = str_replace('${sender_first_name}', $sender['first_name'], $answer);
						$answer = str_replace('${sender_id}', $sender['id'], $answer);

						yield $this -> messages -> sendMessage([
							'no_webpage' => TRUE,
							'peer' => $sender['id'],
							'message' => $answer,
							'parse_mode' => 'HTML'
						]);
						break;
					case 'update':
						// Checking if the chat isn't a private chat
						if ($message['to_id']['_'] !== 'peerUser') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because wasn\'t a message from a private chat (/' . $command . ' section).');
							return;
						}

						// Checking if the sender is a bot's admin
						try {
							yield $this -> DB -> execute('SELECT NULL FROM `Admins` WHERE `id`=?;', [
								$sender['id']
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							return;
						} catch (Amp\Sql\FailureException $e) {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from an unauthorized user (/' . $command . ' section).');
							return;
						}

						$banned = [
							'multiple' = TRUE
						];

						// Retrieving the chats' list
						try {
							yield $this -> DB -> query('SELECT `id` FROM `Chats`;');
						} catch (Amp\Sql\FailureException $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							return;
						}

						$chats = [];

						// Cycle on the result
						while (yield $result -> advance()) {
							$chats []= $result -> getCurrent();
						}

						// Opening a transaction
						$transaction = yield $this -> DB -> beginTransaction();

						// Updating the chats' data
						try {
							$statement = yield $transaction -> prepare('UPDATE `Chats` SET `type`=?, `title`=?, `username`=?, `invite_link`=? WHERE `id`=?;');
						} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

							// Closing the transaction
							yield $transaction -> close();
							return;
						}

						// Cycle on the list of the chats
						foreach ($chats as $DB_chat) {
							// Retrieving the data of the chat
							$DB_chat = yield $this -> getInfo($DB_chat['id']);
							$DB_chat = $DB_chat['Chat'] ?? NULL;

							/**
							* Checking if the chat is a group that is migrated to a supergroup
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
							if (empty($DB_chat) == FALSE && $DB_chat['_'] === 'chat' && $DB_chat['migrated_to']['_'] !== 'inputChannelEmpty') {
								// Closing the statement
								$statement -> close();

								$old_id = $DB_chat['id'];
								$DB_chat = yield $this -> getPwrChat($DB_chat['migrated_to']['channel_id']);

								try {
									yield $transaction -> execute('UPDATE `Chats` SET `id`=?, `type`=?, `title`=?, `username`=?, `invite_link`=? WHERE `id`=?;', [
										$DB_chat['id'],
										$DB_chat['type'],
										$DB_chat['title'],
										$DB_chat['username'],
										$DB_chat['invite'],
										$old_id
									]);
								} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
									$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

									try {
										$statement = yield $transaction -> prepare('UPDATE `Chats` SET `type`=?, `title`=?, `username`=?, `invite_link`=? WHERE `id`=?;');
									} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
										$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

										// Closing the transaction
										yield $transaction -> close();
										return;
									}
									continue;
								}

								// Commit the change
								$transaction -> commit();

								try {
									$statement = yield $transaction -> prepare('UPDATE `Chats` SET `type`=?, `title`=?, `username`=?, `invite_link`=? WHERE `id`=?;');
								} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
									$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

									// Closing the transaction
									yield $transaction -> close();
									return;
								}
								continue;
							}

							try {
								yield $statement -> execute([
									$DB_chat['type'],
									$DB_chat['title'],
									$DB_chat['username'],
									$DB_chat['invite'],
									$DB_chat['id']
								]);
							} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							}
						}

						// Closing the statement
						$statement -> close();

						// Commit the change
						yield $transaction -> commit();

						// Closing the transaction
						yield $transaction -> close();

						/**
						* Retrieving the (super)groups list
						*
						* array_filter() filters the array by the type of each chat
						* array_map() convert each chat to its id
						*/
						$chats = array_filter($chats, function ($n) {
							return $n['type'] == 'supergroup' || $n['type'] == 'chat';
						});
						$chats = array_map(function ($n) {
							return $n['id'];
						}, $chats);

						// Cycle on the list of the (super)groups
						foreach ($chats as $DB_chat) {
							// Retrieving the data of the chat
							$DB_chat = yield $this -> getPwrChat($DB_chat);

							/**
							* Retrieving the members' list of the chat
							*
							* array_filter() filters the array by the type of each member
							* array_map() convert each member to its id
							*/
							$members = array_filter($DB_chat['participants'], function ($n) {
								return $n['role'] == 'user';
							});
							$members = array_map(function ($n) {
								return $n['user']['id'];
							}, $members);

							// Cycle on the list of the members
							foreach ($members as $member) {
								/**
								* Downloading the user's informations from the Combot Anti-Spam API
								*
								* json_decode() convert a JSON string into a PHP variables
								*/
								$result = yield $this -> getHttpClient() -> request(new Amp\Http\Client\Request('https://api.cas.chat/check?user_id=' . $member));

								// Retrieving the result
								$result = yield $result -> getBody() -> buffer();

								$result = json_decode($result, TRUE);

								// Retrieving the data of the new member
								$member = yield $this -> getInfo($member);
								$member = $member['User'] ?? NULL;

								/**
								* Checking if the user isn't a spammer, isn't a deleted account and is a normal user
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
								if ($result['ok'] == FALSE && empty($member) && $member['_'] === 'user' && $member['scam'] == FALSE && $member['deleted'] == FALSE) {
									continue;
								}

								$banned []= [
									'channel' => $update['chat_id'],
									'user_id' => $member['id'],
									'banned_rights' => [
										'_' => 'chatBannedRights',
										'view_messages' => TRUE,
										'send_messages' => TRUE,
										'send_media' => TRUE,
										'send_stickers' => TRUE,
										'send_gifs' => TRUE,
										'send_games' => TRUE,
										'send_inline' => TRUE,
										'embed_links' => TRUE,
										'send_polls' => TRUE,
										'change_info' => TRUE,
										'invite_users' => TRUE,
										'pin_messages' => TRUE,
										'until_date' => 0
									]
								];
							}
						}

						yield $this -> channels -> editBanned($banned);

						/**
						* Retrieving the admins' list
						*
						* array_map() convert admin to its id
						*/
						try {
 							$result = yield $this -> DB -> query('SELECT `id` FROM `Admins`;');
						} catch (Amp\Sql\FailureException $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							return;
						}

						$admins = [];

						// Cycle on the result
						while (yield $result -> advance()) {
							$admins []= $result -> getCurrent();
						}

						$admins = array_map(function ($n) {
							return $n['id'];
						}, $admins);

						// Opening a transaction
						$transaction = yield $this -> DB -> beginTransaction();

						// Updating the admins' data
						try {
							$statement = yield $transaction -> prepare('UPDATE `Admins` SET `first_name`=?, `last_name`=? WHERE `id`=?;');
						} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

							// Closing the transaction
							yield $transaction -> close();
							return;
						}

						// Cycle on the list of the admins
						foreach ($admins as $id) {
							$admin = yield $this -> getInfo($id);
							$admin = $admin['User'] ?? NULL;

							/**
							* Checking if the user is a normal user
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
							if (empty($admin) || $admin['_'] !== 'user') {
								// Closing the statement
								$statement -> close();

								try {
									yield $transaction -> execute('DELETE FROM `Admins` WHERE `id`=?;', [
										$id
									]);
								} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
									$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

									try {
										$statement = yield $transaction -> prepare('UPDATE `Admins` SET `first_name`=?, `last_name`=? WHERE `id`=?;');
									} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
										$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

										// Closing the transaction
										yield $transaction -> close();
										return;
									}
									continue;
								}

								// Closing the statement
								$statement -> close();

								// Commit the change
								yield $transaction -> commit();

								try {
									$statement = yield $transaction -> prepare('UPDATE `Admins` SET `first_name`=?, `last_name`=? WHERE `id`=?;');
								} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
									$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);

									// Closing the transaction
									yield $transaction -> close();
									return;
								}
								continue;
							}

							try {
								yield $statement -> execute([
									$admin['first_name'],
									$admin['last_name'],
									$admin['id']
								]);
							} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
								$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							}
						}

						// Closing the statement
						$statement -> close();

						// Commit the change
						yield $transaction -> commit();

						// Closing the transaction
						yield $transaction -> close();

						// Retrieving the update message
						try {
							$answer = yield $this -> DB -> execute('SELECT `update_message` FROM `Languages` WHERE `lang_code`=?;', [
								$language
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							$answer = 'Database updated.';
						} catch (Amp\Sql\FailureException $e) {
							$answer = 'Database updated.';
						}

						// Checking if the query has product a result
						if ($answer instanceof Amp\Mysql\ResultSet) {
							yield $answer -> advance();
							$answer = $answer -> getCurrent();
							$answer = $answer['update_message'];
						}

						/**
						* Checking if the update message isn't setted
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
						if (empty($answer)) {
							$answer = 'Database updated.';
						}

						yield $this -> messages -> sendMessage([
							'no_webpage' => TRUE,
							'peer' => $sender['id'],
							'message' => $answer,
							'parse_mode' => 'HTML'
						]);
						break;
					default:
						// Checking if the chat isn't a private chat
						if ($message['to_id']['_'] !== 'peerUser') {
							$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because wasn\'t a message from a private chat (/' . $command . ' section).');
							return;
						}

						// Retrieving the unknown message
						try {
							$answer = yield $this -> DB -> execute('SELECT `unknown_message` FROM `Languages` WHERE `lang_code`=?;', [
								$language
							]);
						} catch (Amp\Sql\QueryError $e) {
							$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
							$answer = 'This command isn\'t supported.';
						} catch (Amp\Sql\FailureException $e) {
							$answer = 'This command isn\'t supported.';
						}

						// Checking if the query has product a result
						if ($answer instanceof Amp\Mysql\ResultSet) {
							yield $answer -> advance();
							$answer = $answer -> getCurrent();
							$answer = $answer['unknown_message'];
						}

						/**
						* Checking if the unknown message isn't setted
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
						if (empty($answer)) {
							$answer = 'This command isn\'t supported.';
						}

						yield $this -> messages -> sendMessage([
							'no_webpage' => TRUE,
							'peer' => $sender['id'],
							'message' => $answer,
							'parse_mode' => 'HTML'
						]);
						break;
				}

				// Checking if the chat isn't a private chat
				if ($message['to_id']['_'] !== 'peerUser') {
					yield $this -> channels -> deleteMessages([
						'revoke' => TRUE,
						'id' => [
							$message['id']
						]
					]);
				}
			/**
			* Checking if is the add_lang message
			*
			* preg_match_all() perform a RegEx match with the 'g' flag active
			*/
			} else if (preg_match_all('/^(lang\_code|(add\_lang|admin|confirm|help|invalid\_parameter|invalid\_syntax|mute|mute\_advert|link|reject|staff\_group|start|unknown|update)\_message)\:[[:blank:]]?([[:alnum:][:blank:]\_\<\>\/\@]*)$/miu', $message['message'], $matches, PREG_SET_ORDER)) {
				// Checking if the sender is a bot's admin
				try {
					yield $this -> DB -> execute('SELECT NULL FROM `Admins` WHERE `id`=?;', [
						$sender['id']
					]);
				} catch (Amp\Sql\QueryError $e) {
					$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
					return;
				} catch (Amp\Sql\FailureException $e) {
					$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because was a message from an unauthorized user (add language section).');
					return;
				}

				/**
				* Retrieving the primary key
				*
				* array_filter() filters the array by the first group of the match
				*/
				$primary_key = array_filter($matches, function ($n) {
					return $n[1] == 'lang_code';
				});

				/**
				* Checking if the message doesn't contains the lang_code
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
				if (empty($primary_key)) {
					$this -> logger('The Message ' . $update['id'] . ' wasn\'t managed because have a wrong syntax (add language section).');
					return;
				}

				/**
				* Removing the primary key from the matches
				*
				* array_search() search the primary key into the array
				* array_splice() extract the sub-array from the main array
				*/
				array_splice($matches, array_search($primary_key, $matches), 1);

				$primary_key = $primary_key[0][3];

				// Opening a transaction
				$transaction = yield $this -> DB -> beginTransaction();

				// Insert the language
				try {
					yield $transaction -> execute('INSERT INTO `Languages` (`lang_code`) VALUES (?);', [
						$primary_key
					]);
				} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
					$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
					return;
				}

				// Commit the change
				yield $transaction -> commit();

				// Adding the messages

				try {
					$statement = yield $transaction -> prepare('UPDATE `Languages` SET ?=? WHERE `lang_code`=?;');
				} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
					$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
					return;
				}

				// Cycle on the matches
				foreach ($matches as $match) {
					try {
						yield $statement -> execute([
							$match[1],
							$match[3],
							$primary_key
						]);
					} catch (Amp\Sql\QueryError | Amp\Sql\FailureException $e) {
						$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
					}
				}

				// Closing the statement
				$statement -> close();

				// Commit the change
				yield $transaction -> commit();

				// Closing the transaction
				yield $transaction -> close();

				// Retrieving the confirm message
				try {
					$answer = yield $this -> DB -> execute('SELECT `confirm_message` FROM `Languages` WHERE `lang_code`=?;', [
						$language
					]);
				} catch (Amp\Sql\QueryError $e) {
					$this -> logger('Failed to make the query, because ' . $e -> getMessage() . '.', \danog\MadelineProto\Logger::ERROR);
					$answer = 'Operation completed.';
				} catch (Amp\Sql\FailureException $e) {
					$answer = 'Operation completed.';
				}

				// Checking if the query has product a result
				if ($answer instanceof Amp\Mysql\ResultSet) {
					yield $answer -> advance();
					$answer = $answer -> getCurrent();
					$answer = $answer['confirm_message'];
				}

				/**
				* Checking if the confirm message isn't setted
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
				if (empty($answer)) {
					$answer = 'Operation completed.';
				}

				yield $this -> messages -> sendMessage([
					'no_webpage' => TRUE,
					'peer' => $chat['id'],
					'message' => $answer,
					'reply_to_msg_id' => $message['id'],
					'parse_mode' => 'HTML'
				]);

				// Sending the report to the channel
				$this -> report('<a href=\"mention:' . $sender['id'] . '\" >' . $sender['first_name'] . '</a> added a language (<code>' . $primary_key . '</code>) to the database.');
			}
		}
	}

	$MadelineProto = new danog\MadelineProto\API('inginf_bot.madeline', [
		'app_info' => [
			'lang_code' => 'en'
		],
		'db' => [
			'type' => 'mysql',
			'dbType' => [
				'host' => 'localhost',
				'port' => 3306,
				'user' => 'username',
				'password' => 'password',
				'database' => 'database_name'
			]
			],
		'logger' => [
			'logger' => danog\MadelineProto\Logger::FILE_LOGGER,
			'logger_level' => danog\MadelineProto\Logger::ULTRA_VERBOSE,
			'logger_param' => 'log/inginf_bot.log'
		]
	]);

	// Starting the bot
	$MadelineProto -> startAndLoop(inginf_bot::class);

	exit(0);
?>
