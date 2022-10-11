<?php
function request($url) {
  $url = api . $url;
	$url = str_replace(array(" ", "\n", "'", "#"), array("%20", "%0A%0D", "%27", "%23"), $url);
	$CurlSession = curl_init();
	curl_setopt($CurlSession,CURLOPT_URL,$url);
	curl_setopt($CurlSession,CURLOPT_HEADER, false);
	curl_setopt($CurlSession,CURLOPT_RETURNTRANSFER, true);
	$result=curl_exec($CurlSession);
	curl_close($CurlSession);
	return $result;
}
function getDbConnection() {
	$host = "xxxxxxxxxxx";
	$user = "xxxxxxxxxxxxx";
	$password = xxxxxxxxxxxxxx;
	$db = "xxxxxxxxxxxxxxxx";
	$connessione = new mysqli($host, $user, $password, $db);
	if ($connessione->connect_errno) {
		error_log("Connessione fallita: " . $connessione->connect_error . ".", 0);
		exit();
	}
	return $connessione;
}
function generateResponseKeyBoard($request, $kbdb) {
	global $language;
	if (strstartswith($request, "kb=")) {
		$thereq = substr($request, 3);
		$thereq = str_replace("\/", "/", $thereq);
		return getKeyBoardForPath($thereq, $kbdb);
	}
	return getKeyBoardForPath("", $kbdb);
}
function getKeyBoardForPath($input, $kbdb) {
	global $language;
	if (endsWith($input, "/")) $input = substr($input, 0, strlen($input) - 1);
	/* Gestione Argomenti */
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
	/* Gestione Argomenti FINE */
	/* Cerco Dir richiesta */
	$actual = $kbdb["keyboard"]["list"]; //è la lista di elementi di una cartella
	$actualdir = $kbdb["keyboard"]; //è la cartella
	$actualpath = "";
	if (strempty($path) === false) {
		$slashexplode = explode("/", $path);
		foreach ($slashexplode as $key => $value) {
			if (strempty($value)) continue;
			$err = 0;
			$type = $actual[$value]["type"];
			if (isset($actual[$value]) && ($type == "dir" || $type == "intdir")) {
				if ($type == "intdir") { //Caso intdir
					$intdir = $actual[$value]; //element intdir
					$namearray = trim($intdir["array"]);
					$intlink = $intdir["link"];
					$obj = getIntLinkObj($kbdb, $namearray, $intlink);
					if ($obj == null) $err = 1;
					else $actualdir = $obj;
				}
				else if ($type == "dir") //Caso dir
				$actualdir = $actual[$value];
				else $err = 1;
				//Setto actual (la lista)
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
	/* Cerco Dir richiesta FINE */
	/* Genero Keyboard con elementi della Dir */
	//Elementi per pagina: 0 vuol dire infiniti
	$elperpage = isset($actualdir["pags"]) ? $actualdir["pags"] : 0;
	$pagenum = 0; //Le pagine si contano da 0
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
			if (($language == "en" || $language == "EN") && isset($value["name_eng"])) $el["text"] = $value["name_eng"];
			else $el["text"] = $value["name"];
			$el["callback_data"] = $completeactualpath . "/" . $key;
		}
		else if ($type == "intdir") {
			$namearray = trim($value["array"]);
			$intlink = $value["link"];
			$obj = getIntLinkObj($kbdb, $namearray, $intlink);
			if (($language == "en" || $language == "EN") && isset($obj["name_eng"])) $el["text"] = $obj["name_eng"];
			else $el["text"] = $obj["name"];
			$el["callback_data"] = $completeactualpath . "/" . $key;
		}
		else if ($type == "link") {
			if (($language == "en" || $language == "EN") && isset($value["name_eng"])) $el["text"] = $value["name_eng"];
			else $el["text"] = $value["name"];
			$el["url"] = $value["link"];
		}
		else if ($type == "intlink") {
			$namearray = trim($value["array"]);
			$intlink = $value["link"];
			$obj = getIntLinkObj($kbdb, $namearray, $intlink);
			if ($obj == null) continue;
			if ($obj["type"] == "flink") {
				$el["text"] = $obj["start"]."-".$obj["end"];
			}
			else {
				if (($language == "en" || $language == "EN") && isset($obj["name_eng"])) $el["text"] = $obj["name_eng"];
				else $el["text"] = $obj["name"];
			}
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
	// Bottoni Paginazione
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
	// Bottone Indietro
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
	global $lang;
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
		if (($lang == "en") && isset($actual["name_eng"])) $elname = $actual["name_eng"];
		else $elname = $actual["name"];
		$ellink = $actual["link"];
	}
	else if ($type == "intlink") {
		$namearray = $actual["array"];
		$intlink = $actual["link"];
		$obj = getIntLinkObj($db, $namearray, $intlink);
		if ($obj != null) {
			if (($lang == "en") && isset($obj["name_eng"])) $elname = $obj["name_eng"];
			else $elname = $obj["name"];
			if($obj["type"] == "flink") {
				$elnamestart = strtolower($obj["start"]);
				$elnameend = strtolower($obj["end"]) . str_repeat("z", strlen($question) - strlen($elnameend));
				$elname = $obj["start"]."-".$obj["end"];
			}
			$ellink = $obj["link"];
			if (($lang == "en") && isset($obj["code_eng"])) $elcode = $obj["code_eng"];
			else $elcode = $obj["code"];
		}
	}
	//Creazione elemento da returnare
	if (strncmp(strtolower($elname) , $question, strlen($question)) == 0 || ($question >= $elnamestart && $question <= $elnameend)) {
		$answer["message_text"] = "<a href=\"$ellink\">$elname</a>";
		$answer["parse_mode"] = "HTML";
		$answer["disable_web_page_preview"] = true;
		$id = count($responsearray) + 1;
		$output = array(
			"type" => "article",
			"id" => $id,
			"input_message_content" => $answer,
			"title" => $elname
		);
		if (isset($elcode)) $output["description"] = $elcode;
		else $elcode = "";
		if (alreadyInArray($responsearray, $elname, $elcode) == false) array_push($responsearray, $output);
	}
	return;
}
function alreadyInArray($array, $name, $description) {
	foreach ($array as $key => $value) if ($value["title"] == $name && $value["description"] == $description) return true;
	return false;
}
//funzione di ricerca nel db
function searchDB($question, $db) {
	$question = strtolower($question);
	$responsearray = array();
	searchDBrecursive($db, $question, $db["keyboard"], $responsearray);
	return $responsearray;
}
function getBackPath($path) {
	$pos = strrpos($path, "/");
	return substr($path, 0, $pos);
}
function getIntLinkObj($db, $namearray, $intlink) {
	if (isset($db[$namearray])) { //Vedo se esiste l'array
		$arrref = $db[$namearray]; //Lo prendo
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
function sendMess($id, $urltext) {
	return request("sendMessage?text=$urltext&parse_mode=HTML&chat_id=$id&disable_web_page_preview=true");
}
function sendPhoto($chat_id, $photo, $caption) {
	if (strpos($caption, "\n")) $caption = urlencode($caption);
	return request("sendPhoto?chat_id=$chat_id&caption=$caption&photo=$photo&parse_mode=HTML");
}
function queryHandler($query) {
	global $db_conn;
  $query = $db_conn->prepare($query);
  $query->execute();
  $query->bind_result($result);
  $query->fetch();
  $query->close();
  return $result;
}
function ansquery($q_id, $ans) {
	$res = json_encode($ans);
	return request("answerInlineQuery?inline_query_id=$q_id&results=$res"); //&cache_time=0 per disabilitare cache
}
function inlinekeyboard($layout, $id, $msgtext) {
	if (strpos($msgtext, "\n")) $msgtext = urlencode($msgtext);
	$keyboard = json_encode(array("inline_keyboard" => $layout));
	return request("sendMessage?text=$msgtext&parse_mode=HTML&chat_id=$id&reply_markup=$keyboard&disable_web_page_preview=true");
}
function updateKeyboard($chat, $msg, $layout) {
	$keyboard = json_encode(array("inline_keyboard" => $layout));
	return request("editMessageReplyMarkup?chat_id=$chat&message_id=$msg&reply_markup=$keyboard");
}
function inDB($userid) {
  $stmt = mysqli_prepare($GLOBALS["db_conn"],"SELECT EXISTS(SELECT 1 FROM users WHERE userid=?)");
  mysqli_stmt_bind_param($stmt, "s", $userid);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $isindb);
  mysqli_stmt_fetch($stmt);
  return $isindb;
}

function generateMessage($cmd) {
	global $firstname, $language, $usertype, $userid, $kbdb, $text, $seen;
	if (!isset($seen)) $seen = "bentornat*";
	$lang = strtolower($language);
	$backText = $lang == "it" ? "Indietro" : "Back";
	if ($cmd == "/start") {
		if ($lang == "it") {
			$welcome = "Ciao <b>$firstname</b>, $seen! \xF0\x9F\x91\x8B \n\n" .
				"Questo bot contiene i link ai vari gruppi relativi ai corsi di Ingegneria Informatica (triennale e magistrale).\n\n" .
				"<b>Cerchi il gruppo generale?</b> Lo trovi qui: <b>@politoinginf</b>\n\n" . 
				"Se hai dubbi, consulta le domande frequenti con il comando /faq.\n\n" .
				"Per cercare direttamente un gruppo, usa la ricerca inline (per una guida, usa il comando /inline).\n\n" .
				"<b>Usa /language per cambiare lingua.</b>";
			if ($usertype == "admin") $welcome = $welcome . "\n\n&#x1F6D1 <b>Per le funzionalità da admin, usa il comando /admin.</b> &#x1F6D1";
		}
		else { 
			$welcome = "Hi <b>$firstname</b>!\n" .
				"This bot will redirect you towards all Computer Engineering groups (Bachelor's Degree & Master's Degree).\n\n" . 
				"Use /start to return to the main menu.\n\n" . 
				"<b>Use /language to change language (new feature, work in progress).</b>";
			if ($usertype == "admin") $welcome = $welcome . "\n\n&#x1F6D1 <b>For administration features, use the /admin command.</b> &#x1F6D1";
		}
		inlinekeyboard(getKeyBoardForPath("", $kbdb) , $userid, $welcome);
		exit();
	}
	else {
		switch ($cmd) {
			case "/faq": // ToDo: translation
				$response = "&#x2753&#x2753&#x2753 <b>DOMANDE FREQUENTI</b> &#x2753&#x2753&#x2753\n\n" .
				"D: Dove posso segnalare malfunzionamenti o chiedere aiuto riguardo al bot?\n" .
				"R: Nel <a href=\"https://t.me/joinchat/AWHhTU3onR2UCHWdAZ3FGQ\">gruppo per le segnalazioni</a>.\n\n" .
				"D: Sono nuovo su Telegram, dove posso trovare una guida?\n" .
				"R: Ho cercato di riassumere le principali funzionalità di Telegram nella guida che trovate col comando /manuale. &#x1F4D6\n\n" . 
				"D: Faccio fatica a trovare il gruppo che mi serve negli elenchi del bot. Per i crediti liberi, per esempio, ci sono troppe pagine!\n" .
				"R: Se conosci il nome dell'esame o del gruppo che stai cercando, puoi usare la ricerca inline. Per scoprire come si usa, utilizza il comando /inline.\n\n" .
				"D: Non trovo un esame (<i>oppure</i>, vorrei che venga aggiunto un gruppo al bot).\n" .
				"R: Scrivi nel <a href=\"https://t.me/joinchat/AWHhTU3onR2UCHWdAZ3FGQ\">gruppo per le segnalazioni</a> e riceverai una risposta a breve. &#x1F4E2\n\n" .
				"D: Perché non ci sono gruppi generali per la triennale e per la magistrale?\n" .
				"R: In realtà, i gruppi ci sono, solo che non sono gestiti da me. Chiedete i link in giro e vi saranno dati &#x1F609\n\n" .
				"D: Perché non ci sono gruppi generali per il secondo e per il terzo anno (triennale)?\n" .
				"R: Li reputo poco utili, poi molti studenti sono fuori corso e avrebbero poco senso.\n\n" .
				"D: Posso usare i gruppi del bot per pubblicizzare un canale/evento/altro gruppo?\n" .
				"R: In linea di massima, la pubblicità viene vista come spam e, pertanto, non è permesso. Nel <a href=\"https://t.me/joinchat/AWHhTVVCjwjHnaWGlYvfgw\">gruppo offerte di lavoro</a> è possibile inviare annunci (solo relativi al PoliTo) per chi cerca o offre lavoro. Annunci di altra natura non sono apprezzati, pertanto potrebbero essere eliminati.\n\n" .
				"<b>D: Per il mio corso esistono già vari gruppi Telegram. Posso creare un bot simile a questo che contenga tutti i link?</b>\n" .
				"<b>R: Assolutamente sì, trovate tutti i dettagli nella <a href=\"https://github.com/polito-inginf/inginf_bot\">repository ufficiale</a> del team degli studenti di Ingegneria Informatica</b>.\n";
				report($userid, $nametext, "FAQ_COMMAND");
				inlinekeyboard([[["text" => "↩ $backText", "callback_data" => "welcome"]]], $userid, $response);
			break;
			case "/nowhatsapp": // ToDo: translation
				$response = "<b>PERCHÉ QUEST'ODIO PER WHATSAPP?</b>\n\n" .
				"Non è assolutamente odio, semplicemente non ritengo WhatsApp la giusta piattaforma per i nostri scopi (gruppi tra studenti universitari). Ci sono mille motivi per cui non permetto di condividere link a gruppi WhatsApp. Credo che i pro di Telegram siano oggettivi:\n\n-Si evita la frammentazione.\n-Privacy: gli altri non vedono il vostro numero di telefono\n-Fino a 200000 utenti per gruppo\n-I file restano sul cloud: possono essere eliminati dai propri dispositivi\n-Telegram Desktop è indubbiamente utile (soprattutto per scaricamento/caricamento di file da PC)\n-È possibile modificare i messaggi\n-I gruppi possono essere gestiti molto meglio dagli amministratori\n\nPotrei continuare all'infinito, mi limito a dire che Telegram consente di fare le stesse cose che si possono fare con WhatsApp, con la stessa semplicità (non è solo per <i>smanettoni</i>), ma anche molto altro.\n\nOgnuno è libero di usare la piattaforma che vuole, ma <b>non usate i gruppi presenti in questo bot come contenitori per link a gruppi WhatsApp</b>.\n\n" .
				"La diretta conseguenza di questo è uno spopolamento dei gruppi Telegram, che crea ovviamente frammentazione (i gruppi su WhatsApp non sono minimamente organizzati), senza contare tutti i problemi di privacy legati alla condivisione del proprio numero di telefono.\n\nEventuali inutili discussioni su questa decisione porteranno all'allontanamento degli interessati dal gruppo e dal bot.\n\nVi ringrazio per la collaborazione.";
				report($userid, $nametext, "NOWHATSAPP_COMMAND");
				inlinekeyboard([[["text" => "↩ $backText", "callback_data" => "welcome"]]], $userid, $response);
			break;
			case "/inline": // ToDo: translation
				$response = "&#x1F50D&#x1F50D&#x1F50D <b>COMANDI INLINE</b> &#x1F50D&#x1F50D&#x1F50D\n\n" .
				"Se stai cercando un corso in particolare, puoi usare il bot in modalità <i>inline</i>.\n\n" .
				"In una chat qualsiasi (anche in quella con il bot) scrivi il messaggio:\n\n<code>@inginf_bot nome_esame</code>\n\ndove, al posto di <i>nome_esame</i>, scriverai il nome del corso.\n\n" .
				"Se il corso è presente nel database, potrai sceglierlo da un menu a tendina e riceverai come risposta il link.\n\n" .
				"Questa funzione è utile perché può essere usata per inoltrare un link in una chat qualsiasi: se qualcuno chiede il link al gruppo di compravendita di libri/appunti, basterà scrivere <code>@inginf_bot libri</code> e scegliere il gruppo dal menu per inviarlo nella chat corrente. " .
				"È comoda anche per chi non ha voglia di cercare un corso in mezzo a tutti i sottomenu del bot.";
				report($userid, $nametext, "INLINE_COMMAND");
				inlinekeyboard([[["text" => "↩ $backText", "callback_data" => "welcome"]], [["text" => "Prova ricerca inline!", "switch_inline_query_current_chat" => ""]]], $userid, $response);
			break;
			case "/manuale": // ToDo: translation
				request("sendMessage?text=<a href=\"https://telegra.ph/Telegram---Manuale-di-sopravvivenza-10-07\">GUIDA TELEGRAM</a>&parse_mode=HTML&chat_id=$userid");
				report($userid, $nametext, "MANUAL_COMMAND");
			break;
			case "/language":
				$response = "&#x1F6D1 <b>LANGUAGE</b> &#x1F6D1\n\n" .
				"Use the buttons to set language. English translation in progress.";
				inlinekeyboard([
					[["text" => "\xf0\x9f\x87\xac\xf0\x9f\x87\xa7ENGLISH", "callback_data" => "set_eng"]],
					[["text" => "\xf0\x9f\x87\xae\xf0\x9f\x87\xb9ITALIANO", "callback_data" => "set_ita"]],
					[["text" => "↩ $backText", "callback_data" => "welcome"]]
				], $userid, $response);
			break;			
			case "/admin": // ToDo: translation
				if ($usertype == "admin") {
					$adminId = $userid; //IMPORTANTE!
					$response = "&#x1F6D1 <b>PANNELLO DI CONTROLLO ADMIN</b> &#x1F6D1\n\n";
					inlinekeyboard([
							[["text" => "Gruppo generale admin", "url" => "https://t.me/joinchat/AWHhTRWe34ygeE6FFq6Qmw"]],
							[["text" => "Utenti bot", "callback_data" => "users"]],
							[["text" => "↩ $backText", "callback_data" => "welcome"]]
					], $userid, $response);
				}
				else report($userid, $nametext, "ADMIN_NOT_ALLOWED");
			break;
			case "/sendtest":
				if ($userid == OWNERID) {
					$caption = "Buongiorno gente! 😁\n\nSiamo felici di vedere come i gruppi di Telegram del Politecnico nel tempo stiano diventando una vera e propria istituzione. 🥳\n\n" .
											"Per portare a termine questo processo pensiamo sia necessario un gruppo generale, non direttamente connesso a organizzazioni studentesche, con maggiore enfasi su argomenti off-topic.\n\n" . 
											"Vi invitiamo, dunque, ad entrare nel nuovo gruppo generale, dedicato a chiunque faccia parte del Politecnico di Torino e a condividerlo dove ritenete opportuno!\n\n" .
											"Buona permanenza! 👋\n\n" .
											"👇👇👇👇👇👇\n@PolitoStudents";
					sendPhoto(OWNERID, photo, $caption);

					$list = getUserId();

					foreach($list as $usrid) {
						sendMess(OWNERID, $usrid[0]);
						mysqli_query($GLOBALS["db_conn"], "UPDATE tmp_msg SET sent=1 WHERE userid='$usrid[0]'");
					}

				}
			break;
			case "/setadmin":
				if ($userid == OWNERID) userMod("admin",substr($text, strlen($cmd)+1));
				else report($userid, $nametext, "ADMIN_NOT_ALLOWED");
			break;
			case "/banuser":
				if ($userid == OWNERID) userMod("banned",substr($text, strlen($cmd)+1));
				else report($userid, $nametext, "ADMIN_NOT_ALLOWED");
			break;
			case "/grabid":
				if ($userid == OWNERID) grabID(substr($text, strlen($cmd)+1));
				else report($userid, $nametext, "ADMIN_NOT_ALLOWED");
			break;
			default:
				if ($lang == "it") $response = "Comando non riconosciuto!";
				else $response = "Wrong command!";
				report($userid, $nametext, "WRONG_COMMAND");
				inlinekeyboard([[["text" => "↩ $backText", "callback_data" => "welcome"]]], $userid, $response);
			break;
		}
  }
}
?>
