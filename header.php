<?php
$content = file_get_contents("php://input");
$update = json_decode($content, true);
// if (!$update) exit;
$db_conn = getDbConnection();
$chatid = isset($update['message']['chat']['id']) ? $update['message']['chat']['id'] : "";
$text = isset($update['message']['text']) ? trim($update['message']['text']) : "";
$cbdata = $update['callback_query']['data']; //testo chiamata (testo callback, identificatore pulsante)
if($cbdata == "kb/0") $response = $welcome;
$msgid = $update['callback_query']['message']['message_id']; //id messaggio a cui è legata la tastiera
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
// Inserimento request nel DB
if ($insert = $db_conn->prepare("INSERT INTO requests (userid, name, lang, request_data) VALUES (?, ?, ?, ?)")) {
	$nametext = $firstname . " " . $lastname;
	$insert->bind_param("ssss", $userid, $nametext, $lang, $content);
	$insert->execute();
}
$query = $db_conn->prepare("SELECT lang FROM users WHERE userid=?");
$query->bind_param("i", $userid);
$query->execute();
$query->bind_result($language);
$query->fetch();
$query->close();
if ($language != "IT" && $language != "EN") { // Se lingua NON settata dall'utente
	if ($language == NULL || $language == "") $language = "it"; // Imposta it se lingua non disponibile
	else $language = $lang;
	$insert = $db_conn->prepare("UPDATE users SET lang=? WHERE userid=?");
	$insert->bind_param("si", $language, $userid);
	$insert->execute();
	$insert->close();
}
$database = json_decode(file_get_contents('database.json') , true);
$kbdb = $database;
$response = '';
$usertype = queryHandler("SELECT type FROM users WHERE userid='$userid'");
if (!isset($usertype)) $usertype = "user";
if (isset($cbdata)) {
	if ($cbdata == "set_eng" || $cbdata == "set_ita") {
		$language = strtoupper(substr($cbdata,4,-1));
		$insert = $db_conn->prepare("UPDATE users SET lang=? WHERE userid=?");
		$insert->bind_param("si", $language, $userid);
		$insert->execute();
		$insert->close();
		$backText = $language == "IT" ? "Indietro" : "Back";
		$response = $language == "IT" ? "Lingua impostata correttamente." : "Language was set.";
		inlinekeyboard([[["text" => "↩ $backText", "callback_data" => "welcome"]]], $userid, $response);
		exit;
	}
	$lang = strtolower($language);
	if ($cbdata == "welcome") generateMessage("/start");
	if (!in_array($cbdata, CALLBACK_COMMANDS)) {
		$kbbb = generateResponseKeyBoard($cbdata, $kbdb);
		updateKeyboard($userid, $msgid, $kbbb);
	}
}
$lang = strtolower($language);
//Gestore inline query
if (isset($update["inline_query"])) {
  if ($ilquery != null) {
    $ilquery = trim($ilquery);
    if (strlen($ilquery) >= 2) {
      $queryans = searchDB($ilquery, $kbdb);
      ansquery($ilqid, $queryans);
    }
  }
}
?>
