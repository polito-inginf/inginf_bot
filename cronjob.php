<?php

define('token', "xxxxxxxxx");
define('api', 'https://api.telegram.org/bot' . token . '/');
define('photo', "xxxxxxxxx");
include "functions.php";
include "header.php";

function getUserId() {
	$result = mysqli_query($GLOBALS["db_conn"], "SELECT DISTINCT userid FROM tmp_msg WHERE sent=0 LIMIT 20");
	$rows = [];
	while($row = mysqli_fetch_array($result))
	{
    	$rows[] = $row;
	}
	
	return $rows;
}

function setAsSent($userid) {
	return mysqli_query($GLOBALS["db_conn"], "UPDATE tmp_msg SET sent=1 WHERE userid='$userid'");
}

function countLeft() {
	$stmt = mysqli_prepare($GLOBALS["db_conn"], "SELECT COUNT(*) FROM tmp_msg WHERE sent=0");
	
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $res);
	mysqli_stmt_fetch($stmt);
	return $res;
}

$list = getUserId();

$response = "Buongiorno gente! 😁\n\nSiamo felici di vedere come i gruppi di Telegram del Politecnico nel tempo stiano diventando una vera e propria istituzione. 🥳\n\n" .
"Per portare a termine questo processo pensiamo sia necessario un gruppo generale, non direttamente connesso a organizzazioni studentesche, con maggiore enfasi su argomenti off-topic.\n\n" . 
"Vi invitiamo, dunque, ad entrare nel nuovo gruppo generale, dedicato a chiunque faccia parte del Politecnico di Torino e a condividerlo dove ritenete opportuno!\n\n" .
"Buona permanenza! 👋\n\n" .
"👇👇👇👇👇👇\n@PolitoStudents";

foreach($list as $usrid){
		sendPhoto($usrid[0], photo, $response);
    // sendMess($usrid[0],$response);
    setAsSent($usrid[0]);
}

$left = countLeft();
// sendMess(xxxxxxxxx, $left);
if($left<200)
    sendMess(xxxxxxxxx, "Messaggi inviati con successo. Ne restano " . $left . " da inviare.");

?>