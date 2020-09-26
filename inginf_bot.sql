/** SQL Database creator
 * @todo Adapt to our needs
 */

DROP DATABASE IF EXISTS `inginf_bot`;
CREATE DATABASE `inginf_bot` DEFAULT CHARACTER SET utf8;
USE `inginf_bot`;

CREATE TABLE `Admins` (
	`id` BIGINT,
	`first_name` TEXT DEFAULT NULL,
	`last_name` TEXT DEFAULT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8;

CREATE TABLE `Blacklist` (
	`id` BIGINT,
	PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8;

CREATE TABLE `Chats` (
	`id` BIGINT,
	`type` TEXT NOT NULL,
	`title` TEXT DEFAULT NULL,
	`username` VARCHAR(32) UNIQUE DEFAULT NULL,
	`invite_link` TEXT DEFAULT NULL,
	`welcome` LONGTEXT DEFAULT NULL,						/* i.e. "Hello ${mentions} welcome to this chat !\n\n(Rest of the message to be sent when a user join the chat)" */
	`staff_group` BIGINT DEFAULT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8;

CREATE TABLE `Languages` (
	`lang_code` VARCHARACTER(20),							/* i.e. "en" */
	`add_lang_message` LONGTEXT DEFAULT NULL,				/* i.e. "Send me a message with this format:\n\n<code>lang_code: &lt;insert here the lang_code of the language&gt;\nadd_lang_message: &lt;insert here the message for the /add command when a user want add a language&gt;\nadmin_message: &lt;insert here the message for the @admin tag&gt;\nconfirm_message: &lt;insert here a generic confirm message&gt;\nhelp_message: &lt;insert here the message for the /help command&gt;\ninvalid_parameter_message: &lt;insert here the message that will be sent when a user insert an invalid parameter into a command&gt;\ninvalid_syntax_message: &lt;insert here the message that will be sent when a user send a command with an invalid syntax&gt;\nmute_message: &lt;insert here the message for the /mute command&gt;\nmute_advert_message: &lt;insert here the message for when the /mute command is used with time set to forever&gt;\nlink_message: &lt;insert here the message for the /link command&gt;\nreject_message: &lt;insert here a generic reject message&gt;\nstaff_group_message: &lt;insert here the message for the /staff_group command&gt;\nstart_message: &lt;insert here the message for the /start command&gt;\nunknown_message: &lt;insert here the message for the unknown commands&gt;\nupdate_message: &lt;insert here the message for the /update command&gt;</code>\n\n<b>N.B.</b>: If you want insert a new line in the messages, you must codify it as <code>\n</code>." */
	`admin_message` LONGTEXT DEFAULT NULL,					/* i.e. "<a href=\"mention:${admin_id}\" >${admin_first_name}</a>,\n<a href=\"mention:${sender_id}\" >${sender_first_name}</a> needs your help${motive} into <a href=\"${chat_invite}\" >${chat_title}</a>." */
	`confirm_message` LONGTEXT DEFAULT NULL,				/* i.e. "Operation completed." */
	`help_message` LONGTEXT DEFAULT NULL,					/* i.e. "<b>FREQUENTLY ASKED QUESTION<\b>\n(FAQ list)\n\n<a href=\"(link to the manual, without brackets)\" >TELEGRAM GUIDE</a>\n\n<b>INLINE COMMANDS<\b>\n(Inline mode description)" */
	`invalid_parameter_message` LONGTEXT DEFAULT NULL,		/* i.e. "The ${parameter} is invalid." */
	`invalid_syntax_message` LONGTEXT DEFAULT NULL,			/* i.e. "The syntax of the command is: <code>${syntax}</code>." */
	`mute_message` LONGTEXT DEFAULT NULL,					/* i.e. "The syntax of the command is: <code>/mute [time]</code>.\nThe <code>time</code> option must be more then 30 seconds and less of 366 days." */
	`mute_advert_message` LONGTEXT DEFAULT NULL,			/* i.e. "You have muted <a href=\"mention:${sender_id}\" >${sender_first_name}</a> forever." */
	`link_message` LONGTEXT DEFAULT NULL,					/* i.e. "<a href=\"${invite_link}\" >This</a> is the invite link to this chat." */
	`reject_message` LONGTEXT DEFAULT NULL,					/* i.e. "Operation deleted." */
	`staff_group_message` LONGTEXT DEFAULT NULL,			/* i.e. "For what chats do you want set this staff group ?" */
	`start_message` LONGTEXT DEFAULT NULL,					/* i.e. "Hello <a href=\"mention:${sender_id}\" >${sender_first_name}</a>, welcome !\n\n(Rest of the message to be sent upon receipt of the start command)" */
	`unknown_message` LONGTEXT DEFAULT NULL,				/* i.e. "This command isn\'t supported." */
	`update_message` LONGTEXT DEFAULT NULL,					/* i.e. "Database updated." */
	PRIMARY KEY (`lang_code`)
) DEFAULT CHARACTER SET utf8;
