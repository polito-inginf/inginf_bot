<?php

/** This section of the code defines all required variables
 * by getting information from Telegram Bot's update.
 * @todo Adapt to our needs
 * @todo Not the best way to proceed, use another method
 * @todo Check for undefined variables with isset() or empty()
 */

$text = isset($update['message']['text']) ? trim($update['message']['text']) : "";
$cbdata = $update['callback_query']['data'];
$msgid = $update['callback_query']['message']['message_id'];
$cbid = $update['callback_query']['id'];
$ilqid = $update["inline_query"]["id"];
$ilquery = $update["inline_query"]["query"];

if (isset($update['callback_query'])) {
  $userid = $update['callback_query']['from']['id'];
  $firstname = $update['callback_query']['from']['first_name'];
  $lastname = isset($update['callback_query']['from']['last_name']) ? $update['callback_query']['from']['last_name'] : "";
  $username = isset($update['callback_query']['from']['username']) ? $update['callback_query']['from']['username'] : "";
  $lang = isset($update['callback_query']['from']['language_code']) ? $update['callback_query']['from']['language_code'] : "";
}
else if (isset($update['inline_query'])) {
  $userid = $update['inline_query']['from']['id'];
  $firstname = $update['inline_query']['from']['first_name'];
  $lastname = isset($update['inline_query']['from']['last_name']) ? $update['inline_query']['from']['last_name'] : "";
  $username = isset($update['inline_query']['from']['username']) ? $update['inline_query']['from']['username'] : "";
  $lang = isset($update['inline_query']['from']['language_code']) ? $update['inline_query']['from']['language_code'] : "";
}
else if (isset($update['message'])) {
  $userid = $update['message']['from']['id'];
  $firstname = $update['message']['from']['first_name'];
  $lastname = isset($update['message']['from']['last_name']) ? $update['message']['from']['last_name'] : "";
  $username = isset($update['message']['from']['username']) ? $update['message']['from']['username'] : "";
  $lang = isset($update['message']['from']['language_code']) ? $update['message']['from']['language_code'] : "";
}
$nametext = $firstname . " " . $lastname;
