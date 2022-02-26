<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore

	$azione = 0;
	$vuoto = "";
	
	$info_menu = "";
	
	//se si tratta di eliminare si chiede conferma
	if(isset($_POST['clx_gruppo'])) {
		$id_gruppo = $_POST['clx_gruppo'];

		$db = db_connect();
		$nome_grezzo = $db->query('SELECT nome FROM gruppi WHERE id=' . $id_gruppo);
		$db->connection = NULL;
		
		$nome_grezzo_tab = $nome_grezzo->fetch(PDO::FETCH_ASSOC);
		$nome_el = $nome_grezzo_tab['nome'];

		$azione = 'ASK TO DELETE GROUP';
	}
	
	elseif(isset($_POST['clx_privato']) || isset($_GET['clx_privato'])) {
		if(isset($_POST['clx_privato'])) $id_pre = intval($_POST['clx_privato']);
		else 										$id_pre = intval($_GET['clx_privato']);
		
		$db = db_connect();
		$rispostaPrenotazione = $db->query('SELECT nome FROM prenotazioni WHERE id=' . $id_pre);
		$db->connection = NULL;
		
		$dati_pre_el 	= $rispostaPrenotazione->fetch(PDO::FETCH_ASSOC);
		$nome_el			= $dati_pre_el['nome'];
		
		$azione = 'ASK TO DELETE PRIVATE';
	}
	
	//se si tratta di eliminare e si é confermato si elimina
	elseif(isset($_POST['conferma_gruppo'])) {
		$id_gruppo_clx = $_POST['conferma_gruppo'];
		
		$db = db_connect();
		// Si eliminano le prenotazioni per il gruppo
		$db->query('DELETE FROM prenotazioni WHERE gruppo=' . $id_gruppo_clx);
		$db->query('DELETE FROM gruppi WHERE id=' . $id_gruppo_clx);
		$db->connection = NULL;
		
		$azione = 'GROUP DELETED';
	}
	
	//se si tratta di eliminare e si é confermato si elimina
	elseif(isset($_POST['conferma_privato'])) {
		$id_pre_clx = intval($_POST['conferma_privato']);
		
		$db = db_connect();
		// Prendiamo dati utili per aggiornare il database dei gruppi
		$dati_pre_clx_tab = $db->query('SELECT gruppo FROM prenotazioni WHERE id=' . $id_pre_clx);
		$db->query('DELETE FROM prenotazioni WHERE id='.$id_pre_clx.' OR id_rif='.$id_pre_clx);
		$db->connection = NULL;
		
		$dati_pre_clx = $dati_pre_clx_tab->fetch(PDO::FETCH_ASSOC);
		// Aggiorniamo i dati del gruppo
		if($dati_pre_clx['gruppo'] != 0) aggiornamento_gruppo($dati_pre_clx['gruppo']);
		
		$azione = 'PRIVATE DELETED';
	}
	
	
	// Si prendono i dati per la lista
	$data_ora_oggi = time();
	$data_oggi = mktime(0, 0, 0, date("n", $data_ora_oggi), date("d", $data_ora_oggi), date("Y", $data_ora_oggi));
	$righe_per_pagina = 50;
	$campo_rc_inserito = FALSE;
	$tab_gruppi = NULL;
	$riga_gruppo = array();
	$num_righe = 0;
	
	if(isset($_POST['periodo_rc'])) {
		$_SESSION['li_pre_periodo'] = $_POST['periodo_rc'];
		$_SESSION['li_pre_tipo'] = $_POST['tipo_rc'];
		$_SESSION['li_pre_nome'] = formatta_salvataggio($_POST['nome_rc']);
		$_SESSION['li_pre_agenzia'] = formatta_salvataggio($_POST['agenzia_rc']);
		$_SESSION['li_pre_data_arrivo'] = controllo_data($_POST['data_arrivo_rc']);
		$_SESSION['li_pre_data_partenza'] = controllo_data($_POST['data_partenza_rc']);
		$_SESSION['li_pre_pagina'] = 0;
	}
	elseif(isset($_POST['pagina'])) $_SESSION['li_pre_pagina'] = intval($_POST['pagina']);
	
	$tot_camere_all = 0; $tot_pax_all = 0; $tot_na_all = 0;

	$tab_gruppi .= '<div class="form1_all"><table class="lista_prenotazioni">';
	
	if($_SESSION['li_pre_tipo'] == "gruppi") {
		$tab_gruppi .= '<p class="titolo">LISTA GRUPPI</p>'//
						.  '<tr class="border_bottom"><th class="big_tab1">NOME</th><th>AGENZIA</th><th class="data_tab1">PERIODO</th><th class="big_tab1">RIEPILOGO</th><th class="small_tab1">TOT.</th><th class="small_tab1">NA</th><th class="small_tab1">PAX</th><th class="medium_tab1">OPERAZIONI</th></tr>';
		
		$sql_query = 'SELECT * FROM gruppi';
	}
	
	else {
		$tab_gruppi .= '<p class="titolo">LISTA PRIVATI</p>' //
						. '<tr class="border_bottom"><th class="big_tab1">NOME</th><th>AGENZIA</th><th class="data_tab1">PERIODO</th>' //
						. '<th class="small_tab1" colspan="2">CAMERA</th><th class="small_tab1">PAX</th><th class="small_tab1">A</th><th class="medium_tab1">OPERAZIONI</th></tr>';
		
		$sql_query = 'SELECT * FROM prenotazioni WHERE gruppo = 0 AND id_rif=0';
		
		// Abbiamo già inserito per i privati un primo parametro: gruppo = 0
		$campo_rc_inserito = TRUE;
	}
	
	if($_SESSION['li_pre_nome'] != '') {
		if($campo_rc_inserito ==  FALSE) { $campo_rc_inserito = TRUE; $sql_query .= ' WHERE '; }
		else 										  $sql_query .= ' AND ';
		$sql_query .= 'nome LIKE \'%' . $_SESSION['li_pre_nome'] . '%\'';
	}
	if($_SESSION['li_pre_agenzia'] != '') {
		if($campo_rc_inserito ==  FALSE) { $campo_rc_inserito = TRUE; $sql_query .= ' WHERE '; }
		else 										  $sql_query .= ' AND ';
		$sql_query .= 'agenzia LIKE \'%' . $_SESSION['li_pre_agenzia'] . '%\'';
	}
	
	if(isset($_SESSION['li_pre_pagina'])) $pagina = intval($_SESSION['li_pre_pagina']);
	if($pagina != 0) $limit = $pagina * $righe_per_pagina;
	else 				  $limit = 0;
	
	if($_SESSION['li_pre_data_arrivo'] == NULL && $_SESSION['li_pre_data_partenza'] == NULL) {
		if($_SESSION['li_pre_periodo'] == 'passato') {
			if($campo_rc_inserito ==  FALSE) { $campo_rc_inserito = TRUE; $sql_query .= ' WHERE '; }
			else 										  $sql_query .= ' AND ';
			$sql_query .= 'data_partenza < ' . $data_oggi . ' ORDER BY data_partenza DESC LIMIT ' . $limit . ',' . $righe_per_pagina;
		}
		elseif($_SESSION['li_pre_periodo'] == 'futuro') {
			if($campo_rc_inserito ==  FALSE) { $campo_rc_inserito = TRUE; $sql_query .= ' WHERE '; }
			else 										  $sql_query .= ' AND ';
			$sql_query .= 'data_arrivo > ' . $data_oggi . ' ORDER BY data_arrivo ASC LIMIT ' . $limit . ',' . $righe_per_pagina;
		}
		elseif($_SESSION['li_pre_periodo'] == 'presente') {
			if($campo_rc_inserito ==  FALSE) { $campo_rc_inserito = TRUE; $sql_query .= ' WHERE '; }
			else 										  $sql_query .= ' AND ';
			$sql_query .= 'data_arrivo<=' . $data_oggi . ' AND data_partenza>=' . $data_oggi . ' ORDER BY nome';
		}
		elseif($_SESSION['li_pre_periodo'] == 'tutti') {
			$sql_query .= ' ORDER BY data_arrivo DESC LIMIT ' . $limit . ',' . $righe_per_pagina;
		}
	}
	// Se si sono inserite date specifiche
	else {
		if($campo_rc_inserito ==  FALSE) { $campo_rc_inserito = TRUE; $sql_query .= ' WHERE '; }
		else 										  $sql_query .= ' AND ';
		$sql_query .= 'data_arrivo >= ' . $_SESSION['li_pre_data_arrivo'] . ' AND data_partenza <= '.$_SESSION['li_pre_data_partenza'].' ORDER BY data_arrivo ASC LIMIT ' . $limit . ',' . $righe_per_pagina;
	}
	
	$db = db_connect();
	$reponse = $db->query($sql_query);
	$db->connection = NULL;

	// Se siamo nei gruppi
	if($_SESSION['li_pre_tipo'] == 'gruppi') {
		// Inseriamo tutte le entrate GRUPPI in un array
		for($num_righe = 0 ; $donnees = $reponse->fetch(PDO::FETCH_ASSOC) ; $num_righe++) {
			$tot_camere_all += $donnees['totale_camere'];
			$tot_pax_all	 += $donnees['totale_pax'];
			$tot_na_all		 += $donnees['camere_non_assegnate'];
		
			$tab_gruppi .= '<tr class="border_bottom"><td class="c_' . $donnees['colore'] . '">';
			if($donnees['note'] != '') $tab_gruppi .= '<span class="list_note">*</span> <span class="info">' . formatta_visualizzazione($donnees['note']) . '</span>';
			$tab_gruppi .= '<a href="ge_gru.php?mg='.$donnees['id'].'">'.formatta_visualizzazione($donnees['nome']).'</a>';
			echo '</td>';
			$tab_gruppi .= '<td>' . formatta_visualizzazione($donnees['agenzia']) . '</td>';
			$tab_gruppi .= '<td class="monospace_style">' . formatta_periodo($donnees['data_arrivo'], $donnees['data_partenza']) . '</td>';
			$tab_gruppi .= '<td class="monospace_style">' . $donnees['riepilogo'] . '</td>';
			$tab_gruppi .= '<td class="monospace_style_dx">' . $donnees['totale_camere'] . '</td>';
			
			if($donnees['camere_non_assegnate'] > 0)  $tab_gruppi .= '<td class="monospace_style_dx clr_red">' . $donnees['camere_non_assegnate'] . '</td>';
			else 													$tab_gruppi .= '<td class="monospace_style_dx">0</td>';
			$tab_gruppi .= '<td class="monospace_style_dx">' . $donnees['totale_pax'] . '</td>';
	
			$tab_gruppi .= '<td>';
			$tab_gruppi .= '<div class="center hover_show">';
			
			$tab_gruppi .= '<a class="bottone bottone_t1" href="prenotazioni.php?id_gruppo='.$donnees['id'].'">B</a>';
			
			$tab_gruppi .= ' <a class="bottone bottone_t1" href="rooming_list.php?id_gruppo='.$donnees['id'].'">RL</a>';
			
			$tab_gruppi .= '<a class="bottone bottone_t1" href="ass_cam.php?id_gruppo='.$donnees['id'].'">ASS CAM</a>';
			
			$tab_gruppi .= '<form name="dati" action="lista_pre.php" method="post" enctype="multipart/form-data">';
			$tab_gruppi .= '<input class="bottone bottone_t1 bottone_r" type="submit" value="CLX" />';
			$tab_gruppi .= '<input type="hidden" name="clx_gruppo" value="' . $donnees['id'] . '" />';
			$tab_gruppi .= '</form>';
			$tab_gruppi .= '</div>';
			$tab_gruppi .= '</td>';
			
			$tab_gruppi .= '</tr>';
		}
		
		if($num_righe > 0) {
			
			$tab_gruppi .= '<tr><td class="monospace_style_dx">TOTALI : </td>';
			if($num_righe > 1) $tab_gruppi .= '<td colspan="3">' . $num_righe . ' GRUPPI</td>';
			else 					 $tab_gruppi .= '<td colspan="3">1 GRUPPO</td>';
			$tab_gruppi .= '<td class="monospace_style_dx">' . $tot_camere_all . '</td>';
			
			if($tot_na_all > 0)  $tab_gruppi .= '<td class="monospace_style_dx clr_red">';
			else 						$tab_gruppi .= '<td class="monospace_style_dx">';
			$tab_gruppi .= $tot_na_all . '</td>';
			
			$tab_gruppi .= '<td class="monospace_style_dx">' . $tot_pax_all . '</td>'//
							 . '<td></td></tr>';
	
			$tab_gruppi .= '</table>';
		}
		else {
			$tab_gruppi .= '<tr><td>NESSUN RISULTATO :(</td></tr></table>';
		}
	}
	// Se siamo nei privati
	else {
		$pasti = array('B', 'L', 'D');
		
		// Inseriamo tutte le entrate PRIVATI in un array
		for($num_righe = 0 ; $donnees = $reponse->fetch(PDO::FETCH_ASSOC) ; $num_righe++) {
			$tot_camere_all++;
			$tot_pax_all += $donnees['pax'];
			if($donnees['camera'] == 0) $tot_na_all++;
		
			$tab_gruppi .= '<tr class="border_bottom"><td>';
			if($donnees['note'] != "")
				$tab_gruppi .= '<span class="list_note cn_'.$donnees['colore_note'].'">*</span> <span class="info">' . formatta_visualizzazione($donnees['note']) . '</span>';
			$tab_gruppi .= '<a href="ge_pre.php?mp='.$donnees['id'].'">'.formatta_visualizzazione($donnees['nome']).'</a>';
			echo '</td>';
			$tab_gruppi .= '<td>' . formatta_visualizzazione($donnees['agenzia']) . '</td>';
			$tab_gruppi .= '<td class="monospace_style">'.formatta_periodo($donnees['data_arrivo'], $donnees['data_partenza']).'</td>';
			$tab_gruppi .= '<td class="monospace_style">';
			if($donnees['camera'] == 0) $tab_gruppi .= '<span class="clr_red">NA</style>';
			else 								 $tab_gruppi .= $donnees['camera'];
			$tab_gruppi .= '</td>';
			$tab_gruppi .= '<td class="monospace_style">';
			$tab_gruppi .= formatta_visualizzazione($donnees['vestizione']);
			if($donnees['tipologia'] != "") $tab_gruppi .= '+' . formatta_visualizzazione($donnees['tipologia']);
			$tab_gruppi .= '</td>';
			$tab_gruppi .= '<td class="monospace_style_dx">' . $donnees['pax'] . '</td>';
			$tab_gruppi .= '<td class="monospace_style_dx">' . formatta_visualizzazione($donnees['arrangiamento']);
			if($donnees['arrangiamento'] != 'RS') $tab_gruppi .= '-' . $pasti[$donnees['primo_pasto'] - 1] . '-' . $pasti[$donnees['ultimo_pasto'] - 1];
			$tab_gruppi .= '</td>';
	
			$tab_gruppi .= '<td>';
			$tab_gruppi .= '<div class="center hover_show">';
			
			$tab_gruppi .= '<a class="bottone bottone_t1" href="prenotazioni.php?id_pre='.$donnees['id'].'">B</a>';
			
			$tab_gruppi .= '<form name="dati" action="lista_pre.php" method="post" enctype="multipart/form-data">';
			$tab_gruppi .= '<input class="bottone bottone_t1 bottone_r" type="submit" value="CLX" />';
			$tab_gruppi .= '<input type="hidden" name="clx_privato" value="' . $donnees['id'] . '" />';
			$tab_gruppi .= '</form>';
			$tab_gruppi .= '</div>';
			$tab_gruppi .= '</td>';
			
			$tab_gruppi .= '</tr>';
		}

		if($num_righe > 0) {
			
			$tab_gruppi .= '<tr><td class="monospace_style_dx">TOTALI : </td>';
			if($num_righe > 1) $tab_gruppi .= '<td colspan="2">' . $num_righe . ' PRENOTAZIONI</td>';
			else 					 $tab_gruppi .= '<td colspan="2">1 PRENOTAZIONE</td>';
			
			if($tot_na_all > 0)  $tab_gruppi .= '<td class="monospace_style clr_red" colspan="2">';
			else 						$tab_gruppi .= '<td class="monospace_style" colspan="2">';
			$tab_gruppi .= $tot_na_all . '</td>';
			
			$tab_gruppi .= '<td class="monospace_style_dx">' . $tot_pax_all . '</td>'//
							 . '<td colspan="2"></td></tr>';
	
			$tab_gruppi .= '</table>';
		}
		else {
			$tab_gruppi .= '<tr><td>NESSUN RISULTATO :(</td></tr></table>';
		}
	}
	


	

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head>
	<title>LISTA PRENOTAZIONI</title><?php
	header_standard();
