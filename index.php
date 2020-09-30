<?php
//This file includes all the constants needed by the bot
include 'constants.php';
//This file contains all things to do before starting to handle updates
include 'header.php';
//This file contains all base functions
include 'basefunctions.php';
//This file contains all global variables from the various update types
include 'variables.php';
/** Checks if user is an admin, otherwise exits
 * @todo Update with a better solution (query)
 * @todo Fix include order
 * @todo Create one or more functions to check if a user is admin and related permissions
 */
if (!in_array($userid,ADMINS)) exit;
//This file contains logger code and variables
include 'logger.php';
if ($text === "/start") {
  sendMessage($userid,"Hi!");
  exit;
}
exit;