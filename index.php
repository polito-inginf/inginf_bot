<?php
//This file contains all credentials for telegram bot and SQL DB and it is not included in the GitHub repository.
include 'private.php';
//This file contains all base functions
include 'basefunctions.php';
//This file contains all things to do before starting to handle updates
include 'header.php';
//This file contains all global variables from the various update types
include 'variables.php';
if($text === "/start") {
  sendMessage($userid,"Hi!");
  exit;
}
exit;