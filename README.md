# inginf_bot
 ![Icons](https://i.imgur.com/YLSSaGU.png)
[@inginf_bot](https://t.me/inginf_bot) s a Telegram bot created for the students of Computer Engineering @ Politecnico di Torino.

It contains links to the various groups related to the degree course (bachelor and master's), together with other useful resources. We have created this repository to share the bot's core code. You can reuse this code freely, **in compliance with the [GNU GPLv3 license](https://www.gnu.org/licenses/gpl-3.0.html)**. If you want to create a bot like this for your degree program, follow the guide below.

# Contents

* [File](#files)
	- [index.php](#index.php)
	- [How to install the dependencies](#how-to-install-the-dependencies)
* [Practical advice](#practical-advice)
* [Why Telegram ?](#why-telegram)

# File

Below is a description of the most important files you can find in this repo:

## index.php

This file contains the bot's source code; it is continuosly in update.

This bot was created through the [MadelineProto](https://docs.madelineproto.xyz/) library (v5.1.24), an async PHP client/server API for the Telegram MTProto protocol.

## How to install the dependencies

To install the dependencies locally, install [composer](https://nekobin.com/sagumirohe.bash) and use: `composer update`


# Practical advice

We recommend using an editor or IDE to work with PHP and JSON. [Atom](https://atom.io), for example, is an advanced text editor with many plugins, including the management of git projects and the direct connection with GitHub: set correctly, each time it is saved it automatically loads the file on the GitHub repo. There are a thousand more or less advanced editors, you can even avoid using it and rely on the built-in GitHub one, but if you want to work locally it's better to equip yourself with something better than a simple notepad ! :wink:

# Why Telegram ?

We chose to create the various groups and the bot on Telegram because we think it is an interesting and constantly developing platform, definitely suitable for the purpose. It is an instant messaging service and is used by it, but you can go even further: the bot we have created is an example. We do not deny that in the future other tools can be integrated with Telegram, but we believe that it is still the fastest and most complete system for communication between students.