?></head>
<body><?php
	$testo_menu_top = "";	menu_top($testo_menu_top);
?>
		<div id="corpo_a"><?php
		// STAMPIAMO LA LISTA DELLE PRENOTAZIONI
			$pulsanti = '<div class="a_padding"><a class="bottone" href="ge_gru.php">+ GRUPPO</a>';
			$pulsanti .= '<a class="bottone" href="ge_pre.php">+ PRIVATO</a></div>';

			echo colonna_sinistra($pulsanti, $vuoto, $vuoto, ricerca_prenotazioni());
			
			echo '<div class="cont_dx">';
			
			if($azione === 'ASK TO DELETE GROUP' || $azione === 'ASK TO DELETE PRIVATE') {
				echo '<div class="form1_all form_green">';
				echo '<p class="titolo">CONFERMA ELIMINAZIONE</p>';
			
				if($azione === 'ASK TO DELETE GROUP')
					echo '<div class="cont_op"><p class="form_txt">sei sicuro di voler eliminare il gruppo <span>' . $nome_el . '</span> definitivamente ?</p></div>';
				else
					echo '<div class="cont_op"><p class="form_txt">sei sicuro di voler eliminare la prenotazione <span>' . $nome_el . '</span> definitivamente ?</p></div>';
				
				echo '<div class="pulsanti_bottom">';
				echo '<form style="display: inline;" name="dati" action="lista_pre.php" method="post" enctype="multipart/form-data">';
				echo '<input class="bottone" type="submit" value="NO" />';
				echo '</form>';
				
				echo '<form style="display: inline;" name="dati" action="lista_pre.php" method="post" enctype="multipart/form-data">';
				echo '<input class="bottone bottone_r" type="submit" value="SI" />';
				if($azione === 'ASK TO DELETE GROUP') echo '<input type="hidden" name="conferma_gruppo" value="' . $id_gruppo . '" />';
				else 											  echo '<input type="hidden" name="conferma_privato" value="' . $id_pre . '" />';
				echo '</form>';
				echo '</div>';
				echo '</div>';
			}
			
			elseif($azione === 'GROUP DELETED') {
				echo '<div class="form1_all form_green">';
				echo '<p class="titolo">GRUPPO ELIMINATO CON SUCCESSO</p>';
				echo '</div>';
			}
			
			elseif($azione === 'PRIVATE DELETED') {
				echo '<div class="form1_all form_green">';
				echo '<p class="titolo">PRIVATO ELIMINATO CON SUCCESSO</p>';
				echo '</div>';
			}
			
			echo $tab_gruppi;
			if($pagina > 0 || $num_righe == $righe_per_pagina) {
				echo '<div class="pulsanti_bottom">';
				
				if($num_righe == $righe_per_pagina) {
					echo '<form name="dati" class="form_inline" action="lista_pre.php" method="post" enctype="multipart/form-data">';
					echo '<input class="bottone" type="submit" value="<< PRECEDENTE" />';
					echo '<input type="hidden" name="pagina" value="'.($pagina+1).'" />';
					echo '</form>';
				}
				echo '<span class="bottone">' . ($pagina+1) . '</span>';
				if($pagina > 0) {
					echo '<form name="dati" class="form_inline" action="lista_pre.php" method="post" enctype="multipart/form-data">';
					echo '<input class="bottone" type="submit" value="SUCCESSIVO >>" />';
					echo '<input type="hidden" name="pagina" value="'.($pagina-1).'" />';
					echo '</form>';
				}
				echo '</div>';
			}
		echo '</div>';
		
		?></div>
	</body>
</html><?php
} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>