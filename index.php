<?php
/**
 *
 * Template per la creazione di un bot Telegram in PHP che rimandi a gruppi/canali
 * relativi al proprio corso di studi, ma può essere utile anche per altri scopi.
 * 
 * Il codice presente in questa repository non è completo, ma serve a dare
 * un'idea sul funzionamento del bot. Non è ancora stato commentato completamente,
 * per qualsiasi domanda, cercatemi su Telegram.
 *
 * È possibile riutilizzare questo template nel rispetto della licenza GNU GPLv3.
 *
 * @author     Giorgio Pais
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt
 *
 */
$content = file_get_contents("php://input");
$update = json_decode($content, true);
define('token', "YOUR_TOKEN_HERE");
define('api', 'https://api.telegram.org/bot' . token . '/');
if (!$update) exit;
$message = isset($update['message']) ? $update['message'] : "";
$text = isset($message['text']) ? $message['text'] : "";
$text = trim($text);
$lang = isset($update['message']['from']['language_code']) ? $update['message']['from']['language_code'] : "";
$cbdata = $update['callback_query']['data'];
$msgid = $update['callback_query']['message']['message_id'];
$ilqid = $update["inline_query"]["id"];
$ilquery = $update["inline_query"]["query"];
if (isset($update['callback_query'])) {
	$userid = $update['callback_query']['from']['id'];
	$firstname = $update['callback_query']['from']['first_name'];
}
else if (isset($update['message'])) {
	$userid = $update['message']['from']['id'];
	$firstname = $update['message']['from']['first_name'];
}
$database = json_decode(file_get_contents('database.json') , true);
$kbdb = $database;
$response = '';
if (isset($cbdata)) {
	$kbbb = generateResponseKeyBoard($cbdata, $kbdb);
	updateKeyboard($userid, $msgid, $kbbb);
}
/**
 * N.B.: Funziona solo se è un comando (non spamma a caso).
 * Può essere aggiunto ai gruppi, ma è inutile perché c'è la ricerca inline
 */
