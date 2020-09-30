<?php
//This file includes all the constants needed by the bot
include 'constants.php';
//This file contains all things to do before starting to handle updates
include 'header.php';
//This file contains all base functions
include 'basefunctions.php';
//This file contains logger code and variables
include 'logger.php';
//This file contains all global variables from the various update types
include 'variables.php';
//Checks if user is an admin, otherwise exits
if (!in_array($userid,ADMINS)) exit;
if ($text === "/start") {
  sendMessage($userid,"Hi!");
  exit;
}
exit;