# @inginf_bot

[@inginf_bot](https://politoinginf.000webhostapp.com) è un bot Telegram creato per gli studenti di Ingegneria Informatica del Politecnico di Torino.

Contiene i link ai vari gruppi relativi al corso di laurea (triennale e magistrale), insieme ad altre risorse utili.
Ho creato questa repository per condividere il codice *core* del bot. Potete riutilizzare questo codice liberamente, **nel rispetto della [licenza GNU GPLv3](https://www.gnu.org/licenses/gpl-3.0.html)**.
Se volete creare un bot come questo per il vostro corso di laurea, seguite la guida che trovate sotto.

# Contenuti

- [File](#files)
	- [index.php](#index.php)
	- [database.json](#database.json)
		- [Come funziona](#come-funziona) 
- [Creare un bot simile a questo](#creare-un-bot-simile-a-questo)
	- [Cosa serve](#cosa-serve)
	- [Creazione del bot](#creazione-del-bot)
	- [Consigli pratici](#consigli-pratici)
- [Perché Telegram](#perché-telegram)

# Files

Di seguito, una descrizione dei file che potete trovare in questa repo:

## index.php

Questo file contiene il codice sorgente del bot. Verrà opportunamente commentato e modificato in futuro.

## database.json

Questo è forse il file più importante, perché conterrà tutti i link, i nomi (e, se esistono, i codici) dei vari gruppi/canali presenti nel bot. Dovrete ovviamente mantenere la struttura inalterata e riempirlo con i vostri link.

### Come funziona

**N.B.**: vista la lunghezza del file, consiglio l'utilizzo di un editor/IDE o di un visualizzatore di JSON online, come [questo](https://jsonformatter.curiousconcept.com/), che permette di nascondere/espandere intere sezioni senza fatica.

L'interazione con l'utente avviene tramite dei "bottoni", chiamati su Telegram *Inline Keyboard*, che chiamerò **tastiere**. La creazione di queste tastiere avviene nella prima parte del file, dentro `"keyboard"`.
I vari link sono presenti negli altri **array** allo stesso livello di indentazione (`"corsi"`,`"gen_mag"`,`"altro"` e `"temp"`). Non è necessario creare più array, è solo una questione di leggibilità.

Nelle tastiere, ogni elemento ha un **tipo**, definito da `"type"`, che può essere:

- `"dir"`: indica una "directory". La principale è chiamata **root** e non deve essere modificata. Una directory contiene altre tastiere.
Ha i seguenti attributi:

	- `"name"`: indica il nome da visualizzare nella keyboard
	- `"list"`: elenca gli elementi presenti all'interno della keyboard, che possono a loro volta essere altre dir. Rispettare la sintassi del JSON!
	- `"frow"`(opzionale): è un valore booleano che sta per "full row". Se viene settato a 1, quel bottone sarà su un'intera riga, altrimenti le tastiere vengono create con un layout automatico di 2 bottoni per riga.
	- `"pags"`: è un valore intero che indica il numero di elementi per "pagina". Se la dir contiene più elementi di quelli indicati da pags, verrà creata una nuova pagina e i bottoni per scorrere tra le pagine. Può essere settato a 0 se non si vogliono avere limiti
 
- `"intdir"`: è una directory le cui tastiere vengono create dinamicamente. Fa riferimento a una dir presente dentro un array. Per esempio, c'è una intdir dentro Triennale>Primo Anno che fa riferimento alla dir `"cliberil1"` dentro l'array `"corsi"` (gli attributi sono presenti nella dir, la intdir serve solo a fare riferimento alla dir chiamata). Serve se si vuole creare un "contenitore" di link da poter piazzare più volte dentro `"keyboard"`: basta richiamare la intdir, invece che copiare e incollare la dir n volte.
Ha i seguenti attributi:

	- `"array"`: indica l'array in cui è contenuta la dir a cui fa riferimento
	- `"link"`: indica la dir a cui fa riferimento

	Mancano gli attributi pags e frow perché, facendo riferimento a una dir, sono presenti nella dichiarazione della dir nell'array.
	
- `"intlink"`: chiamato così per differenziarlo da `"link"`, è il tipo "terminale", che indica banalmente un link.
Ha i seguenti attributi:
	
	- `"array"`: indica l'array in cui è contenuto il link a cui fa riferimento
	- `"link"`: indica il link a cui fa riferimento
	- `"frow"`(opzionale): è un valore booleano che sta per "full row". Se viene settato a 1, quel bottone sarà su un'intera riga, altrimenti le tastiere vengono create con un layout automatico di 2 bottoni per riga.

# Creare un bot simile a questo

Prima di cominciare, è necessario avere un server su cui ospitare (hostare) il vostro bot. Esistono molti servizi di hosting gratuiti: se state iniziando, consiglio Heroku.
Questa guida fa riferimento alla creazione di un bot con Heroku e GitHub.

## Cosa serve

Vi serve un **token**: create un bot su Telegram tramite [BotFather](https://t.me/botfather).

Create ora un account su Heroku e un'applicazione. Il nome che darete all'applicazione servirà in futuro per fare riferimento al bot, ma non deve essere necessariamente lo stesso che avete scelto durante la creazione del bot tramite BotFather.

Una volta creata l'applicazione, andate su Settings>Config Vars e fate click su Reveal Config Vars. Aggiungete una cvar con nome `TOKEN` e come valore il token rilasciato da BotFather.

Nel file `index.php`, modificate la riga:

> `define('token', "YOUR_TOKEN_HERE");`

in questo modo:

> `define('token', getenv('TOKEN'));`

Il token NON deve comparire direttamente nel vostro codice se la repository che conterrà i file è pubblica! Chi entra in possesso del token può manipolare il bot a vostra insaputa, e, con esso, anche i dati degli utenti che lo utilizzano. Proprio per questo, invece che scrivere il token "in chiaro" sul main, lo dichiariamo come variabile d'ambiente, "protetta" dall'esterno da Heroku.

Create un account e una repository su [GitHub](https://github.com), al cui interno caricherete (almeno) i due file `index.php` e `database.json`. Nella vostra applicazione su Heroku, andate su Deploy e in Deployment Method selezionate GitHub e indicate un branch della repo da cui "pescare" i file. Potete abilitare i deploy automatici a ogni commit sul branch selezionato, cosa abbastanza comoda, altrimenti dovreste fare il deploy manuale ogni volta tramite Heroku, nella sezione Deploy.

## Creazione del bot

Ora che avete tutto pronto, è necessario "costruire" un link come questo:

> `https://api.telegram.org/bot<TOKEN>/setwebhook?url=https://<NOME_APP>.herokuapp.com/<NOME_FILE_PHP>`

dove dovrete inserire:
- al posto di `<TOKEN>` il token del vostro bot
- al posto di `<NOME_APP>` il nome che avete dato all'applicazione su Heroku (oltre all'account dovrete creare anche una nuova applicazione, roba da 2 minuti)
- al posto di `<NOME_FILE_PHP>` il nome del file "principale" del vostro progetto, nel caso di questa repository sarebbe `index.php`. Un link di esempio sarebbe:

> `https://api.telegram.org/bot1234567890qwertyuiopasdfghjkl/setwebhook?url=https://ingelt.herokuapp.com/index.php`

Ora incollate il vostro link su un browser e premete Enter. Se tutto è andato bene, dovreste visualizzare il seguente messaggio:

> `{"ok":true,"result":true,"description":"Webhook was set"}`

Avete settato il cosiddetto webhook, che collega il vostro bot (tramite il token) all'applicazione che gira su Heroku. Quando scrivete qualcosa al bot, fornite varie informazioni ad esso (come il vostro ID Telegram), che servirà per ricevere una risposta. La prima parte del codice svolge proprio la funzione di "traduzione" di richieste sotto forma di URL in variabili utilizzate nel file PHP.

## Consigli pratici

Consiglio di utilizzare un editor o un IDE per lavorare con PHP e i JSON. [Atom](https://atom.io), ad esempio, è un editor di testi avanzato con moltissimi plugin, tra cui la gestione di progetti git e il collegamento diretto con GitHub: settato correttamente, a ogni salvataggio carica in automatico il file sulla repo di GitHub e, se avete abilitato i deploy automatici su Heroku, fa partire l'applicazione con il codice aggiornato. Esistono mille editor più o meno avanzati, potete addirittura evitare di utilizzarlo e affidarvi a quello built-in di GitHub, ma se volete lavorare in locale è meglio attrezzarsi di qualcosa di meglio del semplice blocco note! :wink:

La velocità di Heroku non è il massimo, così come non lo è con altri servizi di hosting gratuito. Ci sono alternative gratuite o a poco prezzo, ma mi sento di consigliare Heroku perché ha molte comodità, tra cui l'utilizzo tramite command line (`heroku-cli`, cross-platform) e la visualizzazione di log, strumento utilissimo per capire cosa non va nel codice.

# Perché Telegram?

Ho scelto di creare i vari gruppi e il bot su Telegram perché la ritengo una piattaforma interessante e in costante sviluppo, decisamente adatta allo scopo. È un servizio di instant messaging e da tale viene utilizzato, ma si può spingere anche oltre: il bot che ho creato ne è un esempio. Non nego che in futuro si possano integrare a Telegram altri strumenti, ma credo che sia comunque il sistema più veloce e completo per la comunicazione tra studenti.
