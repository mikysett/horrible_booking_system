<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore

	$erreur = 0;
	$azione = '';
	$vuoto = NULL;
	
	// valori standard
	$camera_sel = 0;
	
	$numero 					= '';
	$piano 					= '';
	$ubicazione				= '';
	$vestizione_max	 	= '';
	$pax_max	 				= '';
	$descrizione_breve 	= '';
	$note 					= '';
	$voto 					= '';
	
	
	//se si tratta di eliminare si chiede conferma
	if(isset($_GET['elimina'])) {
		$camera_sel = intval($_GET['elimina']);
		
		for($i = 0 ; $i < $_SESSION['num_cam'] ; $i++) {
			if($_SESSION['cam'][$i]['id'] == $camera_sel) {
				$numeroCameraEl = $_SESSION['cam'][$i]['numero'];
				break;
			}
		}
		
		$azione = 'ASK TO DELETE ROOM';
	}
	
	//se si tratta di eliminare e si é confermato si elimina
	elseif(isset($_POST['conferma'])) {
		$camera_sel = intval($_POST['conferma']);
		
		// Si prende il numero della camera	
		for($i = 0 ; $i < $_SESSION['num_cam'] ; $i++) {
			if($_SESSION['cam'][$i]['id'] == $camera_sel) {
				$numeroCameraEl = $_SESSION['cam'][$i]['numero'];
				break;
			}
		}
		
		// Si elimina la camera
		$db = db_connect();
		$db->query('DELETE FROM camere WHERE id=' . $camera_sel);

		// Si da la camera numero 0 alle prenotazioni con la camera eliminata	
		$set_update = 'camera=?';
		$array_update = array('0', $numeroCameraEl);

		// Si modifica l'elemento nel db
		$sql = 'UPDATE prenotazioni SET ' . $set_update . ' WHERE camera=?';
		$request = $db->prepare($sql);
		$request->execute($array_update);
		$db->connection = NULL;

		$azione = 'ROOM DELETED';
	}

	// Se abbiamo chiesto i dati per modificare una camera
	elseif(isset($_GET['id_modifica'])) {
		$camera_sel = intval($_GET['id_modifica']);
		
		for($i = 0 ; $i < $_SESSION['num_cam'] ; $i++) {
			if($_SESSION['cam'][$i]['id'] == $camera_sel) {
				$numero 				 = $_SESSION['cam'][$i]['numero'];
				$piano 				 = $_SESSION['cam'][$i]['piano'];
				$ubicazione			 = formatta_visualizzazione($_SESSION['cam'][$i]['ubicazione']);
				$vestizione_max 	 = $_SESSION['cam'][$i]['vestizione_max'];
				$pax_max 			 = $_SESSION['cam'][$i]['pax_max'];
				$descrizione_breve = formatta_visualizzazione($_SESSION['cam'][$i]['descrizione_breve']);
				$note					 = formatta_visualizzazione($_SESSION['cam'][$i]['note']);
				$voto					 = $_SESSION['cam'][$i]['voto'];
				break;
			}
		}
	}

	// Se abbiamo modificato una camera
	elseif(isset($_POST['modifica'])) {
		$camera_sel = intval($_POST['modifica']);
		
		// Si prende il numero vecchio della camera
		for($i = 0 ; $i < $_SESSION['num_cam'] ; $i++) {
			if($_SESSION['cam'][$i]['id'] == $camera_sel) {
				$numero_vecchio = $_SESSION['cam'][$i]['numero'];
				break;
			}
		}
		
		// Si inizializzano le variabili
		$numero 					= formatta_salvataggio($_POST['numero']);
		$piano 					= intval($_POST['piano']);
		$ubicazione 			= formatta_salvataggio($_POST['ubicazione']);
		$vestizione_max	 	= formatta_salvataggio($_POST['vestizione_max']);
		$pax_max				 	= intval($_POST['pax_max']);
		$descrizione_breve 	= formatta_salvataggio($_POST['descrizione_breve']);
		$note 					= formatta_salvataggio($_POST['note']);
		$voto 					= floatval($_POST['voto']);

		$set_update = 'numero=?, piano=?, ubicazione=?, vestizione_max=?, pax_max=?, descrizione_breve=?, note=?, voto=?';
		$array_update = array($numero, $piano, $ubicazione, $vestizione_max, $pax_max, $descrizione_breve, $note, $voto, $camera_sel);

		
		// Si modifica l'elemento nel db
		$db = db_connect();
		$sql = 'UPDATE camere SET '.$set_update.' WHERE id=?';
		$request = $db->prepare($sql);
		$request->execute($array_update);
		
		// Si modificano anche le prenotazioni inerenti a quella camera
		$set_update = 'camera=?';
		$array_update = array($numero, $numero_vecchio);

		$sql = 'UPDATE prenotazioni SET ' . $set_update . ' where camera=?';
		$request = $db->prepare($sql);
		$request->execute($array_update);
		$db->connection = NULL;
		
		// si riazzerano i valori per poter inserire subito un'altra camera
		$numero 					= '';
		$piano 					= '';
		$ubicazione 			= '';
		$vestizione_max	 	= '';
		$pax_max				 	= '';
		$descrizione_breve 	= '';
		$note 					= '';
		$voto 					= '';

		$azione = 'ROOM MODIFIED';
	}

	// Se abbiamo creato una camera
	elseif(isset($_POST['crea'])) {
		$numero 					= intval($_POST['numero']);
		$piano 					= intval($_POST['piano']);
		$ubicazione 			= formatta_salvataggio($_POST['ubicazione']);
		$vestizione_max	 	= formatta_salvataggio($_POST['vestizione_max']);
		$pax_max				 	= intval($_POST['pax_max']);
		$descrizione_breve 	= formatta_salvataggio($_POST['descrizione_breve']);
		$note 					= formatta_salvataggio($_POST['note']);
		$voto 					= floatval($_POST['voto']);
		
		$db = db_connect();
		$sql = $db->prepare('INSERT INTO camere
					(numero,piano,ubicazione,vestizione_max,pax_max,descrizione_breve,note,voto)
			VALUES(:numero,:piano,:ubicazione,:vestizione_max,:pax_max,:descrizione_breve,:note,:voto)');
		$request = $sql->execute(array(
			':numero' => $numero,
			':piano' => $piano,
			':ubicazione' => $ubicazione,
			':vestizione_max' => $vestizione_max,
			':pax_max' => $pax_max,
			':descrizione_breve' => $descrizione_breve,
			':note' => $note,
			':voto' => $voto));
		$db->connection = NULL;

		// Si azzerano i valori per poter inserire subito una nuova camera
		$numero 					= '';
		$piano 					= '';
		$ubicazione 			= '';
		$vestizione_max	 	= '';
		$pax_max				 	= '';
		$descrizione_breve 	= '';
		$note 					= '';
		$voto 					= '';

		$azione = 'ROOM ADDED';
	}
	// Se sono state apportate modifiche aggiorniamo l'array con le camere
	if($azione != '' && $azione != 'ASK TO DELETE ROOM') {
		$db = db_connect();
		$risposta_cam = $db->query('SELECT * FROM camere WHERE numero > 0 ORDER BY numero ASC');
		$db->connection = NULL;
		
		$_SESSION['cam'] = $risposta_cam->fetchAll(PDO::FETCH_ASSOC);
		$_SESSION['num_cam'] = count($_SESSION['cam']);
	}
	
	
	$riepilogo_cam = camere_disponibili($vuoto, $vuoto, $_SESSION['cam']);
	$print_cam = '';
	$piano_cam = 0;
	$piano_pax = 0;
	
	
	// Stampiamo le camere disponibili
	$num_righe = count($riepilogo_cam) - 1; // Una riga è per i totali
		
	$print_cam .= '<div class="liste_sx">';
	
	$print_cam .= '<table class="lista_sx">';
	$print_cam .= '<tr class="testa"><th class="titolo_lista_sx" colspan="3">RIEPILOGO CAMERE</th></tr>';
	
	for($i = 0 ; $i < $num_righe ; $i++) {
		if($i == 0)
			$print_cam .= '<tr class="testa"><th class="titolo_lista_sx">'.$riepilogo_cam[$i]["piano"].' PIANO</th><th>NUM</th><th>PAX</th></tr>';
		
		elseif($riepilogo_cam[$i-1]["piano"] != $riepilogo_cam[$i]["piano"]) {
			// Stampiamo i subtotali del piano
			$print_cam .= '<tr class="riga"><td>TOTIALI PIANO</td><td>'.$piano_cam.'</td><td>'.$piano_pax.'</td></tr>';
			$print_cam .= '<tr class="testa"><th class="titolo_lista_sx">'.$riepilogo_cam[$i]["piano"].' PIANO</th><th>NUM</th><th>PAX</th></tr>';
			
			$piano_cam = 0; // Resettiamo i totali del piano
			$piano_pax = 0;
		}
		
		$print_cam .= '<tr class="riga">';
		
		$print_cam .= '<td class="tipo per_comparsa">';
		if($riepilogo_cam[$i]['vestizione'] == '') $print_cam .= '?';
		else 												$print_cam .= $riepilogo_cam[$i]['vestizione'];
		if($riepilogo_cam[$i]['tipologia'] != '')  $print_cam .= '+' . $riepilogo_cam[$i]['tipologia'];
		// Inseriamo i numeri delle camere a comparsa
		$print_cam .= '<span class="a_comparsa">'.$riepilogo_cam[$i]['numeri_camere'].'</tspan>';
		$print_cam .= '</td>';
		
		$print_cam .= '<td>' . $riepilogo_cam[$i]['camere'] . '</td>';
		$print_cam .= '<td>' . $riepilogo_cam[$i]['pax'] . '</td>';
		$print_cam .= '</tr>';
		
		// Aggiorniamo i sub totali del piano
		$piano_cam += $riepilogo_cam[$i]["camere"];
		$piano_pax += $riepilogo_cam[$i]["pax"];
		
		
		// Se siamo all'ultima riga stampiamo anche i subtotali piano
		if($i == $num_righe -1) $print_cam .= '<tr class="riga"><td>TOTIALI PIANO</td><td>'.$piano_cam.'</td><td>'.$piano_pax.'</td></tr>';
	}
	
	// Stampiamo i totali
	if($num_righe > 0) {
		$print_cam .= '<tr class="totali"><td class="tipo">TOTALI</td><td>' . $riepilogo_cam["totali"]["camere"] . '</td>';
		$print_cam .= '<td>' . $riepilogo_cam["totali"]["pax"] . '</td>';
		$print_cam .= '</tr>';
	}
	
	$print_cam .= '</table>';
	$print_cam .= '<br /><br /></div>';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head>
	<title>CAMERE</title><?php
	header_standard();
?></head>

<body>
<?php $testo_menu_top = '';
		menu_top($testo_menu_top); 
		
		echo '<div id="corpo_a">';
		
		echo colonna_sinistra($vuoto, $vuoto, $vuoto, $print_cam);

		echo '<div class="cont_dx">';

		if($azione === 'ASK TO DELETE ROOM') {
			echo '<div class="form1_all form_green">';
			echo '<p class="titolo">CONFERMA ELIMINAZIONE</p>';
		
			echo '<div class="cont_op"><p class="form_txt">sei sicuro di voler eliminare la camera <span>'.$numeroCameraEl.'</span> definitivamente ?</p></div>';
			
			echo '<div class="pulsanti_bottom">';
			echo '<a class="bottone" href="gestione_camere.php">NO</a>';
			
			echo '<form style="display: inline;" name="dati" action="gestione_camere.php" method="post" enctype="multipart/form-data">';
			echo '<input class="bottone bottone_r" type="submit" value="SI" />';
			echo '<input type="hidden" name="conferma" value="'.$camera_sel.'" />';
			echo '</form>';
			echo '</div>';
			echo '</div>';
		}
			
		elseif($azione === 'ROOM DELETED') {
			echo '<div class="form1_all form_green">';
			echo '<p class="titolo">CAMERA ELIMINATA CON SUCCESSO</p>';
			echo '</div>';
		}

		elseif($azione === "ROOM MODIFIED") {
			echo '<div class="form1_all form_green">';
			echo '<p class="titolo">CAMERA MODIFICATA CON SUCCESSO</p>';
			echo '</div>';
		}
		
		elseif($azione === "ROOM ADDED") {
			echo '<div class="form1_all form_green">';
			echo '<p class="titolo">CAMERA AGGIUNTA CON SUCCESSO</p>';
			echo '</div>';
		}
		
		// Per modificare o aggiungere camere
	   echo '<form name="dati" action="gestione_camere.php" method="post" enctype="multipart/form-data">';
	   
	   echo '<div class="form1_650">';
		if(isset($_GET['id_modifica'])) {
			echo '<p class="titolo">MODIFICA CAMERA</p>';
			echo'<input type="hidden" name="modifica" value="'.$camera_sel.'" />';
		}
		else 	echo '<p class="titolo">AGGIUNGI CAMERA</p>';
		
		echo '<table class="aggiungi_camere">';
		echo '<tbody>';
		echo '<tr>';
		echo '<td><input type="text" placeholder="NUM." name="numero" class="field" value="'.$numero.'" autofocus /></td>';
		echo '<td><input type="text" placeholder="PIANO" name="piano" class="field" value="'.$piano.'" /></td>';
		echo '<td><input type="text" placeholder="VEST" name="vestizione_max" class="field" value="'.$vestizione_max.'" /></td>';
		echo '<td><input type="text" placeholder="PAX" name="pax_max" class="field" value="'.$pax_max.'" /></td>';
		echo '<td><input type="text" placeholder="TIPO." name="descrizione_breve" class="field" value="'.$descrizione_breve.'" /></td>';
		echo '<td><input type="text" placeholder="VOTO" name="voto" class="field" value="'.$voto.'" /></td>';
		echo '</tr>';
		echo '<tr><td colspan="6"><input type="text" placeholder="UBICAZIONE" name="ubicazione" class="field" value="'.$ubicazione.'" /></td></tr>';
		echo '<tr><td colspan="6"><input type="text" placeholder="NOTE" name="note" class="field" value="'.$note.'" /></td></tr>';
		
		echo '</tbody>';
		
		echo '</table>';
		
		echo '<div class="pulsanti_bottom">';
			
		echo '<a class="bottone" href="gestione_camere.php">ANNULLA</a> ';
		if(isset($_GET['id_modifica'])) 		echo '<input class="bottone" type="submit" value="MODIFICA" />';
		else 											echo '<input class="bottone" type="submit" name="crea" value="AGGIUNGI" />';
		echo '</div>';
		
		echo '</div>';
		echo '</form>';
		
		
		// Stampiamo la testata delle camere
		echo '<div class="form1_all"><table class="lista_prenotazioni">';
		echo '<p class="titolo">LISTA CAMERE</p>';
		echo '<tr class="border_bottom"><th class="small_tab1">NUM.</th><th class="small_tab1">PIANO</th><th class="small_tab1">VEST</th>' //
							.'<th class="small_tab1">TIPO</th><th class="small_tab1">VOTO</th><th class="big_tab1">UBICAZIONE</th>' //
							.'<th class="big_tab1">NOTE</th><th class="small_tab1">OPERAZIONI</th></tr>';
		
		// Stampiamo le camere
		for($i = 0 ; $i < $_SESSION['num_cam'] ; $i++) {
			if($i+1 < $_SESSION['num_cam']) {
				if($_SESSION['cam'][$i]['piano'] != $_SESSION['cam'][$i+1]['piano'])					  echo '<tr class="border_bottom_big">'; // Cambio piano
				elseif($_SESSION['cam'][$i]['ubicazione'] != $_SESSION['cam'][$i+1]['ubicazione']) echo '<tr class="border_bottom_medium">'; // Cambio ubicazione
				else 																		  echo '<tr class="border_bottom">'; // Normale
			}
			else																			  echo '<tr class="border_bottom">'; // Normale
			
			echo '<td class="monospace_style" style="background:#'.$_SESSION['cam'][$i]['colore'].'">'.$_SESSION['cam'][$i]['numero'].'</td>';
			echo '<td class="monospace_style">'.$_SESSION['cam'][$i]['piano'].'</td>';
			echo '<td class="monospace_style">'.formatta_visualizzazione($_SESSION['cam'][$i]['vestizione_max']).'</td>';
			echo '<td class="monospace_style">'.formatta_visualizzazione($_SESSION['cam'][$i]['descrizione_breve']).'</td>';
			echo '<td class="monospace_style">'.$_SESSION['cam'][$i]['voto'].'</td>';
			echo '<td class="monospace_style">'.formatta_visualizzazione($_SESSION['cam'][$i]['ubicazione']).'</td>';
			echo '<td class="monospace_style">'.formatta_visualizzazione($_SESSION['cam'][$i]['note']).'</td>';
	
			echo '<td><div class="center hover_show">';
	
			echo '<a class="bottone bottone_t1" href="gestione_camere.php?id_modifica='.$_SESSION['cam'][$i]['id'].'">MOD</a> ';
			echo '<a class="bottone bottone_t1 bottone_r" href="gestione_camere.php?elimina='.$_SESSION['cam'][$i]['id'].'">CLX</a>';
			
			echo '</div></td></tr>';
		}
		
		if($_SESSION['num_cam'] < 1) {
			echo '<tr><td colspan="4">NESSUNA CAMERA PRESENTE</td></tr>';
		}
		
		echo '</div>';
		
		echo '</div>';
		
	?></body>
</html>
<?php
} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>