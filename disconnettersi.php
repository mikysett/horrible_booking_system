<?php
session_start(); // Si lancia la sezione
ini_set('display_errors', 'Off');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore
 	$_SESSION = array();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it" >
	<head>
		<title>Booking - Disconnessione</title>
		<link rel="icon" href="../layout/favicon.png" type="image/png" />
		<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
		<meta http-equiv="content-language" content="it" />
		<link rel="stylesheet" href="layout/identification.css" type="text/css" />
		<meta name="author" content="michele" />
		<meta name="copyright" content="Mascheranova" />
		<meta name="robots" content="none" />
	</head>
	<body>

		<div id="head">
			<h1>Booking</h1>
			<p id="admin">Amministratore</p>
			<p>HOTEL NAME HERE</p>
		</div>
	
    	<div id="login">

	    	<p class="messageOk">Disconnessione effettuata con successo</p>
	    	<br />
	    	
	    	<form action="index.php" method="post">
				<fieldset>
					<legend>Riconnettersi</legend>
					<p class="infoChamps"><label for="pseudo">&nbsp;&nbsp;&nbsp;&nbsp;Nome : </label><input type="text" name="pseudo" id="pseudo" /></p>
					<p class="infoChamps"><label for="password">Password : </label><input type="password" name="pass" id="password" /></p>
					<br />
					<p><input class="bottone" type="submit" value="IDENTIFICARSI" /></p>
				</fieldset>
			</form>
			
		</div>
		
	</body>
</html>
<?php
}

else { // Se non si è identificati si rimanda alla dovuta pagina d'identificazione
	header('Location: index.php');
}
?>