if (substr($text, 0, 1) === '/') { // Se è un comando:
/**
 * strtolower converte in minuscolo.
 * explode divide il parametro in sottostringhe ogni volta che trova il separatore specificato
 * (in questo caso, uno spazio).
 * La prima stringa [0] viene assegnata alla variabile $cmd.
 */
  $cmd = explode(' ',strtolower($text))[0];
  /**
 * I seguenti comandi sono completamente personalizzabili.
 * È possibile rimuoverli, aggiungerne altri o modificarli aggiungendo casi allo switch.
 * Per concatenare le stringhe, usare .
 */
  switch ($cmd) {
  	case "/start":
  		$response = "Ciao <b>$firstname</b>, benvenuto/a!\n\n" .
  		"(Resto del messaggio da inviare appena ricevuto il comando /start)";
  		inlinekeyboard(getKeyBoardForPath("", $kbdb) , $userid, $response); // Mostra la tastiera principale
  	   break;
  	case "/faq":
  		$response = "<b>DOMANDE FREQUENTI</b>\n\n" .
      "(Lista di FAQ)";
  		inlinekeyboard([[["text" => "↩ Indietro", "callback_data" => "kb/0"]]], $userid, $response);
  	  break;
  	case "/inline":
  		$response = "<b>COMANDI INLINE</b>\n\n" .
      "(Descrizione modalità inline)";
      inlinekeyboard([[["text" => "↩ Indietro", "callback_data" => "kb/0"]]], $userid, $response);
  	  break;
    case "/manuale":
      request("sendMessage?text=<a href=\"(link al manuale, senza parentesi)\">GUIDA TELEGRAM</a>&parse_mode=HTML&chat_id=$userid");
      break;
    default:
      sendMess($userid, "Comando non riconosciuto!");
      break;
  }
}
// Gestore modalità inline
if (isset($update["inline_query"])) {
  if ($ilquery != null) {
    $ilquery = trim($ilquery);
    if (strlen($ilquery) >= 2) {
      $queryans = searchDB($ilquery, $kbdb);
      ansquery($ilqid, $queryans);
    }
  }
}
// Tutte le funzioni
function generateResponseKeyBoard($request, $kbdb) {
	if (strstartswith($request, "kb=")) {
		$thereq = substr($request, 3);
		$thereq = str_replace("\/", "/", $thereq);
		return getKeyBoardForPath($thereq, $kbdb);
	}
	return getKeyBoardForPath("", $kbdb);
}
function getKeyBoardForPath($input, $kbdb) {
	if (endsWith($input, "/"))
	$input = substr($input, 0, strlen($input) - 1);
	$intpos = strrpos($input, "?");
	if ($intpos === false) {
		$argstr = "";
		$path = $input;
	}
	else {
		$argstr = substr($input, $intpos + 1, strlen($input) - $intpos);
		$path = substr($input, 0, $intpos);
	}
	$args = array();
	if (strempty($argstr) === false) {
		$Eexplode = explode("&", $argstr);
		foreach ($Eexplode as $key => $value) {
			$eqexplode = explode("=", $value);
			if (count($eqexplode) == 2) $args[$eqexplode[0]] = $eqexplode[1];
		}
	}
	$actual = $kbdb["keyboard"]["list"];
	$actualdir = $kbdb["keyboard"];
	$actualpath = "";
	if (strempty($path) === false) {
		$slashexplode = explode("/", $path);
		foreach ($slashexplode as $key => $value) {
			if (strempty($value)) continue;
			$err = 0;
			$type = $actual[$value]["type"];
			if (isset($actual[$value]) && ($type == "dir" || $type == "intdir")) {
				if ($type == "intdir") {
					$intdir = $actual[$value];
					$namearray = trim($intdir["array"]);
					$intlink = $intdir["link"];
					$obj = getIntLinkObj($kbdb, $namearray, $intlink);
					if ($obj == null) $err = 1;
					else $actualdir = $obj;
				}
				else if ($type == "dir")
				$actualdir = $actual[$value];
				else $err = 1;
				if (!isset($actualdir["list"])) $actual = array();
				else $actual = $actualdir["list"];
				$actualpath .= "/" . $value;
			}
			else $err = 1;
			if ($err == 1) {
				$actual = $kbdb["keyboard"]["list"];
				$actualdir = $kbdb["keyboard"];
				$actualpath = "";
			}
		}
	}
	$elperpage = isset($actualdir["pags"]) ? $actualdir["pags"] : 0;
	$pagenum = 0;
	if (isset($args["p"])) $pagenum = $args["p"];
	$startindex = $elperpage * $pagenum;
	$endindex = $elperpage * $pagenum + $elperpage;
	$count = count($actual);
	if ($elperpage == 0) $endindex = $count;
	else {
		$lastpagenum = floor($count / $elperpage);
		if ($count % $elperpage == 0) $lastpagenum -= 1;
	}
	if ($endindex > $count) $endindex = $count;
	$completeactualpath = "kb=" . $actualpath;
	$keyboardarray = array();
	$rowarray = array();
	$nelins = 0;
	for ($i = $startindex;$i < $endindex;$i++) {
		$key = $i;
		$value = $actual[$i];
		$el = array();
		$elfullrow = isset($value["frow"]) ? $value["frow"] : 0;
		$type = $value["type"];
		if ($type == "dir") {
			$el["text"] = $value["name"];
			$el["callback_data"] = $completeactualpath . "/" . $key;
		}
		else if ($type == "intdir") {
			$namearray = trim($value["array"]);
			$intlink = $value["link"];
			$obj = getIntLinkObj($kbdb, $namearray, $intlink);
			$el["text"] = $obj["name"];
			$el["callback_data"] = $completeactualpath . "/" . $key;
		}
		else if ($type == "link") {
			$el["text"] = $value["name"];
			$el["url"] = $value["link"];
		}
		else if ($type == "intlink") {
			print_r($value);
			$namearray = trim($value["array"]);
			$intlink = $value["link"];
			$obj = getIntLinkObj($kbdb, $namearray, $intlink);
			if ($obj == null) continue;
			$el["text"] = $obj["name"];
			$el["url"] = $obj["link"];
		}
		else continue;
		if ($elfullrow == 1 && count($rowarray) != 0) {
			array_push($keyboardarray, $rowarray);
			$rowarray = array();
		}
		array_push($rowarray, $el);
		if ($elfullrow == 1) {
			array_push($keyboardarray, $rowarray);
			$rowarray = array();
		}
		if ($nelins % 2 == 1) {
			array_push($keyboardarray, $rowarray);
			$rowarray = array();
		}
		$nelins++;
	}
	if (count($rowarray) != 0) array_push($keyboardarray, $rowarray);
	if ($elperpage != 0) {
		$pagesarray = array();
		if ($pagenum != 0) {
			$pagedec["text"] = "⬅️️ Pagina " . ($pagenum);
			$pagedec["callback_data"] = $completeactualpath . "?p=" . ($pagenum - 1);
			array_push($pagesarray, $pagedec);
		}
		if ($pagenum < $lastpagenum) {
			$pageinc["text"] = "Pagina " . ($pagenum + 2) . " ➡️";
			$pageinc["callback_data"] = $completeactualpath . "?p=" . ($pagenum + 1);
			array_push($pagesarray, $pageinc);
		}
		array_push($keyboardarray, $pagesarray);
	}
	if (strempty($actualpath) == false) {
		$backarray = array();
		$back["text"] = "↩ Indietro";
		$back["callback_data"] = "kb=" . getBackPath($actualpath);
		array_push($backarray, $back);
		array_push($keyboardarray, $backarray);
	}
	return $keyboardarray;
}
function searchDBrecursive($db, $question, $actual, &$responsearray) {
	$type = $actual["type"];
	$elname = "";
	$ellink = "";
	if ($type == "dir") {
		foreach ($actual["list"] as $key => $value) searchDBrecursive($db, $question, $value, $responsearray);
		return;
	}
	else if ($type == "intdir") {
		$namearray = $actual["array"];
		$intlink = $actual["link"];
		$obj = getIntLinkObj($db, $namearray, $intlink);
		if ($obj != null) foreach ($obj["list"] as $key => $value) searchDBrecursive($db, $question, $value, $responsearray);
		return;
	}
	else if ($type == "link") {
		$elname = $actual["name"];
		$ellink = $actual["link"];
	}
	else if ($type == "intlink") {
		$namearray = $actual["array"];
		$intlink = $actual["link"];
		$obj = getIntLinkObj($db, $namearray, $intlink);
		if ($obj != null) {
			$elname = $obj["name"];
			$ellink = $obj["link"];
			$elcode = $obj["code"];
		}
	}
	if (strncmp(strtolower($elname) , $question, strlen($question)) == 0) {
		$answer["message_text"] = "<a href=\"$ellink\">$elname</a>";
		if (isset($elcode)) $answer["message_text"] .= " (" . $elcode . ")";
		else $elcode = "";
		$answer["parse_mode"] = "HTML";
		$answer["disable_web_page_preview"] = true;
		$id = count($responsearray) + 1;
		$output = array(
			"type" => "article",
			"id" => $id,
			"input_message_content" => $answer,
			"title" => $elname
		);
		$output["description"] = $elcode;
		if (alreadyInArray($responsearray, $elname, $elcode) == false) array_push($responsearray, $output);
	}
	return;
}
function alreadyInArray($array, $name, $description) {
	foreach ($array as $key => $value) if ($value["title"] == $name && $value["description"] == $description) return true;
	return false;
}
function searchDB($question, $db) {
	$inputstr = strtolower($question);
	$responsearray = array();
	searchDBrecursive($db, $question, $db["keyboard"], $responsearray);
	return $responsearray;
}
function getBackPath($path) {
	$pos = strrpos($path, "/");
	return substr($path, 0, $pos);
}
function getIntLinkObj($db, $namearray, $intlink) {
	if (isset($db[$namearray])) {
		$arrref = $db[$namearray];
		$obj = $arrref[$intlink];
		return $obj;
	}
	else return null;
}
function strstartswith($haystack, $needle) {
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}
function endsWith($haystack, $needle) {
	$length = strlen($needle);
	if ($length == 0) return true;
	return (substr($haystack, -$length) === $needle);
}
function strempty($str) {
	return is_string($str) && strlen($str) === 0;
}
function request($method) {
	$req = file_get_contents(api . $method);
	return $req;
}
function sendMess($id, $urltext) {
	if (strpos($urltext, "\n")) $urltext = urlencode($urltext);
	return request("sendMessage?text=$urltext&parse_mode=HTML&chat_id=$id&disable_web_page_preview=true");
}
function ansquery($q_id, $ans) {
	$res = json_encode($ans);
	return request("answerInlineQuery?inline_query_id=$q_id&results=$res");
}
function inlinekeyboard($layout, $id, $msgtext) {
	if (strpos($msgtext, "\n")) $msgtext = urlencode($msgtext);
	$keyboard = array(
		"inline_keyboard" => $layout,
	);
	$keyboard = json_encode($keyboard);
	return request("sendMessage?text=$msgtext&parse_mode=HTML&chat_id=$id&reply_markup=$keyboard&disable_web_page_preview=true");
}
function updateKeyboard($chat, $msg, $layout) {
	$keyboard = array(
		"inline_keyboard" => $layout,
	);
	$keyboard = json_encode($keyboard);
	return request("editMessageReplyMarkup?chat_id=$chat&message_id=$msg&reply_markup=$keyboard");
}
exit(0);
?>
