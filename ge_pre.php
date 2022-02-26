<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore

	$azione = '';
	$stampa_problemi = '';
	$pre_update = 'UPDATE prenotazioni A inner join(';
	$spe_update = '';
	
	$cam_agg = array();
	$cambio_cam = array();
	$share = array();
	
	$num_cam_agg = 0;
	$num_cambio_cam = 0;
	$num_share = 0;
	$id_spe_clx = array();
	$num_spe_clx = 0;
	
	$num_spe = 0;
	
	$tot_cam_agg = 2;
	$tot_cambio_cam = 2;
	$tot_share = 3;
	
	$agg_gruppo_nuovo = FALSE;
	$agg_gruppo_old = FALSE;
	$crea_gruppo = FALSE;
	
	
	$pagina = 'gestione_prenotazioni';

	$db = db_connect();
	
	$info_pre['id_rif']					 = 0;
	$info_pre['tipo_pre']				 = 0;
	$info_pre['stile_spe']				 = 0;
	$info_pre['nome'] 					 = '';
	$info_pre['id_gruppo'] 				 = 0;
	$info_pre['nome_gruppo'] 			 = '';
	$info_pre['agenzia']					 = '';
	$info_pre['vestizione'] 			 = '';
	$info_pre['tipologia'] 				 = '';
	$info_pre['camera'] 					 = '';
	$info_pre['pax']						 = '';
	$info_pre['arrangiamento']			 = '';
	$info_pre['primo_pasto']			 = 3; // CENA BY DEFAULT, 1 = BREAKFAST, 2 = LUNCH, 3 = DINNER
	$info_pre['ultimo_pasto']			 = 1; // BREAKFAST BY DEFAULT
	$info_pre['data_arrivo'] 			 = '';	
	$info_pre['data_partenza']			 = '';
	$info_pre['note']			 			 = '';
	$info_pre['colore_note'] 			 = 0;
	$info_pre['problemi']	 			 = '';
	$info_pre['data_ultima_modifica'] = '';
	
	if(isset($_POST['modifica']) || isset($_POST['duplica']) || isset($_GET['elimina']) || isset($_GET['mp'])) {
		$modifica_pre = TRUE;
	}
	else 		$modifica_pre = FALSE;

	// Se la prenotazione va modificata
	if(isset($_GET['mp'])) {
		$prenotazione_sel = intval($_GET['mp']);
	
		// Prendiamo i dati della prenotazione e annesse
		$reponse = $db->query('SELECT prenotazioni.*, gruppi.nome AS nome_gruppo, gruppi.colore AS colore_gruppo FROM prenotazioni '//
									.'LEFT JOIN gruppi ON prenotazioni.gruppo=gruppi.id '//
									.'WHERE prenotazioni.id='.$prenotazione_sel.' OR id_rif='.$prenotazione_sel //
									.' ORDER BY id_rif');

		$pre_spe = $reponse->fetchAll(PDO::FETCH_ASSOC);
		$num_pre_spe = count($pre_spe);
		$num_spe = $num_pre_spe - 1;

		// Settiamo i dati della prenotazione principale, prima per ordine
		if($num_pre_spe > 0) {
			// Variabile che sta ad indicare che si agisce su una prenotazione già esistente
			$info_pre['id'] = &$pre_spe[0]['id'];
			
			$info_pre['tipo_pre'] 				 = &$pre_spe[0]['tipo_pre'];
			$info_pre['nome'] 					 = formatta_visualizzazione($pre_spe[0]['nome']);
			$info_pre['id_gruppo'] 				 = &$pre_spe[0]['gruppo'];
			$info_pre['nome_gruppo'] 			 = formatta_visualizzazione($pre_spe[0]['nome_gruppo']);
			$info_pre['colore_gruppo'] 		 = formatta_visualizzazione($pre_spe[0]['colore_gruppo']);
			$info_pre['agenzia']					 = formatta_visualizzazione($pre_spe[0]['agenzia']);
			$info_pre['vestizione']				 = formatta_visualizzazione($pre_spe[0]['vestizione']);
			$info_pre['tipologia']				 = formatta_visualizzazione($pre_spe[0]['tipologia']);
			$info_pre['pax']						 = &$pre_spe[0]['pax'];
			$info_pre['arrangiamento']			 = formatta_visualizzazione($pre_spe[0]['arrangiamento']);
			$info_pre['primo_pasto']			 = &$pre_spe[0]['primo_pasto'];
			$info_pre['ultimo_pasto']			 = &$pre_spe[0]['ultimo_pasto'];
			$info_pre['data_ultima_modifica'] = &$pre_spe[0]['data_ultima_modifica'];
			
			$info_pre['data_arrivo'] = &$pre_spe[0]['data_arrivo'];
			$info_pre['data_partenza'] = &$pre_spe[0]['data_partenza'];
			
			$info_pre['camera'] 					 = &$pre_spe[0]['camera'];

			$info_pre['note']			 			 = formatta_visualizzazione($pre_spe[0]['note']);
			$info_pre['colore_note']			 = &$pre_spe[0]['colore_note'];
			$info_pre['problemi']				 = &$pre_spe[0]['problemi'];
			
			
			// Recuperiamo e settiamo i dati delle speciali
			for($i = 1 ; $i < $num_pre_spe ; $i++) {

				// Se la spe è una camera aggiuntiva
				if($pre_spe[$i]['tipo_pre'] >= 10 && $pre_spe[$i]['tipo_pre'] <= 19) {
					$cam_agg[$num_cam_agg]['id'] = &$pre_spe[$i]['id'];
					$cam_agg[$num_cam_agg]['camera'] = &$pre_spe[$i]['camera'];
					$cam_agg[$num_cam_agg]['pax'] = &$pre_spe[$i]['pax'];
					$cam_agg[$num_cam_agg]['vestizione'] = formatta_visualizzazione($pre_spe[$i]['vestizione']);
					$num_cam_agg++;
				}
				// Se la spe è un cambio camera
				elseif($pre_spe[$i]['tipo_pre'] >= 20 && $pre_spe[$i]['tipo_pre'] <= 29) {
					$cambio_cam[$num_cambio_cam]['id'] = &$pre_spe[$i]['id'];
					$cambio_cam[$num_cambio_cam]['data_arrivo'] = &$pre_spe[$i]['data_arrivo'];
					$cambio_cam[$num_cambio_cam]['data_partenza'] = &$pre_spe[$i]['data_partenza'];
					$cambio_cam[$num_cambio_cam]['camera'] = &$pre_spe[$i]['camera'];
					$cambio_cam[$num_cambio_cam]['vestizione'] = formatta_visualizzazione($pre_spe[$i]['vestizione']);
					$num_cambio_cam++;
				}
				// Se la spe è uno share
				elseif($pre_spe[$i]['tipo_pre'] >= 30 && $pre_spe[$i]['tipo_pre'] <= 39) {
					$share[$num_share]['id'] = &$pre_spe[$i]['id'];
					$share[$num_share]['nome'] = &$pre_spe[$i]['nome'];
					$share[$num_share]['data_arrivo'] = &$pre_spe[$i]['data_arrivo'];
					$share[$num_share]['data_partenza'] = &$pre_spe[$i]['data_partenza'];
					$share[$num_share]['pax'] = &$pre_spe[$i]['pax'];
					$share[$num_share]['arrangiamento'] = formatta_visualizzazione($pre_spe[$i]['arrangiamento']);
					$share[$num_share]['primo_pasto'] = &$pre_spe[$i]['primo_pasto'];
					$share[$num_share]['ultimo_pasto'] = &$pre_spe[$i]['ultimo_pasto'];
					$num_share++;
				}
			}

			// Inseriamo le righe vuote
			$tot_cam_agg += $num_cam_agg;
			$tot_cambio_cam += $num_cambio_cam;
			$tot_share += $num_share;
		}
		else 	$azione = 'NOT FOUND';
	}

	// Se si sta aggiungendo una prenotazione a partire dal motore di booking
	elseif(isset($_GET['n_room'])) {
		$info_pre['camera'] 					 = intval($_GET['cam']);
		$info_pre['data_arrivo'] 			 = intval($_GET['da']); // da = data arrivo
		$info_pre['data_partenza']			 = intval($_GET['dp']); // dp = data partenza
	}


	// Se il form è stato compilato e inviato
	elseif(isset($_POST['nome'])) {
		if(isset($_POST['id_prenotazione']))
					 $info_pre['id'] = intval($_POST['id_prenotazione']);
		else		 $info_pre['id'] = NULL;
		
		$info_pre['tipo_pre']				 = intval($_POST['tipo_pre']);
		$info_pre['nome'] 					 = formatta_salvataggio($_POST['nome']);
		$info_pre['nome_gruppo'] 			 = formatta_salvataggio($_POST['gruppo']);
		$info_pre['colore_gruppo'] 		 = formatta_salvataggio($_POST['colore_gruppo']);
		$info_pre['agenzia']					 = formatta_salvataggio($_POST['agenzia']);
		$info_pre['vestizione'] 			 = formatta_salvataggio($_POST['vestizione']);
		$info_pre['tipologia'] 				 = formatta_salvataggio($_POST['tipologia']);
		$info_pre['pax']						 = intval($_POST['pax']);
		$info_pre['arrangiamento'] 		 = formatta_salvataggio($_POST['arrangiamento']);
		$info_pre['primo_pasto']			 = intval($_POST['primo_pasto']);
		$info_pre['ultimo_pasto']			 = intval($_POST['ultimo_pasto']);
		$info_pre['data_arrivo'] 			 = controllo_data($_POST['data_arrivo']);
		$info_pre['data_partenza']			 = controllo_data($_POST['data_partenza']);

		$info_pre['camera'] 					 = intval($_POST['camera']);
		if($info_pre['camera'] == '')  	 $info_pre['camera'] = 0;
	
		$info_pre['note']			 			 = formatta_salvataggio($_POST['note']);
		$info_pre['colore_note']			 = intval($_POST['colore_note']);
		$info_pre['data_ultima_modifica'] = time();
	
		// Se la prenotazione va modificata
		if($info_pre['id'] != NULL && !isset($_POST['duplica'])) {
			
			// Recuperiamo l'id del gruppo in precedenza
			$id_gruppo_vecchio_grezzo = $db->query('SELECT gruppo FROM prenotazioni WHERE id=' . $info_pre['id']);
			$id_gruppo_vecchio_tab = $id_gruppo_vecchio_grezzo->fetch(PDO::FETCH_ASSOC);
			
			// Se la prenotazione era precedentemente agganciata a un gruppo
			if($id_gruppo_vecchio_tab != NULL) {
				$id_gruppo_vecchio = &$id_gruppo_vecchio_tab['gruppo'];

				// Se la prenotazione è ancora agganciata a un gruppo
				if($info_pre['nome_gruppo'] != '') {
					// Controlliamo se il nuovo gruppo già esiste
					$id_gruppo_grezzo = $db->query('SELECT id FROM gruppi WHERE nome=\'' . $info_pre['nome_gruppo'] . '\' AND ' //
					. '((data_arrivo >= '.$info_pre['data_arrivo'].' && data_arrivo < '.$info_pre['data_partenza'].') || ' //
					. ' (data_partenza > '.$info_pre['data_arrivo'].' && data_partenza <= '.$info_pre['data_partenza'].') || ' //
					. ' (data_arrivo < '.$info_pre['data_arrivo'].' && data_partenza > '.$info_pre['data_partenza'].'))');

					// Se il gruppo è stato trovato
					if($id_gruppo_tab = $id_gruppo_grezzo->fetchAll(PDO::FETCH_ASSOC)) {
						$info_pre['id_gruppo'] = $id_gruppo_tab[0]['id'];
						
						// Aggiorniamo entrambi
						$agg_gruppo_old = TRUE;
						$agg_gruppo_nuovo = TRUE;
					}
					// Se il gruppo va creato
					else {
						$agg_gruppo_old = TRUE;
						$crea_gruppo = TRUE;
					}
				}
				// Se la prenotazione è stata sganciata dal gruppo si aggiorna
				else 		$agg_gruppo_old = TRUE;
			}
			
			$azione = 'RESERVATION MODIFIED';
		}
		
		// Se la prenotazione va creata
		else {
			// Se la prenotazione è agganciata a un gruppo
			if($info_pre['nome_gruppo'] != NULL) {
				
				// Cerchiamo il gruppo
					$id_gruppo_grezzo = $db->query('SELECT id FROM gruppi WHERE nome=\'' . $info_pre['nome_gruppo'] . '\' AND ' //
					. '((data_arrivo >= '.$info_pre['data_arrivo'].' && data_arrivo < '.$info_pre['data_partenza'].') || ' //
					. ' (data_partenza > '.$info_pre['data_arrivo'].' && data_partenza <= '.$info_pre['data_partenza'].') || ' //
					. ' (data_arrivo < '.$info_pre['data_arrivo'].' && data_partenza > '.$info_pre['data_partenza'].'))');

				// Se il gruppo è stato trovato gli si da il suo id
				if($id_gruppo_tab = $id_gruppo_grezzo->fetchAll(PDO::FETCH_ASSOC)) {
					$info_pre['id_gruppo'] = $id_gruppo_tab[0]['id'];
					$agg_gruppo_nuovo = TRUE;
				}
				// Si crea il gruppo e così otteniamo l'id
				else 		$crea_gruppo = TRUE;
			}
			
			// Inseriamo una nuova voce nel database prenotazioni per ottenere l'id
			$db->query('INSERT INTO prenotazioni (id_rif) VALUES (0)');
			
			// Recuperiamo l'id della prenotazione
			$info_id_grezza = $db->query('SELECT id FROM prenotazioni ORDER BY id DESC LIMIT 0,1');
		
			$info_id = $info_id_grezza->fetch(PDO::FETCH_ASSOC);
			$info_pre['id'] = $info_id['id'];
			
			$azione = 'RESERVATION ADDED';
		}

		// MULTICAMERA
		for($i = 0 ; isset($_POST['cam_agg_'.$i]) ; $i++) {
			// Se il cambio camera è stato compilato e va inserito
			if($_POST['cam_agg_'.$i] != '') {
				
				// Se la speciale va eliminata
				if(isset($_POST['cam_agg_clx_'.$i])) {
					$id_spe_clx[] = intval($_POST['cam_agg_clx_'.$i]);
					$num_spe_clx++;
				}
				else {
					if(isset($_POST['cam_agg_id_'.$i])) $cam_agg[$num_cam_agg]['id'] = intval($_POST['cam_agg_id_'.$i]);
					else 											$cam_agg[$num_cam_agg]['id'] = NULL;
					
					$cam_agg[$num_cam_agg]['tipo_pre'] = 10+$info_pre['tipo_pre'];
					$cam_agg[$num_cam_agg]['stile_spe'] = 0;
					$cam_agg[$num_cam_agg]['camera'] = intval($_POST['cam_agg_'.$i]);
					$cam_agg[$num_cam_agg]['pax'] = intval($_POST['pax_cam_agg_'.$i]);
					$cam_agg[$num_cam_agg]['vestizione'] = formatta_salvataggio($_POST['vest_cam_agg_'.$i]);
					$cam_agg[$num_cam_agg]['tipologia'] = &$info_pre['tipologia'];
					$cam_agg[$num_cam_agg]['id_rif'] = &$info_pre['id'];
					$cam_agg[$num_cam_agg]['nome'] = &$info_pre['nome'];
					$cam_agg[$num_cam_agg]['nome_gruppo'] = &$info_pre['nome_gruppo'];
					$cam_agg[$num_cam_agg]['id_gruppo'] = &$info_pre['id_gruppo'];
					$cam_agg[$num_cam_agg]['agenzia'] = &$info_pre['agenzia'];
					$cam_agg[$num_cam_agg]['arrangiamento'] = &$info_pre['arrangiamento'];
					$cam_agg[$num_cam_agg]['primo_pasto'] = &$info_pre['primo_pasto'];
					$cam_agg[$num_cam_agg]['ultimo_pasto'] = &$info_pre['ultimo_pasto'];
					$cam_agg[$num_cam_agg]['data_arrivo'] = &$info_pre['data_arrivo'];
					$cam_agg[$num_cam_agg]['data_partenza'] = &$info_pre['data_partenza'];
					$cam_agg[$num_cam_agg]['note'] = &$info_pre['note'];
					$cam_agg[$num_cam_agg]['colore_note'] = &$info_pre['colore_note'];
					$cam_agg[$num_cam_agg]['problemi'] = '';

					$num_cam_agg++;
				}
			}
		}
		
		// CAMBIO CAMERA
		for($i = 0 ; isset($_POST['cam_cambio_cam_'.$i]) ; $i++) {
			// Se il cambio camera è stato compilato e va inserito
			if($_POST['cam_cambio_cam_'.$i] != '') {
				
				// Se la speciale va eliminata
				if(isset($_POST['cambio_cam_clx_'.$i])) {
					$id_spe_clx[] = intval($_POST['cambio_cam_id_'.$i]);
					$num_spe_clx++;
				}
				else {
					if(isset($_POST['cambio_cam_id_'.$i])) $cambio_cam[$num_cambio_cam]['id'] = intval($_POST['cambio_cam_id_'.$i]);
					else 												$cambio_cam[$num_cambio_cam]['id'] = NULL;
					
					$cambio_cam[$num_cambio_cam]['tipo_pre'] = 20+$info_pre['tipo_pre'];
					$cambio_cam[$num_cambio_cam]['stile_spe'] = 0;
					$cambio_cam[$num_cambio_cam]['camera'] = intval($_POST['cam_cambio_cam_'.$i]);
					$cambio_cam[$num_cambio_cam]['data_arrivo'] = controllo_data($_POST['da_cambio_cam_'.$i]);
					$cambio_cam[$num_cambio_cam]['data_partenza'] = controllo_data($_POST['dp_cambio_cam_'.$i]);
					$cambio_cam[$num_cambio_cam]['vestizione'] = formatta_salvataggio($_POST['vest_cambio_cam_'.$i]);
					$cambio_cam[$num_cambio_cam]['id_rif'] = &$info_pre['id'];
					$cambio_cam[$num_cambio_cam]['pax'] = &$info_pre['pax'];
					$cambio_cam[$num_cambio_cam]['nome'] = &$info_pre['nome'];
					$cambio_cam[$num_cambio_cam]['agenzia'] = &$info_pre['agenzia'];
					$cambio_cam[$num_cambio_cam]['nome_gruppo'] = &$info_pre['nome_gruppo'];
					$cambio_cam[$num_cambio_cam]['id_gruppo'] = &$info_pre['id_gruppo'];
					$cambio_cam[$num_cambio_cam]['tipologia'] = &$info_pre['tipologia'];
					$cambio_cam[$num_cambio_cam]['arrangiamento'] = &$info_pre['arrangiamento'];
					$cambio_cam[$num_cambio_cam]['primo_pasto'] = &$info_pre['primo_pasto'];
					$cambio_cam[$num_cambio_cam]['ultimo_pasto'] = &$info_pre['ultimo_pasto'];
					$cambio_cam[$num_cambio_cam]['note'] = &$info_pre['note'];
					$cambio_cam[$num_cambio_cam]['colore_note'] = &$info_pre['colore_note'];
					$cambio_cam[$num_cambio_cam]['problemi'] = '';
					
					$num_cambio_cam++;
				}
			}
		}
		
		// SHARE
		for($i = 0 ; isset($_POST['nome_share_'.$i]) ; $i++) {
			// Se il cambio camera è stato compilato e va inserito
			if($_POST['nome_share_'.$i] != '') {
				
				// Se la speciale va eliminata
				if(isset($_POST['share_clx_'.$i])) {
					$id_spe_clx[] = intval($_POST['share_id_'.$i]);
					$num_spe_clx++;
				}
				else {
					if(isset($_POST['share_id_'.$i])) {
						$share[$num_share]['id'] = intval($_POST['share_id_'.$i]);
					}
					else $share[$num_share]['id'] = NULL;
					
					$share[$num_share]['tipo_pre'] = 30+$info_pre['tipo_pre'];
					$share[$num_share]['stile_spe'] = 0;
					$share[$num_share]['nome'] = formatta_salvataggio($_POST['nome_share_'.$i]);
					$share[$num_share]['data_arrivo'] = controllo_data($_POST['da_share_'.$i]);
					$share[$num_share]['data_partenza'] = controllo_data($_POST['dp_share_'.$i]);
					$share[$num_share]['pax'] = intval($_POST['pax_share_'.$i]);
					$share[$num_share]['arrangiamento'] = formatta_salvataggio($_POST['arrangiamento_share_'.$i]);
					$share[$num_share]['primo_pasto'] = intval($_POST['primo_pasto_share_'.$i]);
					$share[$num_share]['ultimo_pasto'] = intval($_POST['ultimo_pasto_share_'.$i]);
					$share[$num_share]['ultimo_pasto'] = intval($_POST['ultimo_pasto_share_'.$i]);
					
					$share[$num_share]['id_rif'] = &$info_pre['id'];
					$share[$num_share]['camera'] = &$info_pre['camera'];
					$share[$num_share]['vestizione'] = &$info_pre['vestizione'];
					$share[$num_share]['agenzia'] = &$info_pre['agenzia'];
					$share[$num_share]['nome_gruppo'] = &$info_pre['nome_gruppo'];
					$share[$num_share]['id_gruppo'] = &$info_pre['id_gruppo'];
					$share[$num_share]['tipologia'] = &$info_pre['tipologia'];
					$share[$num_share]['note'] = &$info_pre['note'];
					$share[$num_share]['colore_note'] = &$info_pre['colore_note'];
					$share[$num_share]['problemi'] = '';
					$num_share++;
				}
			}
		}

		// Eliminiamo le speciali cancellate
		if($num_spe_clx > 0) {
			$sql_delete = 'DELETE FROM prenotazioni WHERE id IN ('.$id_spe_clx[0];
			for($i = 1 ; $i < $num_spe_clx ; $i++) {
				$sql_delete .= ','.$id_spe_clx[$i];
			}
			$sql_delete .= ')';
			
			$db->query($sql_delete);
		}
		
		// Aggiorniamo i possibili problemi
		// Inseriamo tutti i dati in un unico array
		$pre_spe[0] = &$info_pre;
		$num_voci = 1;
		for($i = 0 ; $i < $num_cam_agg ; $i++) 	{ $pre_spe[$num_voci] = &$cam_agg[$i]; $num_voci++; }
		for($i = 0 ; $i < $num_cambio_cam ; $i++) { $pre_spe[$num_voci] = &$cambio_cam[$i]; $num_voci++; }
		for($i = 0 ; $i < $num_share ; $i++)		{ $pre_spe[$num_voci] = &$share[$i]; $num_voci++; }
		
		// Inseriamo una nuova voce nel database prenotazioni per ottenere l'id
		$num_spe = $num_cam_agg + $num_cambio_cam + $num_share;
		if($num_spe > 0) {
			$sql_id_spe_insert = 'INSERT INTO prenotazioni (id_rif) VALUES';
			for($i = 1, $num_spe_insert = 0 ; $i < $num_voci ; $i++) {
				if($pre_spe[$i]['id'] == NULL) {
					if($num_spe_insert > 0) $sql_id_spe_insert .= ',';
					$sql_id_spe_insert .= '('.$info_pre['id'].')';
					
					$num_spe_insert++;
				}
			}
			
			// Se ci sono delle speciali che hanno bisogno dell'id
			if($num_spe_insert > 0) {
				$sql_id_spe_insert .= ';';
	
				$request = $db->query($sql_id_spe_insert);
	
				$spe_id_grezza = $db->query('SELECT id FROM prenotazioni WHERE id_rif='.$info_pre['id'].' ORDER BY id DESC LIMIT 0,'.$num_spe_insert);
				
				// Ad ogni spe diamo un id
				for($i = 1 ; $i < $num_voci ; $i++) {
					if($pre_spe[$i]['id'] == NULL) {
						$spe_id = $spe_id_grezza->fetch(PDO::FETCH_ASSOC);
						$pre_spe[$i]['id'] = $spe_id['id'];
					}
				}
			}
		}
		
		controllo_pre($pre_spe);
		$stampa_problemi = print_problemi($pre_spe, $pagina);
		
		// Inseriamo le righe vuote
		$tot_cam_agg += $num_cam_agg;
		$tot_cambio_cam += $num_cambio_cam;
		$tot_share += $num_share;
	}
	
	$db->connection = NULL;
	
	
	// Formattiamo i possibili problemi
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head><?php
	echo '<title>';
	if(isset($_GET['mp']) || isset($_POST['nome']))  echo formatta_visualizzazione($info_pre['nome']) . ' - MODIFICA PRENOTAZIONE';
	else 							echo 'NUOVA PRENOTAZIONE';
	echo '</title>';
			
	header_standard();
