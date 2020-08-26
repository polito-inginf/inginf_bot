# inginf_bot
 ![Icons](https://i.imgur.com/YLSSaGU.png)
[@inginf_bot](https://t.me/inginf_bot) s a Telegram bot created for the students of Computer Engineering @ Politecnico di Torino.

It contains links to the various groups related to the degree course (bachelor and master's), together with other useful resources. We have created this repository to share the bot's core code. You can reuse this code freely, **in compliance with the [GNU GPLv3 license](https://www.gnu.org/licenses/gpl-3.0.html)**. If you want to create a bot like this for your degree program, follow the guide below.

# Contents

* [File](#files)
	- [index.php](#index.php)
	- [database.json](#database.json)
		+ [How does it work](#how-does-it-work)
		+ [Add a language to the bot](#add-a-language-to-the-bot)
* [Create a bot like this](#create-a-bot-like-this)
	- [What is needed](#what-is-needed)
	- [Practical advice](#practical-advice)
* [Why Telegram ?](#why-telegram)

# File

Below is a description of the most important files you can find in this repo:

## index.php

This file contains the bot's source code; it is continuosly in update.

This bot was created through the [MadelineProto](https://docs.madelineproto.xyz/) library (v5.1.24), an async PHP client/server API for the Telegram MTProto protocol.

## database.json

This is, perhaps, the absolute most important file, because it will contain all the links, the names and, if any, the codes of the various groups/channels present in the bot. You will, obviously, have to keep the structure unchanged and fill it with your links.

### How does it work

**N.B.**: Given the length of the file, we recommend using an editor/IDE or an online JSON viewer, like [this one](https://jsonformatter.curiousconcept.com/), which allows you to hide/expand entire sections without effort.

The interaction with the user takes place via "buttons", called on Telegram _Inline Keyboard_, which we will call **keyboards**. The creation of these keyboards occurs in the first part of the file, inside `"keyboard"`. The various links are present in the other **arrays** at the same level of indentation (`"courses"`, `"general_master"`, `"commands"` and `"other"`). There is no need to create multiple arrays, it's just a matter of readability.

In keyboards, each element has a **type**, defined by `"type"`, which can be:
* `"dir"`: indicates a "directory". The main one is called **root** and must not be changed. A directory contains other keyboards. It has the following attributes:
	- `"name"`: indicates the name to be displayed in the keyboard.
	- `"list"`: it lists the elements present inside the keyboard, which can in turn be other dir. Respect the JSON syntax !
	- `"frow"`(optional): is a boolean value that stands for "full row". If set to 1, that button will be on an entire row, otherwise keyboards are created with an automatic layout of 2 buttons per row.
	- `"pags"`: is an integer value indicating the number of items per "page". If the dir contains more elements than indicated by pags, a new page will be created and buttons to scroll through the pages. It can be set to 0 if you don't want to have limits.
* `"intdir"`: is a directory whose keyboards are dynamically created. Refers to a dir inside an array (the attributes are present in the dir, the intdir is only used to refer to the called dir). It is needed if you want to create a link "container" that you can place several times inside `"keyboard"`: just call the intdir, instead of copying and pasting the dir n times. It has the following attributes:
	- `"array"`: indicates the array in which the dir it refers to is contained.
	- `"link"`: indicates the dir to which it refers.

	The pags and frow attributes are missing because, referring to a dir, they are present in the declaration of the dir in the array.

* `"intlink"`: so called to differentiate it from `"link"`, it is the "terminal" type, which simply indicates a link. It has the following attributes:
	- `"array"`: indicates the array containing the link to which it refers.
	- `"link"`: indicates the link to which it refers.
	- `"frow"`(optional): is a boolean value that stands for "full row". If set to 1, that button will be on an entire row, otherwise keyboards are created with an automatic layout of 2 buttons per row.

### Add a language to the bot

If you want to add support for another language, you need to copy the path of an existing language (_i.e._ `"it"` or `"en"`) and translate the `"name"` parameter of the `"dir"` elements (_i.e._ First year, Bachelor degree, etc.).

**N.B.**: The other sections (`"courses"`, `"general_master"`, `"commands"` and `"other"`) and the other parameters must not be changed, because they are setted, by default, with all the groups existing in all the languages and the other parameters are a reference to them.

# Create a bot like this

Before you start, you need to have a server on which to host (host) your bot. There are many free hosting services - if you're starting out, we recommend Heroku. This guide refers to creating a bot with Heroku and GitHub.

## What is needed

You need a **token**: create a bot on Telegram via [BotFather](https://t.me/botfather).

Now create an account on Heroku and an application (the name you give the application does not necessarily have to be the same name you chose when creating the bot using BotFather).

Once the application is created, go to Settings > Config Vars and click on Reveal Config Vars. Add a cvar with name `BOT_TOKEN` and as value the token issued by BotFather.

The token must NOT appear directly in your code if the repository that will contain the files is public ! Whoever comes into possession of the token can manipulate the bot without your knowledge, and with it, also the data of the users who use it. Precisely for this reason, instead of writing the token "in clear" on the main, we declare it as an environment variable, "protected" from the outside by Heroku.

Create an account and a repository on [GitHub](https://github.com), inside which you will load (at least) the two files `index.php` and `database.json`. In your Heroku application, go to Deploy and in Deployment Method select GitHub and indicate a branch of the repo from which to "fetch" files. You can enable automatic deployments at each commit on the selected branch, which is quite convenient, otherwise you would have to manually deploy each time through Heroku, in the Deploy section.

## Practical advice

We recommend using an editor or IDE to work with PHP and JSON. [Atom](https://atom.io), for example, is an advanced text editor with many plugins, including the management of git projects and the direct connection with GitHub: set correctly, each time it is saved it automatically loads the file on the GitHub repo and, if you have enabled the automatic deploy on Heroku, starts the application with the updated code. There are a thousand more or less advanced editors, you can even avoid using it and rely on the built-in GitHub one, but if you want to work locally it's better to equip yourself with something better than a simple notepad ! :wink:

Heroku's speed isn't the best, nor is it with other free hosting services. There are free or cheap alternatives, but we would recommend Heroku because it has many comforts, including use via command line (`heroku-cli`, cross-platform) and log viewing, a very useful tool to understand what is wrong with the code.

# Why Telegram ?

We chose to create the various groups and the bot on Telegram because we think it is an interesting and constantly developing platform, definitely suitable for the purpose. It is an instant messaging service and is used by it, but you can go even further: the bot we have created is an example. We do not deny that in the future other tools can be integrated with Telegram, but we believe that it is still the fastest and most complete system for communication between students.
