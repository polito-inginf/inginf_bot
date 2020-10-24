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
const SHOW_ALERT = 1 << 3;
const SUPPORTS_STREAMING = 1 << 4;

const CAN_CHANGE_INFO = 1 << 5;
const CAN_POST_MESSAGES = 1 << 6;
const CAN_EDIT_MESSAGES = 1 << 7;
const CAN_DELETE_MESSAGES = 1 << 8;
const CAN_INVITE_USERS = 1 << 9;
const CAN_RESTRICT_MEMBERS = 1 << 10;
const CAN_PIN_MESSAGES = 1 << 11;
const CAN_PROMOTE_MEMBERS = 1 << 12;

/**
* The Bot API endpoint
*
* define() Defines a named constant
*/
define('api', 'https://api.telegram.org/bot' . token . '/');
