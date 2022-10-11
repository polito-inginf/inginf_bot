<?php
function userMod($type,$userid) {
  global $db_conn;
  $inDB = queryHandler("SELECT COUNT(*) FROM users WHERE userid='$userid'");
  if($inDB) {
    $insert = $db_conn->prepare("UPDATE users SET type=? WHERE userid=?");
    $insert->bind_param("si", $type, $userid);
    $insert->execute();
    $insert->close();
    if ($type == "admin") sendMess(OWNERID,"Utente <code>" .$userid. "</code> aggiunto alla lista degli amministratori.");
    else if ($type == "banned") sendMess(OWNERID,"Utente <code>" .$userid. "</code> bannato.");
  }
  else sendMess(OWNERID,"Utente " . $userid . " non trovato nel database.");
}
function sendallTool($message) {
  global $db_conn;
  $query = $db_conn->prepare("SELECT userid FROM users");
  $query->execute();
  $query->bind_result($id);
  while ($query->fetch()) sendMess($id, $message);
  $query->close();
  exit();
}
function grabID($username) {
  global $userid;
  if (strstartswith($username,"@")) $username = substr($username, 1);
  if ($username == "") sendMess(OWNERID,"Inserire username!");
  else if ($id = queryHandler("SELECT userid FROM users WHERE username='$username'")) sendMess($userid,"ID = <code>$id</code>");
  else sendMess(OWNERID,"Utente " . $username . " non trovato nel database.");
}
function usersCount() {
  global $userid;
  setlocale(LC_TIME, 'it_IT');
  $date_month = strftime("%B", strtotime('last month'));
  if (date("m", strtotime('last month')) == "04" || date("m", strtotime('last month')) == "08" || date("m", strtotime('last month')) == "10") $prep = "ad";
  else $prep = "a";
  $date_until_month = strftime("%e %B", strtotime('last day of previous month'));
  $total = queryHandler("SELECT COUNT(*) FROM users");
  $month = queryHandler("SELECT COUNT(*) FROM users WHERE YEAR(date_first) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(date_first) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)");
  $chart_total = queryHandler("SELECT(SELECT COUNT(*) FROM users) - (SELECT COUNT(*) FROM users WHERE MONTH(date_first) = MONTH(CURRENT_DATE))");
  sendMess($userid, "Il bot è stato utilizzato da:\n-<code>$total</code> persone <b>in totale</b>\n-<code>$month</code> persone <b>$prep $date_month</b>\n-<code>$chart_total</code> persone <b>fino al $date_until_month</b>.");
}
function report($id, $name, $reason) {
  global $db_conn;
  $insert = $db_conn->prepare("INSERT INTO reports (userid, name, reason, severe) VALUES (?, ?, ?, ?)");
  switch ($reason) {
    case "ADMIN_NOT_ALLOWED":
      $reason = "Used admin command";
      $severe = false;
      sendMess($id, "Non sei abilitato a usare questo comando! Ogni abuso verrà segnalato. Usa /start per tornare al menu principale.");
      break;
    case "INLINE_COMMAND":
      $reason = "Used inline command";
      $severe = false;
      break;
    case "MANUAL_COMMAND":
      $reason = "Used manuale command";
      $severe = false;
      break;
    case "FAQ_COMMAND":
      $reason = "Used faq command";
      $severe = false;
      break;
    case "WRONG_COMMAND":
      $reason = "Used wrong command";
      $severe = false;
      break;
    case "NOWHATSAPP_COMMAND":
      $reason = "Used nowhatsapp command";
      $severe = false;
      break;
    case "BANNED_USER":
      $reason = "Banned user";
      $severe = false;
      break;
  }
  $insert->bind_param("sssi", $id, $name, $reason, $severe);
  $insert->execute();
  if ($severe) {
    $query = $db_conn->prepare("SELECT COUNT(*) FROM `reports` WHERE severe=1 AND userid=?");
    $query->bind_param("i",$id);
    $query->execute();
    $query->bind_result($sevnum);
    $query->fetch();
    if ($sevnum == 2) {
      sendMess(OWNERID, "L'utente <code>$name</code> (id: <code>$id</code>) ha <b>$sevnum</b> segnalazioni!");
    }
  }
  $insert->close();
}
if ($usertype == "banned") {
  report($userid, $nametext, "BANNED_USER");
  if (isset($update['inline_query'])) exit(0);
  sendMess($userid, "Non sei autorizzato ad utilizzare il bot. Il tuo messaggio è stato segnalato all'amministratore.\n\nPer info, contatta @xxxxxxxxx.");
  exit;
}
if(inDB($userid)) $seen = "bentornat*";
else $seen = "benvenut*";
$now = date("Y-m-d H:i:s");
$insert = $db_conn->prepare("INSERT INTO users (userid, name, username, type) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE type=?, date=?, name=?, username=?");
$insert->bind_param("ssssssss", $userid, $nametext, $username, $usertype, $usertype, $now, $nametext, $username);
$insert->execute();
$insert->close();
if($cbdata == "users") {
  usersCount();
  exit;
}
?>
