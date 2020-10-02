<?php
	// Gets the incoming JSON update
	$content = file_get_contents("php://input");
	
	// Decodes update into an array
	$update = json_decode($content, TRUE);
	
	// Opens a MySQL connection. Credentials are stored in private.php file
	$db_conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	
	// Checking if the connecion is failed
	if ($db_conn -> connect_errno) {
		exit(1);
	}
?>
