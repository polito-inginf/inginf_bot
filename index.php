<?php
include "constants.php";
include "functions.php"; // Contiene tutte le funzioni comuni
include "header.php"; // Contiene tutta la gestione delle requests e il gestore delle ricerche inline
include "admintools.php"; // Contiene tutte le cose gestibili dagli admin (compresi i report)
if (substr($text, 0, 1) === '/') generateMessage(explode(' ',strtolower(trim($text)))[0]); //Se è un comando, processalo
exit(0);
?>
