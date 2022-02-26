<?php
session_start(); // Si lancia la sezione

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore

require('funzioni_admin.php');
	/*
	// Si aggiunge primo pasto e ultimo pasto, one time operation
	$db = db_connect();
	$set_update = array(3, 1, 0);
	$sql = "UPDATE prenotazioni SET primo_pasto=?, ultimo_pasto=? WHERE id>=?";
	$request = $db->prepare($sql);
	$request->execute($set_update);
	$db->connection = NULL;
	*/
	// ONE TIME OP
	// inserimento_id_gruppi();
	// update_agenzie_gruppi();
	// assegna_colori_gruppo();

$azione = '';

// Se si è chiesto il backup
if(isset($_GET['backup'])) {
	$backup = backup_tables('*');
	
}

elseif(isset($_GET['colori_camere'])) {
	$db = db_connect();
	assegna_colori_camere($db);
	$db->connection = NULL;
	
	
	$azione = 'assegna_colori_camere';
}

elseif(isset($_POST['data_eliminazione'])) {
	$azione = 'conferma_el_pre';
	
	$data_el_pre = controllo_data($_POST['data_eliminazione']);
}

elseif(isset($_GET['conferma_eliminazione'])) {
	$azione = 'el_pre_confermata';

	$data_el_pre = intval($_GET['conferma_eliminazione']);
	
	$db = db_connect();
	$db->query('DELETE FROM prenotazioni WHERE data_partenza<' . $data_el_pre);
	$db->query('DELETE FROM gruppi WHERE data_partenza<' . $data_el_pre);
	$db->connection = NULL;
}

elseif(isset($_GET['show_cam'])) {
	$show_cam = intval($_GET['show_cam']);
	
	if($show_cam == 0) $_SESSION['show_cam'] = FALSE;
	else 					 $_SESSION['show_cam'] = TRUE;
}