?></head>
<body style="min-height:1000px"><?php
		$testo_menu_top = ' | <a href="lista_pre.php">LISTA PRENOTAZIONI</a>';
		menu_top($testo_menu_top); ?>

		<div id="corpo_a"><?php

		if($modifica_pre == TRUE) echo '<div class="cont_dx">';

		if($azione != '' || $stampa_problemi != '') {
			
			echo '<div class="form1_650';
			
			if($azione == 'RESERVATION ADDED' || $azione == 'RESERVATION MODIFIED') echo  ' form_green';
			else	 echo  ' form_red';
			echo '">';
			
			
			switch($azione) {
				case 'RESERVATION ADDED':
				$titolo_operazione = '<p class="titolo">PRENOTAZIONE AGGIUNTA<a class="bottone guarda_b" href="prenotazioni.php?id_pre='.$info_pre['id'].'">GUARDA A BOOKING</a></p>';
				$contenuto_operazione = '';
				break;
	
				case 'RESERVATION MODIFIED':
				$titolo_operazione = '<p class="titolo">PRENOTAZIONE MODIFICATA<a class="bottone guarda_b" href="prenotazioni.php?id_pre='.$info_pre['id'].'">GUARDA A BOOKING</a></p>';
				$contenuto_operazione = '';
				break;
	
				case 'NOT FOUND':
				$titolo_operazione = '<p class="titolo">PRENOTAZIONE NON TROVATA</p>';
				$contenuto_operazione = '';
				break;
	
				case '':
				$titolo_operazione = '<p class="titolo">PROBLEMI</p>';
				$contenuto_operazione = '';
				break;
			}
			
			echo $titolo_operazione;
			
			if($contenuto_operazione != '') echo '<div class="cont_op">' . $contenuto_operazione . '</div>';
			if($stampa_problemi != '') echo $stampa_problemi;
			echo '</div>';
		}
		
		?>

	    	<form name="dati" action="ge_pre.php" method="post" enctype="multipart/form-data">
				<div class="form1_650 ge_pre"><?php
			
			echo '<p class="titolo">';
				if($modifica_pre == TRUE)  echo 'MODIFICA PRENOTAZIONE';
				else 								echo 'NUOVA PRENOTAZIONE';
			
			echo '</p>';
					?><table>
						<tr><td colspan="4"><input placeholder="NOME PRENOTAZIONE" type="text" name="nome" class="field" value="<?php echo formatta_visualizzazione($info_pre['nome']) ?>" autofocus /></td></tr>
						<tr>
							<td colspan="2"><input placeholder="NOME GRUPPO" type="text" name="gruppo" class="field" value="<?php echo formatta_visualizzazione($info_pre['nome_gruppo']) ?>" /></td><?php
							
							echo '<td colspan="2"><input placeholder="NOME AGENZIA" type="text" name="agenzia" class="field" ';
							echo 'value="'.formatta_visualizzazione($info_pre['agenzia']).'" /></td>';
						
						
						?></tr>
						
						<tr><?php
							echo '<td>';
							echo '<select name="tipo_pre">';
							echo '<option '; if($info_pre['tipo_pre'] == 0) echo 'selected="selected" '; echo 'value="0">CF</option>';
							echo '<option '; if($info_pre['tipo_pre'] == 1) echo 'selected="selected" '; echo 'value="1">OP</option>';
							echo '</select>';
							echo '</td>';
							?><td><input placeholder="DAL" type="text" name="data_arrivo" class="field" value="<?php if($info_pre["data_arrivo"] != NULL) echo date('d/m/Y', $info_pre['data_arrivo']); ?>" /></td>
							<td><input placeholder="AL" type="text" name="data_partenza" class="field" value="<?php if($info_pre["data_partenza"] != NULL) echo date('d/m/Y', $info_pre['data_partenza']); ?>" /></td>
							<td><input placeholder="VESTIZIONE" type="text" name="vestizione" class="field" value="<?php echo $info_pre["vestizione"] ?>" /></td>
						</tr>
						
						<tr>
							<td><input placeholder="PAX" type="text" name="pax" class="field" value="<?php echo $info_pre["pax"] ?>" /></td>
							<td><input placeholder="CAMERA" type="text" name="camera" class="field" value="<?php echo $info_pre["camera"] ?>" /></td>
							<td><input placeholder="TIPOLOGIA" type="text" name="tipologia" class="field" value="<?php echo $info_pre["tipologia"] ?>" /></td> <?php
							echo ' <td>';
							echo '<select name="arrangiamento">';
							echo '<option '; if($info_pre['arrangiamento'] == 'HB' || $info_pre['arrangiamento'] == '') echo 'selected="selected" '; echo 'value="HB">HB</option>';
							echo '<option '; if($info_pre['arrangiamento'] == 'BB') echo 'selected="selected" '; echo 'value="BB">BB</option>';
							echo '<option '; if($info_pre['arrangiamento'] == 'FB') echo 'selected="selected" '; echo 'value="FB">FB</option>';
							echo '<option '; if($info_pre['arrangiamento'] == 'RS') echo 'selected="selected" '; echo 'value="RS">RS</option>';
							echo '</select> ';
							echo '<select name="primo_pasto">';
							echo '<option '; if($info_pre['primo_pasto'] == 1) echo 'selected="selected" '; echo 'value="1">B</option>';
							echo '<option '; if($info_pre['primo_pasto'] == 2) echo 'selected="selected" '; echo 'value="2">L</option>';
							echo '<option '; if($info_pre['primo_pasto'] == 3) echo 'selected="selected" '; echo 'value="3">D</option>';
							echo '</select> ';
							echo '<select name="ultimo_pasto">';
							echo '<option '; if($info_pre['ultimo_pasto'] == 1) echo 'selected="selected" '; echo 'value="1">B</option>';
							echo '<option '; if($info_pre['ultimo_pasto'] == 2) echo 'selected="selected" '; echo 'value="2">L</option>';
							echo '<option '; if($info_pre['ultimo_pasto'] == 3) echo 'selected="selected" '; echo 'value="3">D</option>';
							echo '</select>';
							echo '</td>';
						?></tr>
						<tr>
							<td colspan="4"><textarea placeholder="NOTE" name="note"><?php echo formatta_visualizzazione($info_pre["note"]) ?></textarea></td>
						</tr>
						<tr>
							<td><label>COLORE NOTE</label></td>
							<td colspan="3"><?php
								echo lista_colori_note($info_pre['colore_note']);
							?></td>
						</tr>
					</table>
					
					<div class="pulsanti_bottom"><?php
					
						// Se stiamo operando su una prenotazione già creata
						if($modifica_pre == TRUE) {
							echo '<p class="data_modifica">ultima modifica :<br />' .date('d/m/Y, H:i', $info_pre['data_ultima_modifica']) . '</p>';
							
							echo '<input type="hidden" name="id_prenotazione" value="'.$info_pre['id'].'" />';
							echo '<input type="hidden" name="colore_gruppo" value="'.$info_pre['colore_gruppo'].'" />';
		
							if(isset($_POST['modifica']) || isset($_GET['mp']))
								echo '<input type="hidden" name="old_gruppo" value="'.$info_pre['nome_gruppo'].'" />';
							
							echo '<a class="bottone bottone_r" href="lista_pre.php?clx_privato='.$info_pre['id'].'">ELIMINA</a> ';
							echo '<a class="bottone" href="menu.php">ANNULLA</a> ';
							// Se ci sono speciali non si visualizza il bottone duplica per evitare errori
							if($num_spe == 0) echo '<input class="bottone" name="duplica" type="submit" value="DUPLICA" /> ';
							echo '<input class="bottone" name="modifica" type="submit" value="MODIFICA" />';
						}
						else {
							echo '<a class="bottone" href="menu.php">ANNULLA</a> ';
							echo '<input class="bottone" name="modifica" type="submit" value="INSERISCI" />';
						}
						
					echo'</div></div>';
					
					// Inseriamo i campi per camere multiple/cambio camera/share
					if($modifica_pre == TRUE) {
						echo '<div class="form1_650 ge_pre">';
						echo '<p class="titolo">';
						if($num_spe > 0) echo '<span class="num_spe">'.$num_spe.'</span>';
						echo 'TIPOLOGIE SPECIALI</p>';
						
						// Se non ci sono spe inserite le tabelle sono nascoste di default
						if($num_spe == 0) echo '<div class="show_spe">';
						
						echo '<p class="sottotitolo">CAMERE AGGIUNTIVE</p>';
						echo '<table>';
						for($i = 0 ; $i < $tot_cam_agg ; $i++) {
							
							echo '<tr><td><span class="monospace_style_dx">'.($i+1).'</span></td>';
							
							// Se esiste già il cambio camera in questa riga
							if($i < $num_cam_agg) {
								echo '<td><input type="text" class="field" placeholder="NUMERO CAMERA" value="'.$cam_agg[$i]['camera'].'" name="cam_agg_'.$i.'" /></td>'//
								  .'<td class="small_tab1"><input type="text" class="field" placeholder="PAX" value="'.$cam_agg[$i]['pax'].'" name="pax_cam_agg_'.$i.'" /></td>'//
								  .'<td><input type="text" class="field" placeholder="VESTIZIONE" value="'.formatta_visualizzazione($cam_agg[$i]['vestizione']).'" name="vest_cam_agg_'.$i.'" /></td>'//
								  .'<td><label class="clx_spe" for="cam_agg_clx_'.$i.'">CLX</label><input type="checkbox" name="cam_agg_clx_'.$i.'" id="cam_agg_clx_'.$i.'" value="'.$cam_agg[$i]['id'].'" /></td>'//
								  .'<input type="hidden" name="cam_agg_id_'.$i.'" value="'.$cam_agg[$i]['id'].'" />';
							}
							else {
								echo '<td><input type="text" class="field" placeholder="NUMERO CAMERA" name="cam_agg_'.$i.'" /></td>'//
								  .'<td class="small_tab1"><input type="text" class="field" placeholder="PAX" name="pax_cam_agg_'.$i.'" /></td>'//
								  .'<td><input type="text" class="field" placeholder="VESTIZIONE" name="vest_cam_agg_'.$i.'" /></td>';
							}
							echo '</tr>';
						}
						echo '</table>';
						
						echo '<p class="sottotitolo">CAMBIO CAMERA</p>';
						echo '<table>';
						for($i = 0 ; $i < $tot_cambio_cam ; $i++) {
							echo '<tr><td><span class="monospace_style_dx">'.($i+1).'</span></td>';
							
							if($i < $num_cambio_cam) {
								echo  '<td class="date_ge_gru"><input type="text" class="field" placeholder="DAL" value="'.date('d/m/Y', $cambio_cam[$i]['data_arrivo']).'" name="da_cambio_cam_'.$i.'" /></td>'//
									  .'<td class="date_ge_gru"><input type="text" class="field" placeholder="AL" value="'.date('d/m/Y', $cambio_cam[$i]['data_partenza']).'" name="dp_cambio_cam_'.$i.'" /></td>'//
									  .'<td><input type="text" class="field" placeholder="CAMERA" value="'.$cambio_cam[$i]['camera'].'" name="cam_cambio_cam_'.$i.'" /></td>'//
									  .'<td><input type="text" class="field" placeholder="VESTIZIONE" value="'.formatta_visualizzazione($cambio_cam[$i]['vestizione']).'" name="vest_cambio_cam_'.$i.'" /></td>'//
								  	  .'<td><label class="clx_spe" for="cambio_cam_clx_'.$i.'">CLX</label><input type="checkbox" name="cambio_cam_clx_'.$i.'" id="cambio_cam_clx_'.$i.'" value="'.$cambio_cam[$i]['id'].'" /></td>'//
								 	  .'<input type="hidden" name="cambio_cam_id_'.$i.'" value="'.$cambio_cam[$i]['id'].'" />';
							}
							else {
								echo  '<td class="date_ge_gru"><input type="text" class="field" placeholder="DAL" name="da_cambio_cam_'.$i.'" /></td>'//
									  .'<td class="date_ge_gru"><input type="text" class="field" placeholder="AL" name="dp_cambio_cam_'.$i.'" /></td>'//
									  .'<td><input type="text" class="field" placeholder="CAMERA" name="cam_cambio_cam_'.$i.'" /></td>'//
									  .'<td><input type="text" class="field" placeholder="VESTIZIONE" name="vest_cambio_cam_'.$i.'" /></td>';
							}
							echo '</tr>';
						}
						echo '</table>';
						
						echo '<p class="sottotitolo">SHARE</p>';
						echo '<table>';
						for($i = 0 ; $i < $tot_share ; $i++) {
							echo '<tr><td><span class="monospace_style_dx">'.($i+1).'</span></td>';
							
							if($i < $num_share) {
								echo '<td class="nome_share"><input type="text" class="field" placeholder="NOME" value="'.formatta_visualizzazione($share[$i]['nome']).'" name="nome_share_'.$i.'" /></td>'//
									  .'<td class="date_ge_gru"><input type="text" class="field" placeholder="DAL" value="'.date('d/m/Y', $share[$i]['data_arrivo']).'" name="da_share_'.$i.'" /></td>'//
									  .'<td class="date_ge_gru"><input type="text" class="field" placeholder="AL" value="'.date('d/m/Y', $share[$i]['data_partenza']).'" name="dp_share_'.$i.'" /></td>'//
									  .'<td class="small_tab1"><input type="text" class="field" placeholder="PAX" value="'.$share[$i]['pax'].'" name="pax_share_'.$i.'" /></td>';
								
								echo ' <td>';
								echo '<select name="arrangiamento_share_'.$i.'">';
								echo '<option '; if($share[$i]['arrangiamento'] == 'HB' || $share[$i]['arrangiamento'] == '') echo 'selected="selected" '; echo 'value="HB">HB</option>';
								echo '<option '; if($share[$i]['arrangiamento'] == 'BB') echo 'selected="selected" '; echo 'value="BB">BB</option>';
								echo '<option '; if($share[$i]['arrangiamento'] == 'FB') echo 'selected="selected" '; echo 'value="FB">FB</option>';
								echo '<option '; if($share[$i]['arrangiamento'] == 'RS') echo 'selected="selected" '; echo 'value="RS">RS</option>';
								echo '</select> ';
								echo '<select name="primo_pasto_share_'.$i.'">';
								echo '<option '; if($share[$i]['primo_pasto'] == 1) echo 'selected="selected" '; echo 'value="1">B</option>';
								echo '<option '; if($share[$i]['primo_pasto'] == 2) echo 'selected="selected" '; echo 'value="2">L</option>';
								echo '<option '; if($share[$i]['primo_pasto'] == 3) echo 'selected="selected" '; echo 'value="3">D</option>';
								echo '</select> ';
								echo '<select name="ultimo_pasto_share_'.$i.'">';
								echo '<option '; if($share[$i]['ultimo_pasto'] == 1) echo 'selected="selected" '; echo 'value="1">B</option>';
								echo '<option '; if($share[$i]['ultimo_pasto'] == 2) echo 'selected="selected" '; echo 'value="2">L</option>';
								echo '<option '; if($share[$i]['ultimo_pasto'] == 3) echo 'selected="selected" '; echo 'value="3">D</option>';
								echo '</select>';
								echo '</td>';
								echo '<td><label class="clx_spe" for="share_clx_'.$i.'">CLX</label><input type="checkbox" name="share_clx_'.$i.'" id="share_clx_'.$i.'" value="'.$share[$i]['id'].'" /></td>';
								echo '<input type="hidden" name="share_id_'.$i.'" value="'.$share[$i]['id'].'" />';
							}
							
							else {
								echo '<td><input type="text" class="field" placeholder="NOME" name="nome_share_'.$i.'" /></td>'//
									  .'<td class="date_ge_gru"><input type="text" class="field" placeholder="DAL" name="da_share_'.$i.'" /></td>'//
									  .'<td class="date_ge_gru"><input type="text" class="field" placeholder="AL" name="dp_share_'.$i.'" /></td>'//
									  .'<td class="small_tab1"><input type="text" class="field" placeholder="PAX" name="pax_share_'.$i.'" /></td>';
								
								echo ' <td>';
								echo '<select name="arrangiamento_share_'.$i.'">';
								echo '<option '; if($info_pre['arrangiamento'] == 'HB' || $info_pre['arrangiamento'] == '') echo 'selected="selected" '; echo 'value="HB">HB</option>';
								echo '<option '; if($info_pre['arrangiamento'] == 'BB') echo 'selected="selected" '; echo 'value="BB">BB</option>';
								echo '<option '; if($info_pre['arrangiamento'] == 'FB') echo 'selected="selected" '; echo 'value="FB">FB</option>';
								echo '<option '; if($info_pre['arrangiamento'] == 'RS') echo 'selected="selected" '; echo 'value="RS">RS</option>';
								echo '</select> ';
								echo '<select name="primo_pasto_share_'.$i.'">';
								echo '<option '; if($info_pre['primo_pasto'] == 1) echo 'selected="selected" '; echo 'value="1">B</option>';
								echo '<option '; if($info_pre['primo_pasto'] == 2) echo 'selected="selected" '; echo 'value="2">L</option>';
								echo '<option '; if($info_pre['primo_pasto'] == 3) echo 'selected="selected" '; echo 'value="3">D</option>';
								echo '</select> ';
								echo '<select name="ultimo_pasto_share_'.$i.'">';
								echo '<option '; if($info_pre['ultimo_pasto'] == 1) echo 'selected="selected" '; echo 'value="1">B</option>';
								echo '<option '; if($info_pre['ultimo_pasto'] == 2) echo 'selected="selected" '; echo 'value="2">L</option>';
								echo '<option '; if($info_pre['ultimo_pasto'] == 3) echo 'selected="selected" '; echo 'value="3">D</option>';
								echo '</select>';
								echo '</td>';
							}
							
							echo '</tr>';
						}
						echo '</table>';
						
						if($num_spe == 0) echo '</div>';
						
						echo '</div>';
					}
					
					echo '</form>';
			
					if($modifica_pre == TRUE) {
						echo '</div>'; // fine cont_dx
						
						// Aggiorniamo tutti i dati necessari alla colonna di sinistra
						if($azione == 'RESERVATION ADDED' || $azione == 'RESERVATION MODIFIED') {
							
							// Modifica per referenza anche id_gruppo della prenotazione
							if($crea_gruppo) {
								$info_pre['colore_gruppo'] = aggiornamento_gruppo($info_pre['id_gruppo'], $pre_spe, 'ge_pre');
							}
							
							set_stile_pre($pre_spe);
							
							// Creamo un array con tutte le speciali
							$spe = array();
							for($i = 0 ; $i < $num_cam_agg ; $i++) 	 $spe[] = &$cam_agg[$i];
							for($i = 0 ; $i < $num_cambio_cam ; $i++)  $spe[] = &$cambio_cam[$i];
							for($i = 0 ; $i < $num_share ; $i++)		 $spe[] = &$share[$i];
							
							// Inseriamo le camere aggiuntive nelle query
							for($i = 0 ; $i < $num_spe ; $i++) {
								// Aggiorniamo i dati della speciali
								$spe_update .= ' UNION SELECT '.$spe[$i]['id'].', '.$info_pre['id'].', '.$spe[$i]['tipo_pre'].', '.$spe[$i]['stile_spe'].',' //
												. $spe[$i]['camera'].', \''.$spe[$i]['nome'].'\',' //
												. '\''.$spe[$i]['agenzia'].'\', ' //
												. $info_pre['id_gruppo'].', ' //
												. '\''.$spe[$i]['vestizione'].'\', \''.$spe[$i]['tipologia'].'\',' //
												. $spe[$i]['pax'].', \''.$spe[$i]['arrangiamento'].'\',' //
												. $spe[$i]['primo_pasto'].', '.$spe[$i]['ultimo_pasto'].',' //
												. $spe[$i]['data_arrivo'].', '.$spe[$i]['data_partenza'].',' //
												. '\''.$spe[$i]['note'].'\', '.$spe[$i]['colore_note'].',' //
												. '\''. $spe[$i]['problemi'].'\', '.$info_pre['data_ultima_modifica'];
							}
					
							// Inseriamo la prenotazione tra le prenotazioni da aggiornare
							$pre_update = ' SELECT '.$info_pre['id'].' id, 0 id_rif, '.$info_pre['tipo_pre'].' tipo_pre, '.$info_pre['stile_spe'].' stile_spe,' //
											. $info_pre['camera'].' camera, \''.$info_pre['nome'].'\' nome,' //
											. '\''.$info_pre['agenzia'].'\' agenzia, ' //
											. $info_pre['id_gruppo'].' gruppo, ' //
											. '\''.$info_pre['vestizione'].'\' vestizione, \''.$info_pre['tipologia'].'\' tipologia,' //
											. $info_pre['pax'].' pax, \''.$info_pre['arrangiamento'].'\' arrangiamento,' //
											. $info_pre['primo_pasto'].' primo_pasto, '.$info_pre['ultimo_pasto'].' ultimo_pasto,' //
											. $info_pre['data_arrivo'].' data_arrivo, '.$info_pre['data_partenza'].' data_partenza,' //
											. '\''.$info_pre['note'].'\' note, '.$info_pre['colore_note'].' colore_note,' //
											. '\''.$info_pre['problemi'].'\' problemi, '.$info_pre['data_ultima_modifica'].' data_ultima_modifica';
							
							$sql_update =  'UPDATE prenotazioni A inner join(' . $pre_update . $spe_update . ') B USING (id) SET A.id_rif = B.id_rif,' //
												. 'A.tipo_pre = B.tipo_pre, A.stile_spe = B.stile_spe, A.camera = B.camera, A.nome = B.nome,' //
												. 'A.agenzia = B.agenzia, A.gruppo = B.gruppo, A.vestizione = B.vestizione,' //
												. 'A.tipologia = B.tipologia, A.pax = B.pax, A.arrangiamento = B.arrangiamento, ' //
												. 'A.primo_pasto = B.primo_pasto, A.ultimo_pasto = B.ultimo_pasto, A.data_arrivo = B.data_arrivo,' //
												. 'A.data_partenza = B.data_partenza, A.note = B.note, A.colore_note = B.colore_note,' //
												. 'A.problemi = B.problemi, A.data_ultima_modifica = B.data_ultima_modifica' //
												. ';';
							
							$db = db_connect();
							$db->query($sql_update);
							$db->connection = NULL;
							
						}
						
						if($agg_gruppo_old)	 aggiornamento_gruppo($id_gruppo_vecchio);
						if($agg_gruppo_nuovo) aggiornamento_gruppo($info_pre['id_gruppo']);
						
						// Settiamo la colonna di sinistra
						echo '<div class="colonna_sx">';
						echo '<div class="sx_info_pre">';
						echo formatta_periodo($info_pre['data_arrivo'], $info_pre['data_partenza']);
						echo '<div class="sx_num_notti">'.num_notti($info_pre['data_arrivo'], $info_pre['data_partenza']).' NOTTI</div>';
						
						// Se la prenotazione è legata a un gruppo
						if($info_pre['nome_gruppo'] != '') {
							echo '<a class="sx_gruppo c_'.$info_pre['colore_gruppo'].'" href="ge_gru.php?mg='.$info_pre['id_gruppo'].'">';
							echo formatta_visualizzazione($info_pre['nome_gruppo']);
							echo '</a>';
						}
						
						// Chiudiamo sx_info_pre
						echo '</div>';
						
						// Recuperiamo le camere disponibili
						$cam_dispo = camere_disponibili($info_pre['data_arrivo'], $info_pre['data_partenza']);
						
						$print_cam_dispo = '';
						$piano_cam = 0;
						$piano_pax = 0;
						
						
						// Stampiamo le camere disponibili
						$num_righe = count($cam_dispo) - 1; // Una riga è per i totali
							
						echo '<div class="liste_sx">';
						
						echo '<table class="lista_sx">';
						echo '<tr class="testa"><th class="titolo_lista_sx" colspan="3">CAMERE DISPONIBILI</th></tr>';
						
						for($i = 0 ; $i < $num_righe ; $i++) {
							if($i == 0)
								echo '<tr class="testa"><th class="titolo_lista_sx">'.$cam_dispo[$i]["piano"].' PIANO</th><th>NUM</th><th>PAX</th></tr>';
							
							elseif($cam_dispo[$i-1]['piano'] != $cam_dispo[$i]['piano']) {
								// Stampiamo i subtotali del piano
								echo '<tr class="riga"><td>TOTIALI PIANO</td><td>'.$piano_cam.'</td><td>'.$piano_pax.'</td></tr>';
								echo '<tr class="testa"><th class="titolo_lista_sx">'.$cam_dispo[$i]["piano"].' PIANO</th><th>NUM</th><th>PAX</th></tr>';
								
								$piano_cam = 0; // Resettiamo i totali del piano
								$piano_pax = 0;
							}
							
							echo '<tr class="riga">';
							
							echo '<td class="tipo per_comparsa">';
							if($cam_dispo[$i]['vestizione'] == '') echo '?';
							else 												echo $cam_dispo[$i]['vestizione'];
							if($cam_dispo[$i]['tipologia'] != '')  echo '+' . $cam_dispo[$i]['tipologia'];
							// Inseriamo i numeri delle camere a comparsa
							echo '<span class="a_comparsa">'.$cam_dispo[$i]["numeri_camere"].'</tspan>';
							echo '</td>';
							
							echo '<td>' . $cam_dispo[$i]['camere'] . '</td>';
							echo '<td>' . $cam_dispo[$i]['pax'] . '</td>';
							echo '</tr>';
							
							// Aggiorniamo i sub totali del piano
							$piano_cam += $cam_dispo[$i]["camere"];
							$piano_pax += $cam_dispo[$i]["pax"];
							
							
							// Se siamo all'ultima riga stampiamo anche i subtotali piano
							if($i == $num_righe -1) echo '<tr class="riga"><td>TOTIALI PIANO</td><td>'.$piano_cam.'</td><td>'.$piano_pax.'</td></tr>';
						}
						
						// Stampiamo i totali
						if($num_righe > 0) {
							echo '<tr class="totali"><td class="tipo">TOTALI</td><td>' . $cam_dispo["totali"]["camere"] . '</td>';
							echo '<td>' . $cam_dispo["totali"]["pax"] . '</td>';
							echo '</tr>';
						}
						
						echo '</table>';
						
						echo '</div>';
					}
		echo '</div>'; // Fine corpo
		
	echo '</body></html>';
	
	if($modifica_pre == FALSE) {
		if($crea_gruppo) aggiornamento_gruppo($info_pre['id_gruppo'], $pre_spe);
		if($agg_gruppo_old)	 aggiornamento_gruppo($id_gruppo_vecchio);
		if($agg_gruppo_nuovo) aggiornamento_gruppo($info_pre['id_gruppo']);
	}

} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>