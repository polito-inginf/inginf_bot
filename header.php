<?php
//Gets the incoming JSON update
$content = file_get_contents("php://input");
//Decodes update into an array
$update = json_decode($content, true);
/** Opens a MySQL connection. Credentials are stored in private.php file
 * @todo Check for errors
 */
$dbconn = mysqli_connect(DBHOST, DBUSER, DBPWD, DBNAME);