<?php
//30-11-2018
//started on 01-06-2017
// La app di Heroku si puo richiamare da browser con
//			https://cucinazie.herokuapp.com/


/*API key = 449299215:AAGntMAYriIqXHhh1bMNXa9m42oyNYj3FhU

da browser request ->   https://cucinazie.herokuapp.com/register.php
           answer  <-   {"ok":true,"result":true,"description":"Webhook is already set"}
In questo modo invocheremo lo script register.php che ha lo scopo di comunicare a Telegram
l’indirizzo dell’applicazione web che risponderà alle richieste del bot.

da browser request ->   https://api.telegram.org/bot449299215:AAGntMAYriIqXHhh1bMNXa9m42oyNYj3FhU/getMe
           answer  <-   {"ok":true,"result":{"id":449299215,"is_bot":true,"first_name":"cucinazie","username":"cucinazie_bot"}}

riferimenti:
https://gist.github.com/salvatorecordiano/2fd5f4ece35e75ab29b49316e6b6a273
https://www.salvatorecordiano.it/creare-un-bot-telegram-guida-passo-passo/
*/
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if(!$update)
{
  exit;
}

$message = isset($update['message']) ? $update['message'] : "";
$messageId = isset($message['message_id']) ? $message['message_id'] : "";
$chatId = isset($message['chat']['id']) ? $message['chat']['id'] : "";
$firstname = isset($message['chat']['first_name']) ? $message['chat']['first_name'] : "";
$lastname = isset($message['chat']['last_name']) ? $message['chat']['last_name'] : "";
$username = isset($message['chat']['username']) ? $message['chat']['username'] : "";
$date = isset($message['date']) ? $message['date'] : "";
$text = isset($message['text']) ? $message['text'] : "";

// pulisco il messaggio ricevuto togliendo eventuali spazi prima e dopo il testo
$text = trim($text);
// converto tutti i caratteri alfanumerici del messaggio in minuscolo
$text = strtolower($text);

header("Content-Type: application/json");

//ATTENZIONE!... Tutti i testi e i COMANDI contengono SOLO lettere minuscole
$response = '';
$helptext = "List of commands : 
/on_on    -> Faretti ON  Veranda ON 
/Ion_Eoff -> Faretti ON  Veranda OFF  
/Ioff_Eon -> Faretii OFF Veranda ON
/off_off -> spenti
/cucina  -> Lettura stazione3 ... su bus RS485
";

if(strpos($text, "/start") === 0 || $text=="ciao" || $text == "help"){
	$response = "Ciao $firstname, benvenuto   \n". $helptext; 
}

//<-- Comandi ai rele
elseif(strpos($text,"on_on")){
	$response = file_get_contents("http://dario95.ddns.net:8083/rele/2/3");
}
elseif(strpos($text,"lon_toff")){
	$response = file_get_contents("http://dario95.ddns.net:8083/rele/2/2");
}
elseif(strpos($text,"loff_ton")){
	$response = file_get_contents("http://dario95.ddns.net:8083/rele/2/1");
}
elseif(strpos($text,"off_off")){
	$response = file_get_contents("http://dario95.ddns.net:8083/rele/2/0");
}
//<-- Lettura parametri slave5
elseif($text=="/pranzo"){
	$response = file_get_contents("http://dario95.ddns.net:8083/pranzo");
}

//<-- Manda a video la risposta completa
elseif($text=="/verbose"){
	$response = "chatId ".$chatId. "   messId ".$messageId. "  user ".$username. "   lastname ".$lastname. "   firstname ".$firstname. "\n". $helptext ;	
	$response = $response. "\n\n Heroku + dropbox gmail.com";
}


else
{
	$response = "Unknown command!";			//<---Capita quando i comandi contengono lettere maiuscole
}

// la mia risposta è un array JSON composto da chat_id, text, method
// chat_id mi consente di rispondere allo specifico utente che ha scritto al bot
// text è il testo della risposta
$parameters = array('chat_id' => $chatId, "text" => $response);
$parameters["method"] = "sendMessage";
// Gli EMOTICON sono a:     http://www.charbase.com/block/miscellaneous-symbols-and-pictographs
//													https://unicode.org/emoji/charts/full-emoji-list.html
//													https://apps.timwhitlock.info/emoji/tables/unicode
$parameters["reply_markup"] = '{ "keyboard": [["/on_on \ud83d\udd34", "/lon_toff \ud83d\udd06"],["/loff_ton \ud83c\udfa6", "/off_off \ud83d\udd35"],["/pranzo"]], "one_time_keyboard": false,  "resize_keyboard": true}';
// converto e stampo l'array JSON sulla response
echo json_encode($parameters);
?>