<?php
session_start(); // Si lancia la sezione
ini_set('display_errors', 'On');
/* STORICO VERSIONI
16/05/16 - v. 0.0.3 CRAZY ROCKET
NUOVO MOTORE GRAFICO PER BOOKING

17/05/16 - v. 0.0.4 STYLISH PUB
MIGLIORIE GRAFICHE E FUNZIONALI IN MOTORE BOOKING

21/06/16 v. 0.0.5 AMBROGIO
AGGIUNTA FUNZIONI DI BOOKING AVANZATE: SHARE, PRENOTAZIONI SU PI� CAMERE, CAMBIO CAMERA
AGGIUNTA STAMPA ROOMING LIST E STATISTICHE
MIGLIORATA ERGONOMIA GENERALE

v 0.0.6 SHINDLER
INTEGRAZIONE LISTE ARRIVI/PARTENZE/IN CASA PER GOVERNANTE E MOVIMENTI GIORNALIERI

OTTOBRE 2016 - v 0.0.7 ZEN BOND
MIGLIORAMENTI GRAFICI MOTORE DI BOOKING, CORREZZIONE ERRORI GESTIONE GRUPPI

05/03/2017 - v 0.0.8 MOSE
MIGLIORAMENTI PAGINA STATISTICHE

30/09/2017 - v 0.0.9 MORPHEUS
MIGLIORAMENTI PAGINA STATISTICHE
AGGIUNTA POSSIBILIT� DI SCENTA TRA MOSTRARE/NASCONDERE L'ASSEGNAZIONE CAMERE (SOLO APPARTAMENTI RESTANO ASSEGNATI)
*/

$adminName = "admin";
$adminPass = "password";

// ERROR CODES:
// -1 = Admin correctly identified
//  1 = Username or password not provided
//  3 = Incorrect username or password
$erreur = 0;

if(isset($_POST['pseudo']) && isset($_POST['pass'])) { // Si les deux variables existent => l'utilisateur à appuyé sur le bouton
	$pseudo = htmlspecialchars($_POST['pseudo']);
	$pass = htmlspecialchars($_POST['pass']);
	
	if($pseudo == '' || $pass == '') $erreur = 1; // Si l'une des deux variables est vide on dit = "c'est pas bon"
	
	elseif($pseudo != $adminName || $pass != $adminPass) $erreur = 3; // Si mot de passe ou pseudo incorrects
	
	else { // Tout s'est bien passé, on renvoie l'admin à la page qu'il a choisie
		$_SESSION['pseudo'] = $pseudo;
		$_SESSION['pass'] = $pass;

		// Inizializziamo i valori di default
		require('funzioni_admin.php');
		valori_default();

		header('Location: menu.php');
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it" >
<head>
<title>Identificazione Amministratore</title>
<link rel="icon" href="../layout/favicon.png" type="image/png" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="content-language" content="it" />
<link rel="stylesheet" href="layout/identification.css" type="text/css" />
<meta name="author" content="michele" />
<meta name="copyright" content="Michele Sessa" />
<meta name="robots" content="none" />
</head>
<body>

<div id="head">
<h1>Booking</h1>
<p id="admin">MORPHEUS v. 0.0.9</p>
<p>HOTEL NAME HERE</p>
</div>

<div id="login">
 	
<form action="index.php" method="post">
<fieldset>
<legend>Identificarsi</legend>
<?php if($erreur == 1) { ?><p class="messageBad">Tutti i campi sono obligatori</p> <?php }
elseif($erreur == 3) { ?><p class="messageBad">Password o nome incorretti</p> <?php } ?>
<p class="infoChamps"><label for="pseudo">&nbsp;&nbsp;&nbsp;&nbsp;Nome : </label><input type="text" name="pseudo" id="pseudo" autofocus /></p>
<p class="infoChamps"><label for="password">Password : </label><input type="password" name="pass" id="password" /></p>
<br />
<p><input class="bottone" type="submit" value="IDENTIFICARSI" /></p>
</fieldset>
</form>

</div>

</body>
</html>