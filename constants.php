<?php
/**
* These are all the constants that will be used for communicating
* with Telegram Bot.
*
* No libraries are used in this project.
*
* @author		Giorgio Pais
* @author		Giulio Coa
* @author		Simone Cosimo
* @author		Luca Zaccaria
* @author		Alessio Bincoletto
* @author		Marco Smorti
*
* @copyright	2020- Giorgio Pais <info@politoinginf.it>
*
* @license		https://choosealicense.com/licenses/lgpl-3.0/
*/

include 'private.php';

// Bitwise operators constants
const MARKDOWN = 1 << 0;
const ENABLE_PAGE_PREVIEW = 1 << 1;
const DISABLE_NOTIFICATION = 1 << 2;

// The Bot API endpoint
define('api', 'https://api.telegram.org/bot' . token . '/');
