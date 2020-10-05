<?php
/**
* These are all the base functions that will be used for communicating
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
* @license		https://choosealicense.com/licenses/gpl-3.0/
*/

include 'private.php';

/**
 * Bitwise operators constants
 */

const MARKDOWN = 0b001;
const ENABLE_PAGE_PREVIEW = 0b010;
const DISABLE_NOTIFICATION = 0b100;

define('api', 'https://api.telegram.org/bot' . token . '/');

define('LOG_LVL', 7);