if(!isset($_GET['backup'])) {
	// Si settano i valori standard per visualizzare il booking
	$mesi = array('GEN', 'FEB', 'MAR', 'APR', 'MAG', 'GIU', 'LUG', 'AGO', 'SET', 'OTT', 'NOV', 'DIC');
	$data_ora_oggi = time();
	
	// Si crea una variabile globale da riutilizzare nelle varie funzioni
	$_SESSION['oggi_giorno'] = date('d', $data_ora_oggi);
	$_SESSION['oggi_mese'] = date('n', $data_ora_oggi);
	$_SESSION['oggi_anno'] = date('Y', $data_ora_oggi);
	$_SESSION['oggi'] = mktime(0, 0, 0, $_SESSION['oggi_mese'], $_SESSION['oggi_giorno'], $_SESSION['oggi_anno']);
	$domani = strtotime('+1 day', $_SESSION['oggi']);
	
	
	//$data_oggi = mktime(0, 0, 0, 12, 17, 2016);
	
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head>
<title>MENU</title><?php
header_standard();
?><link rel="stylesheet" href="layout/menu.css" type="text/css" />
</head>
<body><?php
		$testo_menu_top = '';
		menu_top($testo_menu_top); ?>
	<div id="corpo_a"><?php

	if($azione === 'conferma_el_pre') {
		echo '<div class="form1_all form_green">';
		echo '<p class="titolo">CONFERMA ELIMINAZIONE</p>';
	
		echo '<div class="cont_op"><p class="form_txt">' //
		.'sei sicuro di voler eliminare tutte le prenotazioni precedenti al <b>'.date('d/m/Y', $data_el_pre).' definitivamente</b> ?</p></div>';
		
		echo '<div class="pulsanti_bottom">';
		echo '<a class="bottone bottone_r" style="display: block; float: right" href="menu.php?conferma_eliminazione='.$data_el_pre.'">SI</a>';
		echo '<a class="bottone" style="display: block; float: right" href="menu.php">NO</a>';
		echo '<p style="clear:both"></p>';
		echo '</div>';
		echo '</div>';
	}
	
	elseif($azione === 'el_pre_confermata') {
		echo '<div class="form1_all form_green">';
		echo '<p class="titolo">PRENOTAZIONI ELIMINATE CON SUCCESSO</p>';
		echo '</div>';
	}
	
	elseif($azione === 'assegna_colori_camere') {
		echo '<div class="form1_all form_green">';
		echo '<p class="titolo">COLORI CAMERE ASSEGNATI CON SUCCESSO</p>';
		echo '</div>';
	}

	// Calendario sinistra
	echo '<div class="cal_sx">';
		calendario_menu();
	echo '</div>';
	
	// Menu Container
	echo '<div class="menu_container">';
	
		echo '<p class="titolo_sez"><span>BOOKING</span></p>';
		// GUARDA IL BOOKING
		echo '<div class="menu">';
			echo '<form name="guarda_booking" action="prenotazioni.php" method="post" enctype="multipart/form-data">';
			echo '<div class="titolo_menu">GUARDA BOOKING';
			echo '<div class="show_cam">';
			if($_SESSION['show_cam'] == FALSE) echo '<a class="bottone" href="menu.php?show_cam=1">MOSTRA ASSEGNAZIONE CAMERE</a>';
			else 										  echo '<a class="bottone" href="menu.php?show_cam=0">NASCONDI ASSEGNAZIONE CAMERE</a>';
			echo '</div>';
			echo '</div>';
				 
			
			echo '<input type="text" name="data_arrivo" class="field campi_menu" placeholder="DAL" />';
			echo '<input type="text" name="data_partenza" class="field campi_menu" placeholder="AL" />';
			echo '<div class="pulsanti_bottom"><input type="submit" class="bottone" name="cerca" value="CERCA" /></div>';
			echo '<input type="hidden" name="guarda_booking" />';
			echo '</form>';
		echo '</div>';
	
		// CERCA PRENOTAZIONI
		echo '<div class="menu">';
			echo '<form name="cerca_prenotazioni" action="prenotazioni.php" method="post" enctype="multipart/form-data">';
			echo '<div class="titolo_menu">CERCA PRENOTAZIONI</div>';
			echo '<input type="text" name="nome" class="field campi_menu" placeholder="NOME" />';
			echo '<input type="text" name="camera" class="field campi_menu" placeholder="CAMERA" />';
			echo '<input type="text" name="gruppo" class="field campi_menu" placeholder="GRUPPO" />';
			echo '<input type="text" name="agenzia" class="field campi_menu" placeholder="AGENZIA" />';
			echo '<div class="separatore"></div>';
			echo '<input type="text" name="data_arrivo" class="field campi_menu" placeholder="DAL" />';
			echo '<input type="text" name="data_partenza" class="field campi_menu" placeholder="AL" />';


			echo '<p class="opzioni_menu"><input type="radio" name="tipo_data" id="r1" value="tra_le_date" checked="checked" />';
			echo '<label for="r1"> TRA DUE DATE</label>';
  			echo ' <input type="radio" name="tipo_data" id="r2" value="data_esatta" />';
  			echo '<label for="r2"> DATA ESATTA</label></p>';

			echo '<div class="pulsanti_bottom">';
				echo '<input type="submit" class="bottone" name="cerca" value="CERCA" />';
			echo '</div>';
			echo '<input type="hidden" name="cerca_prenotazioni" />';
			echo '</form>';
		echo '</div>';
		
		// GESTIONE PRENOTAZIONI
		echo '<div class="menu">';
			echo '<div class="titolo_menu">GESTIONE PRENOTAZIONI</div>';
			echo '<a href="ge_pre.php" class="link_menu">NUOVA PRENOTAZIONE</a>';
			echo '<a href="ge_gru.php" class="link_menu">NUOVO GRUPPO</a>';
			echo '<div class="separatore"></div>';
			echo '<a href="lista_pre.php" class="link_menu">LISTA PRENOTAZIONI</a>';
		echo '</div>';
		
	echo '</div>';
	
// Menu Container
	echo '<div class="menu_container">';
	
		echo '<p class="titolo_sez"><span>LISTE &amp; MOVIMENTI</span></p>';
		
		// MOVIMENTI
		echo '<div class="menu">';
			echo '<form name="elimina_pre" action="movimenti.php" method="post" enctype="multipart/form-data">';
			echo '<div class="titolo_menu">MOVIMENTI</div>';
			
			echo '<select class="opz_menu" name="tipo_lista">'
				 .'<option value="arrivi" selected="selected">VISUALIZZA ARRIVI</option>'
				 .'<option value="partenze">VISUALIZZA PARTENZE</option>'
				 .'<option value="in_casa">VISUALIZZA IN CASA</option>'
				 .'</select>';
			
			echo '<input type="text" name="data_arrivo" class="field campi_menu" placeholder="DAL"'
				  .'value="'.date('d/m/y', $domani).'" />';
			echo '<input type="text" name="data_partenza" class="field campi_menu" placeholder="(FACOLTATIVO) AL" />';
			echo '<input type="text" name="agenzia" class="field campi_menu" placeholder="AGENZIA" />';
			
			echo '<select class="opz_menu" name="ordine_lista">'
				 .'<option value="nome">ORDINA PER NOME</option>'
				 .'<option value="gruppo" selected="selected">ORDINA PER GRUPPI</option>'
				 .'<option value="camera">ORDINA PER CAMERA</option>'
				 .'</select>';
			
			echo '<div class="pulsanti_bottom">'
				 .'<input type="submit" class="bottone" name="cerca" value="VAI" />'
				 .'</div>';
			
			echo '</form>';
		echo '</div>';
		
		// STATISTICHE
		echo '<div class="menu">';
			echo '<form name="elimina_pre" action="statistiche.php" method="post" enctype="multipart/form-data">';
			echo '<div class="titolo_menu">STATISTICHE</div>';
			echo '<input type="text" name="data_arrivo" class="field campi_menu" placeholder="DAL" />';
			echo '<input type="text" name="data_partenza" class="field campi_menu" placeholder="AL" />';
			echo '<div class="separatore"></div>';
			
			echo '<div class="menu_checkbox">';
			echo '<input type="checkbox" name="presenze" id="presenze" value="presenze" checked="checked"><label for="presenze"> PRESENZE</label><br />';
			echo '<input type="checkbox" name="occupazione" id="occupazione" value="occupazione"><label for="occupazione"> OCCUPAZIONE</label><br />';
			echo '<input type="checkbox" name="cucina" id="cucina" value="cucina"><label for="cucina"> CUCINA</label>';
			echo '</div>';
			
			echo '<div class="pulsanti_bottom">';
			if($_SESSION['inizio_estate'] != 0 && $_SESSION['fine_estate'] != 0) echo '<input type="submit" class="bottone" name="estate" value="EST" />';
			if($_SESSION['inizio_inverno'] != 0 && $_SESSION['fine_inverno'] != 0) echo '<input type="submit" class="bottone" name="inverno" value="INV" />';
			echo '<input type="submit" class="bottone" name="cerca" value="VAI" />';
			echo '</div>';
			
			echo '</form>';
		echo '</div>';
		
	echo '</div>';
// Menu Container
	echo '<div class="menu_container">';
	
		echo '<p class="titolo_sez"><span>GESTIONE</span></p>';
		
		// CAMERE & TARIFFE
		echo '<div class="menu">';
			echo '<div class="titolo_menu">CAMERE &amp; TARIFFE</div>';
			echo '<a href="gestione_camere.php" class="link_menu">GESTIONE CAMERE</a>';
			echo '<a href="menu.php?colori_camere=1" class="link_menu">ASSEGNA COLORI CAMERE</a>';
			echo '<div class="separatore"></div>';
			echo '<a href="tariffe.php" class="link_menu">GESTIONE TARIFFE</a>';
			echo '<a href="gestione_tariffe.php" class="link_menu">AGGIUNGI TARIFFA</a>';
		echo '</div>';
		
		// STRAORDINARIE
		echo '<div class="menu">';
			echo '<form name="elimina_pre" action="menu.php" method="post" enctype="multipart/form-data">';
			echo '<div class="titolo_menu">STRAORDINARIE</div>';
			echo '<a href="menu.php?backup=1" class="link_menu">SCARICA BACKUP</a>';
			echo '<div class="separatore"></div>';
			echo '<p class="testo_menu">ELIMINA PRENOTAZIONI</p>';
			echo '<input type="text" name="data_eliminazione" class="field campi_menu" placeholder="CON PARTENZA PRIMA DEL XX/XX/XXXX" />';
			echo '<input type="submit" class="bottone bottone_r bottone_riga" name="cerca" value="ELIMINA" />';
			echo '</form>';
		echo '</div>';
		
	echo '</div>';
	
	
					/* Se mai inplementare tale funzione in lista_pre come campo ricerca
					<form name="dati" action="lista_modifiche.php" method="post" enctype="multipart/form-data">
					VISUALIZZA PRENOTAZIONI MODIFICATE O CREATE DOPO IL 
					<input type="text" name="data_modifiche" style="width: 80px" class="field" value="<?php echo $_SESSION['oggi_giorno'].'/'.$_SESSION['oggi_mese'].'/'.$_SESSION['oggi_anno'];?>" />
					ALLE 
					<input type="text" name="ora_modifiche" style="width: 50px" class="field" value="00:00" />
					<input class="bottone" type="submit" style="padding: 5px 10px" value="VAI" />
					</form>
					*/

	echo '</div></body></html>';


} // Fine dello skip per il backup
} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>