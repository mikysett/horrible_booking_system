<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore
	$errori = '';
	$data = time();
	$movimenti = '';
	
	$giorni = array('DOMENICA','LUNEDI','MARTEDI','MERCOLEDI','GIOVEDI','VENERDI','SABATO');
	
	$condizioni = '';
	$order_by = '';
	$gruppo_attuale['id'] = NULL;
	$gruppo_attuale['nome'] = '';
	$gruppo_attuale['tot_cam'] = 0;
	$gruppo_attuale['tot_pax'] = 0;
	$gruppo_attuale['list_cam'] = 0;
	$gruppo_attuale['list_pax'] = 0;
	
	$tot_cam = 0;
	$tot_pax = 0;
	
	$show_arrivo = TRUE;
	$show_partenza = TRUE;
	$show_gruppo = TRUE;
	$prima_tab_privati = TRUE;
	
	// Controlliamo la validità delle date
	$data_arrivo = controllo_data($_POST['data_arrivo']);
	$data_partenza = controllo_data($_POST['data_partenza']);
	$agenzia_sel = formatta_salvataggio($_POST['agenzia']);
	
	// Creamo la tabella delle date
	if($data_partenza == NULL && $data_partenza < $data_arrivo) $data_partenza = $data_arrivo;
	$date = array();
	$date[] = $data_arrivo;
	for($i = 0 ; $date[$i] < $data_partenza ; $i++) {
		$date[$i+1] = strtotime('+1 day', $date[$i]);
	}
	$num_date = count($date);
	$data_sel = 0;
	
	// Recuperiamo i dati inseriti e parametriamo la query
	$tipo_lista = $_POST['tipo_lista'];
	$ordine_lista = $_POST['ordine_lista'];
	
	// Capiamo qual'è la lista che si vuole
	if($tipo_lista == 'arrivi')		 $tipo_data = 'prenotazioni.data_arrivo';
	elseif($tipo_lista == 'partenze') $tipo_data = 'prenotazioni.data_partenza';
	elseif($tipo_lista == 'in_casa')  $tipo_data = 'prenotazioni.data_arrivo';
	
	// Settiamo l'ordine
	if($ordine_lista == 'nome')			$order_by = $tipo_data.', prenotazioni.nome, prenotazioni.pax';
	elseif($ordine_lista == 'camera')	$order_by = $tipo_data.', prenotazioni.camera, prenotazioni.nome';
	elseif($ordine_lista == 'gruppo') {
		$order_by = $tipo_data.', prenotazioni.gruppo, prenotazioni.nome';
		$show_gruppo = FALSE;
	}
	
	if($tipo_lista == 'arrivi' || $tipo_lista == 'partenze') {
		if($data_arrivo != NULL) {
			$condizioni = 'WHERE '.$tipo_data.' >= '.$data_arrivo.' AND '.$tipo_data.' <= '.$data_partenza;
		}
		else {
			$errori = 'ERRORE DATE';
		}
		if($tipo_lista == 'arrivi')	 	 { $show_arrivo = FALSE; $data_test = 'data_arrivo'; }
		elseif($tipo_lista == 'partenze') { $show_partenza = FALSE; $data_test = 'data_partenza'; }
	}
	
	elseif($tipo_lista == 'in_casa') {
		if($data_arrivo != NULL) {
			$condizioni = 'WHERE (prenotazioni.data_arrivo <= '.$data_arrivo.' AND prenotazioni.data_partenza >= '.$data_partenza.')';
		}
		else {
			$errori = 'ERRORE DATE';
		}
	}
	
	// Se si è scelto di visualizzare gli arrivi per una singola agenzia
	if($agenzia_sel != '') {
		$condizioni .= ' AND prenotazioni.agenzia="'.$agenzia_sel.'"';
	}
	
	$db = db_connect();

try {
 	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	$dati_pre = $db->query('SELECT prenotazioni.*,gruppi.colore AS colore_gruppo,gruppi.nome AS nome_gruppo, gruppi.note AS note_gruppo, totale_pax, totale_camere'//
									.' FROM prenotazioni LEFT JOIN gruppi ON prenotazioni.gruppo=gruppi.id '.$condizioni.' AND id_rif=0' //
									.' ORDER BY '.$order_by);
}
catch(Exception $e) {
	echo 'Exception -> ';
 	var_dump($e->getMessage());
}

	$pre = $dati_pre->fetchAll(PDO::FETCH_ASSOC);
	$num_pre = count($pre);
	
	$sql_spe = '';
	// Recuperiamo tutte le speciali correlate alle principali
	for($i = 0, $prima_spe = TRUE ; $i < $num_pre ; $i++) {
		if($pre[$i]['stile_spe'] != 0) {
			if($prima_spe == FALSE) $sql_spe .= ' OR ';
			else 							$prima_spe = FALSE;
			$sql_spe .= 'id_rif='.$pre[$i]['id'];
		}
	}
	if($sql_spe != '') {
		$dati_spe = $db->query('SELECT * FROM prenotazioni WHERE '.$sql_spe.' ORDER BY tipo_pre, data_arrivo');
		$spe = $dati_spe->fetchAll(PDO::FETCH_ASSOC);
		$num_spe = count($spe);
	}
	else $num_spe = 0;
	
	// Recuperiamo i cambi camere e gli share del periodo
	// A differenza delle spe di prima queste sono autonome e vengono segnalate in tabelle separate
	$dati_mov_spe = $db->query('SELECT prenotazioni.*,gruppi.colore AS colore_gruppo,gruppi.nome AS nome_gruppo, gruppi.note AS note_gruppo, totale_pax, totale_camere'//
									.' FROM prenotazioni LEFT JOIN gruppi ON prenotazioni.gruppo=gruppi.id '.$condizioni.' AND id_rif>0' //
									.' ORDER BY '.$order_by);
	
	$mov_spe = $dati_mov_spe->fetchAll(PDO::FETCH_ASSOC);
	$num_mov_spe = count($mov_spe);
	$db->connection = NULL;
	
	// Settiamo nome pagina
	$nome_pagina = '';
	$date_pagina = '';
	
	if($tipo_lista == 'arrivi')	 	 $nome_pagina = 'ARRIVI';
	elseif($tipo_lista == 'partenze') $nome_pagina = 'PARTENZE';
	elseif($tipo_lista == 'in_casa')	 $nome_pagina = 'IN CASA';
	
	if($num_date == 1) $date_pagina = 'DI <b>'.$giorni[date('w', $data_arrivo)].'</b> '.date('d/m/y', $data_arrivo);
	else 					 $date_pagina = formatta_periodo($data_arrivo, $data_partenza);
	
	// ----------------------------------------------------------------------------------
	// INIZIAMO A STAMPARE LA PAGINA
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'
		 .'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">'
		 .'<head>';

	if($num_date == 1) echo '<title>'.$nome_pagina.' DI '.$giorni[date('w', $data_arrivo)].' '.date('d/m/y', $data_arrivo).' - '.$_SESSION['nome_struttura'].'</title>';
	else					 echo '<title>'.$nome_pagina.' '.$date_pagina.' - '.$_SESSION['nome_struttura'].'</title>';
	header_mo();
	echo '</head><body>';
	
	// Stampiamo il menu
	echo '<div class="no_print"><a href="menu.php">MENU</a></div>';
	
	// Stampiamo la testata
	echo '<div class="menu">';
		echo '<span class="nome_struttura">'.$_SESSION['nome_struttura'].'</span>';
		echo '<span class="data_stampa"><b>'.$nome_pagina.'</b> '.$date_pagina.'</span>';
	echo '</div>';
	
	// Variabili per buffer lista
	$testa_lista = '';
	$corpo_lista = '';
	
	// Stampiamo la lista
	echo '<div class="mo_lista">';
	
	// CAMBIO CAMERE DA FARE!!!!!
	// Stampiamo gli share e i cambi camere per il periodo
	// DA MIGLIORARE!!!
	for($j = 0, $num_mov_share = 0, $num_mov_cambio_cam = 0, $print_mov_share = '', $print_mov_cambio_cam = '' ; $j < $num_mov_spe ; $j++) {
		// Controlliamo prima che tale movimento non sia già integrato nella sua principale
		for($z = 0, $in_principale = FALSE ; $z < $num_pre ; $z++) {
			if($mov_spe[$j]['id_rif'] == $pre[$z]['id']) {
				$in_principale = TRUE;
				break;
			}
		}
		
		if($in_principale == TRUE) continue;
		
		// Se le date corrispondono
		if($mov_spe[$j][$data_test] == $date[$data_sel]) {
			// Se si tratta di uno share
			if($mov_spe[$j]['tipo_pre'] >= 30 && $mov_spe[$j]['tipo_pre'] <= 39) {
				// Procediamo alla stampa della riga
				$print_mov_share .= '<div class="mo_riga';
				$print_mov_share .= '">';
					$print_mov_share .= '<div class="li_nome"><b>[SHARE]</b> '.set_visual($mov_spe[$j]['nome']).'</div>';
					$print_mov_share .= '<div class="li_agenzia">'.set_visual($mov_spe[$j]['agenzia']).'</div>';
					$print_mov_share .= '<div class="li_gruppo">'.set_visual($mov_spe[$j]['nome_gruppo']).'</div>';
					$print_mov_share .= '<div class="li_camera">';
					if($mov_spe[$j]['camera'] != 0) $print_mov_share .= $mov_spe[$j]['camera'];
					$print_mov_share .= '</div>';
					$print_mov_share .= '<div class="li_vestizione">'.$mov_spe[$j]['vestizione'].'</div>';
					$print_mov_share .= '<div class="li_pax">';
					if($mov_spe[$j]['pax'] != 0) $print_mov_share .= $mov_spe[$j]['pax']; // è uguale a 0 in caso di share
					$print_mov_share .= '</div>';
					if($show_arrivo == TRUE)	 $print_mov_share .= '<div class="li_data">'.date('d/m', $mov_spe[$j]['data_arrivo']).'</div>';
					if($show_partenza == TRUE)  $print_mov_share .= '<div class="li_data">'.date('d/m', $mov_spe[$j]['data_partenza']).'</div>';
					$print_mov_share .= '<div class="li_arr">'.formatta_visualizzazione($mov_spe[$j]['arrangiamento']).'</div>';
					
					$print_mov_share .= '<div class="li_note';
					if($mov_spe[$j]['note'] != '' && $mov_spe[$j]['colore_note'] != 0) $print_mov_share .= ' cn_'.$mov_spe[$j]['colore_note']; // Stampiamo il bordo col colore delle note
					$print_mov_share .= '">'.formatta_visualizzazione($mov_spe[$j]['note']).'</div>';
					
					$print_mov_share .= '<p style="clear:both"></p>';
				$print_mov_share .= '</div>';
			}
		}
	}
	echo $print_mov_share;
	
	
	for($i = 0, $bordo_top_bold = FALSE ; $i < $num_pre ; $i++) {
		
		// Se stiamo cambiando giorno
		if($num_date > 1 && ($tipo_lista == 'arrivi' || $tipo_lista == 'partenze') && $pre[$i][$data_test] != $date[$data_sel]) {
			
			if($i != 0) { // Se non siamo alla prima data
				// Se non siamo in ordine gruppo chiudiamo la tabella precedente
				if($ordine_lista != 'gruppo') echo '</div>';
			
				// Stampiamo i totali del giorno e la data
				echo '<div class="testa_giorno">';
				if($tot_cam > 0 || $tot_pax > 0) {
					echo '<p class="info_giorno"><b>TOTALI</b> '.$tot_cam.' CAMERE '.$tot_pax.' PAX</p>';
				}
				else {
					echo '<p class="info_giorno">NON CI SONO '.$nome_pagina.'</p>';
				}
				
				$tot_cam = 0; $tot_pax = 0;
				
				// Stampiamo la data corrente
				echo '<p class="data_giorno">'.$nome_pagina.' DI <b>'.$giorni[date('w', $date[$data_sel])].'</b> '.date('d/m/y', $date[$data_sel]).'</p>';
				echo '</div>';
				
				if($data_sel+1 != $num_date) $data_sel++;
				else echo '.';
			}
			
			// Se bisogna stampare l'ultimo gruppo
			if($corpo_lista != '') {
				$movimenti .= '<div class="mo_gruppo">';
					$movimenti .= '<div class="mo_testa_gruppo" style="background:'.$gruppo_attuale['bk_color'].'">';
						$movimenti .= '<div class="li_nome">'.set_visual($gruppo_attuale['nome']).'</div>';
						$movimenti .= '<div class="li_agenzia">';
						// Inseriamo pax e camere gruppo
						$cam_pax_gru = 'CAM: '.$gruppo_attuale['list_cam'];
						if($gruppo_attuale['list_cam'] < $gruppo_attuale['tot_cam']) $cam_pax_gru .= '/'.$gruppo_attuale['tot_cam'];
						$cam_pax_gru .= ' PAX: '.$gruppo_attuale['list_pax'];
						if($gruppo_attuale['list_pax'] < $gruppo_attuale['tot_pax']) $cam_pax_gru .= '/'.$gruppo_attuale['tot_pax'];
						$movimenti .= set_visual($cam_pax_gru);
						$movimenti .= '</div>';
						$movimenti .= '<div class="li_camera">Camera</div>';
						$movimenti .= '<div class="li_vestizione">Vest.</div>';
						$movimenti .= '<div class="li_pax">Pax</div>';
						if($show_arrivo == TRUE)	 $movimenti .= '<div class="li_data">Arrivo</div>';
						if($show_partenza == TRUE)  $movimenti .= '<div class="li_data">Partenza</div>';
						$movimenti .= '<div class="li_arr">Arr.</div>';
						if($tipo_lista == 'in_casa' && $num_date == 1) $movimenti .= '<div class="li_arr">B</div><div class="li_arr">L</div><div class="li_arr">D</div>';
						$movimenti .= '<div class="li_note">'.formatta_visualizzazione($gruppo_attuale['note_gruppo']).'</div>';
						$movimenti .= '<p style="clear:both"></p>';
					$movimenti .= '</div>';
				
					$movimenti .= $corpo_lista;
					$corpo_lista = '';
				$movimenti .= '</div>';
			}
			
			// Non sulla prima data per evitare errori grafici
			if($ordine_lista != 'gruppo') {
				if($prima_tab_privati) {
					$movimenti .= '<div class="mo_gruppo">';
					$movimenti .= '<div class="mo_testa_lista">';
						$movimenti .= '<div class="li_nome">Cognome e Nome</div>';
						$movimenti .= '<div class="li_agenzia">Agenzia</div>';
						if($show_gruppo == TRUE) $movimenti .= '<div class="li_gruppo">Gruppo</div>';
						$movimenti .= '<div class="li_camera">Cam.</div>';
						$movimenti .= '<div class="li_vestizione">Vest.</div>';
						$movimenti .= '<div class="li_pax">Pax</div>';
						if($show_arrivo == TRUE)	 $movimenti .= '<div class="li_data">Arrivo</div>';
						if($show_partenza == TRUE)  $movimenti .= '<div class="li_data">Partenza</div>';
						$movimenti .= '<div class="li_arr">Arr.</div>';
						if($tipo_lista == 'in_casa' && $num_date == 1) $movimenti .= '<div class="li_arr">B</div><div class="li_arr">L</div><div class="li_arr">D</div>';
						$movimenti .= '<div class="li_note">Note</div>';
						$movimenti .= '<p style="clear:both"></p>';
					$movimenti .= '</div>';
					echo $movimenti .= '</div>';
					$prima_tab_privati = FALSE;
				}
				else echo '<div class="mo_gruppo">'.$movimenti.'</div>'; // Aggiungiamo anche il contenitore se non si tratta di gruppi
			}
			else {
				echo $movimenti; // Stampiamo tutti i movimenti del giorno in questione
			}
			
			$movimenti = '';
			
			$gruppo_attuale = NULL;
			
			// Se tra la data vecchia e quella della prenotazione nuova è passato più di un giorno stampiamo i giorni con 0 risultati
			$num_giorni_saltati = 0;
			while($date[$data_sel] != $pre[$i][$data_test]) {
				$num_giorni_saltati++;
				if($data_sel+1 == $num_date) break;
				$data_sel++;
			}
			if($num_giorni_saltati > 1) {
				echo '<div class="testa_giorno">';
				echo '<p class="info_giorno">NON CI SONO '.$nome_pagina.'</p>';
				echo '<p class="data_giorno">'.$nome_pagina.' '.formatta_periodo($date[$data_sel-$num_giorni_saltati], $date[$data_sel-1]).'</p>';
				echo '</div>';
			}
			elseif($num_giorni_saltati == 1) {
				echo '<div class="testa_giorno">';
				echo '<p class="info_giorno">NON CI SONO '.$nome_pagina.'</p>';
				echo '<p class="data_giorno">'.$nome_pagina.' DI <b>'.$giorni[date('w', $date[$data_sel-$num_giorni_saltati])].'</b> '.date('d/m/y', $date[$data_sel-$num_giorni_saltati]).'</p>';
				echo '</div>';
			}
		}
		
		// Se sono raggruppati per gruppo e stiamo cambiando gruppo stampiamo la testata
		if($ordine_lista == 'gruppo' && $pre[$i]['gruppo'] !== $gruppo_attuale['id']) {
			
			// Se non siamo al primo gruppo chiudiamo il gruppo precedente e lo stampiamo
			if($gruppo_attuale['id'] !== NULL) {
				$movimenti .= '<div class="mo_gruppo">';
					$movimenti .= '<div class="mo_testa_gruppo" style="background:'.$gruppo_attuale['bk_color'].'">';
						$movimenti .= '<div class="li_nome">'.set_visual($gruppo_attuale['nome']).'</div>';
						$movimenti .= '<div class="li_agenzia">';
						// Inseriamo pax e camere gruppo
						$cam_pax_gru = 'CAM: '.$gruppo_attuale['list_cam'];
						if($gruppo_attuale['list_cam'] < $gruppo_attuale['tot_cam']) $cam_pax_gru .= '/'.$gruppo_attuale['tot_cam'];
						$cam_pax_gru .= ' PAX: '.$gruppo_attuale['list_pax'];
						if($gruppo_attuale['list_pax'] < $gruppo_attuale['tot_pax']) $cam_pax_gru .= '/'.$gruppo_attuale['tot_pax'];
						$movimenti .= set_visual($cam_pax_gru);
						$movimenti .= '</div>';
						$movimenti .= '<div class="li_camera">Cam.</div>';
						$movimenti .= '<div class="li_vestizione">Vest.</div>';
						$movimenti .= '<div class="li_pax">Pax</div>';
						if($show_arrivo == TRUE)	 $movimenti .= '<div class="li_data">Arrivo</div>';
						if($show_partenza == TRUE)  $movimenti .= '<div class="li_data">Partenza</div>';
						$movimenti .= '<div class="li_arr">Arr.</div>';
						if($tipo_lista == 'in_casa' && $num_date == 1) $movimenti .= '<div class="li_arr">B</div><div class="li_arr">L</div><div class="li_arr">D</div>';
						$movimenti .= '<div class="li_note">'.formatta_visualizzazione($gruppo_attuale['note_gruppo']).'</div>';
						$movimenti .= '<p style="clear:both"></p>';
					$movimenti .= '</div>';
				
					$movimenti .= $corpo_lista;
					$corpo_lista = '';
				$movimenti .= '</div>';
			}
			
			// Settiamo i valori standard per il gruppo
			$gruppo_attuale['id'] = $pre[$i]['gruppo'];
			
			if($gruppo_attuale['id'] == 0) {
				$gruppo_attuale['nome'] = '<b>PRIVATI</b>';
				$gruppo_attuale['bk_color'] = '#ffe084';
				$gruppo_attuale['tot_cam'] = 0;
				$gruppo_attuale['tot_pax'] = 0;
				$gruppo_attuale['list_cam'] = 0;
				$gruppo_attuale['list_pax'] = 0;
				$gruppo_attuale['note'] = '';
			}
			else {
				$gruppo_attuale['nome'] = $pre[$i]['nome_gruppo'];
				$gruppo_attuale['bk_color'] = '#b3ebf2';
				$gruppo_attuale['tot_cam'] = $pre[$i]['totale_camere'];
				$gruppo_attuale['tot_pax'] = $pre[$i]['totale_pax'];
				$gruppo_attuale['list_cam'] = 0;
				$gruppo_attuale['list_pax'] = 0;
				$gruppo_attuale['note'] = $pre[$i]['note_gruppo'];
			}
			$gruppo_attuale['list_cam']++;
			$gruppo_attuale['list_pax'] += $pre[$i]['pax'];
		}
		// Se abbiamo scelto il raggruppamento gruppi aggiorniamo i valori generali
		elseif($ordine_lista == 'gruppo') {
			$gruppo_attuale['list_cam']++;
			$gruppo_attuale['list_pax'] += $pre[$i]['pax'];
		}
	
		// Aggiorniamo i totali
		$tot_cam++;
		$tot_pax += $pre[$i]['pax'];
	
	
		// Procediamo alla stampa della riga
		$corpo_lista .= '<div class="mo_riga';
		if($bordo_top_bold == TRUE || ($pre[$i]['id_rif'] == 0 && $pre[$i]['stile_spe'] != 0)) {
			$corpo_lista .= ' bordo_top_bold'; // Se ci sono spe per il periodo in questione
			if($pre[$i]['id_rif'] == 0 && $pre[$i]['stile_spe'] == 0)	$bordo_top_bold = FALSE; // Se non siamo di nuovo in una spe torniamo al bordo normale
		}
		$corpo_lista .= '">';
			$corpo_lista .= '<div class="li_nome">'.set_visual($pre[$i]['nome']).'</div>';
			$corpo_lista .= '<div class="li_agenzia">'.set_visual($pre[$i]['agenzia']).'</div>';
			if($show_gruppo == TRUE) $corpo_lista .= '<div class="li_gruppo">'.set_visual($pre[$i]['nome_gruppo']).'</div>';
			$corpo_lista .= '<div class="li_camera">';
			if($pre[$i]['camera'] != 0) $corpo_lista .= $pre[$i]['camera'];
			$corpo_lista .= '</div>';
			$corpo_lista .= '<div class="li_vestizione">'.$pre[$i]['vestizione'];
			if($pre[$i]['tipologia'] != '') $corpo_lista .= '<b>+'.$pre[$i]['tipologia'].'</b>';
			$corpo_lista .= '</div>';
			$corpo_lista .= '<div class="li_pax">';
			if($pre[$i]['pax'] != 0) $corpo_lista .= $pre[$i]['pax']; // è uguale a 0 in caso di share
			$corpo_lista .= '</div>';
			if($show_arrivo == TRUE) {
				$corpo_lista .= '<div class="li_data">';
				if($tipo_lista == 'in_casa' && $pre[$i]['data_arrivo'] == $data_arrivo) $corpo_lista .= '<b>'.date('d/m', $pre[$i]['data_arrivo']).'</b>';
				else 																						 	$corpo_lista .= date('d/m', $pre[$i]['data_arrivo']);
				$corpo_lista .= '</div>';
			}
			if($show_partenza == TRUE) {
				$corpo_lista .= '<div class="li_data">';
				if($tipo_lista == 'in_casa' && $pre[$i]['data_partenza'] == $data_partenza) $corpo_lista .= '<b>'.date('d/m', $pre[$i]['data_partenza']).'</b>';
				else 																								 $corpo_lista .= date('d/m', $pre[$i]['data_partenza']);
				$corpo_lista .= '</div>';
			}
			if($pre[$i]['arrangiamento'] == 'FB') $corpo_lista .= '<div class="li_arr"><b>'.formatta_visualizzazione($pre[$i]['arrangiamento']).'</b></div>';
			else 											  $corpo_lista .= '<div class="li_arr">'.formatta_visualizzazione($pre[$i]['arrangiamento']).'</div>';
			
			// Se visualizziamo le persone in casa stampiamo le crocette per i pasti
			if($tipo_lista == 'in_casa') {
				if($pre[$i]['data_arrivo'] == $data_arrivo) {
					if($pre[$i]['primo_pasto'] == 1 && $pre[$i]['arrangiamento'] != 'RS')
						$corpo_lista .= '<div class="li_arr"><b>X</b></div>';
					else $corpo_lista .= '<div class="li_arr"></div>';
					
					if($pre[$i]['primo_pasto'] <= 2 && $pre[$i]['arrangiamento'] == 'FB')
						$corpo_lista .= '<div class="li_arr"><b>X</b></div>';
					else $corpo_lista .= '<div class="li_arr"></div>';
					
					if($pre[$i]['primo_pasto'] <= 3 && ($pre[$i]['arrangiamento'] == 'FB' || $pre[$i]['arrangiamento'] == 'HB'))
						$corpo_lista .= '<div class="li_arr">X</div>';
					else $corpo_lista .= '<div class="li_arr"></div>';
				}
				elseif($pre[$i]['data_partenza'] == $data_arrivo) {
					if($pre[$i]['ultimo_pasto'] >= 1 && $pre[$i]['arrangiamento'] != 'RS')
						$corpo_lista .= '<div class="li_arr">X</div>';
					else $corpo_lista .= '<div class="li_arr"></div>';
					
					if($pre[$i]['ultimo_pasto'] >= 2 && $pre[$i]['arrangiamento'] == 'FB')
						$corpo_lista .= '<div class="li_arr">X</div>';
					else $corpo_lista .= '<div class="li_arr"></div>';
					
					if($pre[$i]['ultimo_pasto'] >= 3 && ($pre[$i]['arrangiamento'] == 'FB' || $pre[$i]['arrangiamento'] == 'HB'))
						$corpo_lista .= '<div class="li_arr"><b>X</b></div>';
					else $corpo_lista .= '<div class="li_arr"></div>';
				}
				else {
					if($pre[$i]['arrangiamento'] != 'RS')
						$corpo_lista .= '<div class="li_arr">X</div>';
					else $corpo_lista .= '<div class="li_arr"></div>';
					
					if($pre[$i]['arrangiamento'] == 'FB')
						$corpo_lista .= '<div class="li_arr">X</div>';
					else $corpo_lista .= '<div class="li_arr"></div>';
					
					if($pre[$i]['arrangiamento'] == 'FB' || $pre[$i]['arrangiamento'] == 'HB')
						$corpo_lista .= '<div class="li_arr">X</div>';
					else $corpo_lista .= '<div class="li_arr"></div>';
				}
			}
			
			$corpo_lista .= '<div class="li_note';
			if($pre[$i]['note'] != '' && $pre[$i]['colore_note'] != 0) $corpo_lista .= ' cn_'.$pre[$i]['colore_note']; // Stampiamo il bordo col colore delle note
			$corpo_lista .= '">'.formatta_visualizzazione($pre[$i]['note']).'</div>';
			
			$corpo_lista .= '<p style="clear:both"></p>';
		$corpo_lista .= '</div>';
		
		// Stampiamo le righe delle speciali
		if($pre[$i]['id_rif'] == 0 && $pre[$i]['stile_spe'] != 0) {
			$bordo_top_bold = TRUE;
			for($j = 0 ; $j < $num_spe ; $j++) {
				if($spe[$j]['id_rif'] == $pre[$i]['id']) {
					if($spe[$j]['tipo_pre'] >= 10 && $spe[$j]['tipo_pre'] <= 19) { // Multicamera
						// Aggiorniamo i totali
						if($tipo_lista == 'arrivi' || $tipo_lista == 'partenze') {
							if($spe[$j][$data_test] == $date[$data_sel]) {
								$tot_cam++;
								$tot_pax += $pre[$i]['pax'];
							}
						}

						$corpo_lista .= '<div class="mo_riga">';
							$corpo_lista .= '<div class="li_nome"><b>+ CAMERA</b></div>';
							$corpo_lista .= '<div class="li_agenzia"></div>';
							if($show_gruppo == TRUE) $corpo_lista .= '<div class="li_gruppo"></div>';
							$corpo_lista .= '<div class="li_camera">';
							if($pre[$i]['camera'] != 0) $corpo_lista .= $spe[$j]['camera'];
							$corpo_lista .= '</div>';
							$corpo_lista .= '<div class="li_vestizione">'.$spe[$j]['vestizione'];
							if($spe[$j]['tipologia'] != '') $corpo_lista .= '<b>+'.$spe[$j]['tipologia'].'</b>';
							$corpo_lista .= '</div>';
							$corpo_lista .= '<div class="li_pax">'.$spe[$j]['pax'].'</div>';
							if($show_arrivo == TRUE)	 $corpo_lista .= '<div class="li_data"></div>';
							if($show_partenza == TRUE)  $corpo_lista .= '<div class="li_data"></div>';
							$corpo_lista .= '<div class="li_arr"></div>';
							$corpo_lista .= '<div class="li_note"></div>';
							$corpo_lista .= '<p style="clear:both"></p>';
						$corpo_lista .= '</div>';
					}
					elseif($spe[$j]['tipo_pre'] >= 20 && $spe[$j]['tipo_pre'] <= 29) { // CAMBIO CAMERA
						$corpo_lista .= '<div class="mo_riga">';
							$corpo_lista .= '<div class="li_nome"><b>CAMBIO CAMERA</b></div>';
							$corpo_lista .= '<div class="li_agenzia">DAL '.date('d/m', $spe[$j]['data_arrivo']).'</div>';
							if($show_gruppo == TRUE) $corpo_lista .= '<div class="li_gruppo"></div>';
							$corpo_lista .= '<div class="li_camera">';
							if($pre[$i]['camera'] != 0) $corpo_lista .= $spe[$j]['camera'];
							$corpo_lista .= '</div>';
							$corpo_lista .= '<div class="li_vestizione">'.$spe[$j]['vestizione'];
							if($spe[$j]['tipologia'] != '') $corpo_lista .= '<b>+'.$spe[$j]['tipologia'].'</b>';
							$corpo_lista .= '</div>';
							$corpo_lista .= '<div class="li_pax"></div>';
							if($show_arrivo == TRUE)	 $corpo_lista .= '<div class="li_data"></div>';
							if($show_partenza == TRUE)  $corpo_lista .= '<div class="li_data">'.date('d/m', $spe[$j]['data_partenza']).'</div>';
							$corpo_lista .= '<div class="li_arr"></div>';
							$corpo_lista .= '<div class="li_note"></div>';
							$corpo_lista .= '<p style="clear:both"></p>';
						$corpo_lista .= '</div>';
					}
					elseif($spe[$j]['tipo_pre'] >= 30 && $spe[$j]['tipo_pre'] <= 39) { // SHARE
						// Aggiorniamo i totali se sono compresi nel periodo richiesto
						if($tipo_lista == 'arrivi' || $tipo_lista == 'partenze') {
							if($spe[$j][$data_test] == $date[$data_sel]) {
								$tot_pax += $pre[$i]['pax'];
							}
						}
						
						$corpo_lista .= '<div class="mo_riga">';
							$corpo_lista .= '<div class="li_nome"><b>SHARE</b> '.set_visual($spe[$j]['nome']).'</div>';
							$corpo_lista .= '<div class="li_agenzia">DAL '.date('d/m', $spe[$j]['data_arrivo']).'</div>';
							if($show_gruppo == TRUE) $corpo_lista .= '<div class="li_gruppo"></div>';
							$corpo_lista .= '<div class="li_camera"></div>';
							$corpo_lista .= '<div class="li_vestizione"></div>';
							$corpo_lista .= '<div class="li_pax">'.$spe[$j]['pax'].'</div>';
							if($show_arrivo == TRUE)	 $corpo_lista .= '<div class="li_data"></div>';
							if($show_partenza == TRUE)  $corpo_lista .= '<div class="li_data">'.date('d/m', $spe[$j]['data_partenza']).'</div>';
							$corpo_lista .= '<div class="li_arr">'.formatta_visualizzazione($spe[$j]['arrangiamento']).'</div>';
							$corpo_lista .= '<div class="li_note"></div>';
							$corpo_lista .= '<p style="clear:both"></p>';
						$corpo_lista .= '</div>';
					}
				}
			}
		}
	
		// Se si è scelto di stampare le righe una per volta e non raggruppandole per gruppo
		if($ordine_lista != 'gruppo') {
			$movimenti .= $corpo_lista;
			$corpo_lista = '';
		}
	}
	
	// Se bisogna stampare l'ultimo gruppo
	if($corpo_lista != '') {
		$movimenti .= '<div class="mo_gruppo">';
			$movimenti .= '<div class="mo_testa_gruppo" style="background:'.$gruppo_attuale['bk_color'].'">';
				$movimenti .= '<div class="li_nome">'.set_visual($gruppo_attuale['nome']).'</div>';
				$movimenti .= '<div class="li_agenzia">';
				// Inseriamo pax e camere gruppo
				$cam_pax_gru = 'CAM: '.$gruppo_attuale['list_cam'];
				if($gruppo_attuale['list_cam'] < $gruppo_attuale['tot_cam']) $cam_pax_gru .= '/'.$gruppo_attuale['tot_cam'];
				$cam_pax_gru .= ' PAX: '.$gruppo_attuale['list_pax'];
				if($gruppo_attuale['list_pax'] < $gruppo_attuale['tot_pax']) $cam_pax_gru .= '/'.$gruppo_attuale['tot_pax'];
				$movimenti .= set_visual($cam_pax_gru);
				$movimenti .= '</div>';
				$movimenti .= '<div class="li_camera">Camera</div>';
				$movimenti .= '<div class="li_vestizione">Vest.</div>';
				$movimenti .= '<div class="li_pax">Pax</div>';
				if($show_arrivo == TRUE)	 $movimenti .= '<div class="li_data">Arrivo</div>';
				if($show_partenza == TRUE)  $movimenti .= '<div class="li_data">Partenza</div>';
				$movimenti .= '<div class="li_arr">Arr.</div>';
				$movimenti .= '<div class="li_note">'.formatta_visualizzazione($gruppo_attuale['note_gruppo']).'</div>';
				$movimenti .= '<p style="clear:both"></p>';
			$movimenti .= '</div>';
		
			$movimenti .= $corpo_lista;
			$corpo_lista = '';
		$movimenti .= '</div>';
	}
	
	// Se siamo su più giorni in arrivi o partenze stampiamo anche la testata
	if($num_date > 1 && ($tipo_lista == 'arrivi' || $tipo_lista == 'partenze')) {
		// Stampiamo i totali del giorno e la data
		if($num_pre > 0) {
			echo '<div class="testa_giorno">';
			if($tot_cam > 0 || $tot_pax > 0) {
				echo '<p class="info_giorno"><b>TOTALI</b> '.$tot_cam.' CAMERE '.$tot_pax.' PAX</p>';
			}
			else {
				echo '<p class="info_giorno">NON CI SONO '.$nome_pagina.'</p>';
			}
		
			$tot_cam = 0; $tot_pax = 0;
			
			// Stampiamo la data corrente
			echo '<p class="data_giorno">'.$nome_pagina.' DI <b>'.$giorni[date('w', $date[$data_sel])].'</b> '.date('d/m/y', $date[$data_sel]).'</p>';
			echo '</div>';
		}
		
		if($data_sel+1 != $num_date) $data_sel++;
		
		if($num_date > 1 && $ordine_lista != 'gruppo')  echo '<div class="mo_gruppo">'.$movimenti.'</div>';
		else 															echo $movimenti;
		
		// Se tra la data dell'ultima prenotazione e quella attuale ne mancano le stampiamo vuote
		$num_giorni_saltati = 0;
		if($num_pre == 0) $data_finale = $data_partenza;
		else 					$data_finale = $pre[$i-1][$data_test];
		while($date[$data_sel] != $data_finale) {
			$num_giorni_saltati++;
			if($data_sel+1 == $num_date) break;
			$data_sel++;
		}
		if($num_giorni_saltati > 1) {
			echo '<div class="testa_giorno">';
			echo '<p class="info_giorno">NON CI SONO '.$nome_pagina.'</p>';
			echo '<p class="data_giorno">'.$nome_pagina.' '.formatta_periodo($date[$data_sel-$num_giorni_saltati], $date[$data_sel]).'</p>';
			echo '</div>';
		}
		elseif($num_giorni_saltati == 1) {
			echo '<div class="testa_giorno">';
			echo '<p class="info_giorno">NON CI SONO '.$nome_pagina.'</p>';
			echo '<p class="data_giorno">'.$nome_pagina.' DI <b>'.$giorni[date('w', $date[$data_sel])].'</b> '.date('d/m/y', $date[$data_sel]).'</p>';
			echo '</div>';
		}
	}
	else echo $movimenti;
	
	// Se non si è ordinato per gruppo e si è trovato qualcosa si chiude mo_gruppo
	if($i >= 0 && $ordine_lista != 'gruppo') echo '</div>';
	
	// Se non si è trovato niente e si è su un unico giorno
	if($num_pre == 0 && $num_date == 1) {
		echo '<div class="testa_giorno">';
		echo '<p class="info_giorno">NON CI SONO '.$nome_pagina.'</p>';
		echo '</div>';
	}
	
	echo '</div>'; // fine .mo_lista
	
	// Se siamo su un unico giorno a chiediamo gli arrivi stampiamo anche il riepilogo movimenti
	if(($tipo_lista == 'arrivi' || $tipo_lista == 'in_casa') && $num_date == 1) {
		// Recuperiamo le prenotazioni ordinandole per data
		$prenotazioni_da_prendere = 'WHERE prenotazioni.data_arrivo='.$data_arrivo 		// Se l'arrivo è nel periodo
												.'|| prenotazioni.data_partenza=' .$data_arrivo 	// Se la partenza è nel periodo
												.'|| (prenotazioni.data_partenza>='.$data_arrivo.' AND prenotazioni.data_arrivo<='.$data_arrivo.')'; // Se la prenotazione ingloba il periodo
	
		$db = db_connect();
		$prenotazioni_grezze = $db->query('SELECT prenotazioni.id_rif,prenotazioni.tipo_pre,prenotazioni.stile_spe,prenotazioni.data_arrivo,prenotazioni.data_partenza,prenotazioni.pax, '//
									.'prenotazioni.agenzia,prenotazioni.arrangiamento,prenotazioni.primo_pasto,prenotazioni.ultimo_pasto'//
									.' FROM prenotazioni LEFT JOIN gruppi ON prenotazioni.gruppo=gruppi.id '//
									.$prenotazioni_da_prendere . ' ORDER BY prenotazioni.data_arrivo, gruppo');
		$db->connection = NULL;
		
		$pre = $prenotazioni_grezze->fetchAll(PDO::FETCH_ASSOC);
		$num_pre = count($pre);
		$pre_rimaste = $num_pre;
		
		// Se esistono effettivamente delle prenotazioni per il periodo indicato
		if($num_pre > 0) {
			// Prendiamo la prima data utile e la parametriamo
			$info_day[0]['timestamp'] = $data_arrivo;
			$prima_data = &$info_day[0]['timestamp'];
			
			$info_day[0]['arrivi_cam'] = 0;		  $info_day[0]['arrivi_pax'] = 0;
			$info_day[0]['partenze_cam'] = 0;	  $info_day[0]['partenze_pax'] = 0;
			$info_day[0]['in_casa_cam'] = 0;		  $info_day[0]['in_casa_pax'] = 0;
			$info_day[0]['presenze_tot_cam'] = 0; $info_day[0]['presenze_tot_pax'] = 0;
			$info_day[0]['b_cam'] = 0; $info_day[0]['b_pax'] = 0;
			$info_day[0]['l_cam'] = 0; $info_day[0]['l_pax'] = 0;
			$info_day[0]['d_cam'] = 0; $info_day[0]['d_pax'] = 0;
			$info_day[0]['RS_cam'] = 0; $info_day[0]['BB_cam'] = 0; $info_day[0]['HB_cam'] = 0; $info_day[0]['FB_cam'] = 0;
			$info_day[0]['RS_pax'] = 0; $info_day[0]['BB_pax'] = 0; $info_day[0]['HB_pax'] = 0; $info_day[0]['FB_pax'] = 0;
		
			// Si inseriscono i dati statistici nel giorno
			for($i = 0, $pre_el = FALSE, $data_sel = &$info_day[0]['timestamp'] ; $i < $num_pre ; $i++) {
				$frecce_cam = 0;
				$is_principale = FALSE;
				$is_cambio_cam = FALSE;
				$is_share = FALSE;
				if($pre[$i]['tipo_pre'] <= 9) {
					$is_principale = TRUE;
					$frecce_cam = $pre[$i]['stile_spe'] % 10;
				}
				elseif($pre[$i]['tipo_pre'] >= 30 && $pre[$i]['tipo_pre'] <= 39) $is_share = TRUE;
				elseif($pre[$i]['tipo_pre'] >= 20 && $pre[$i]['tipo_pre'] <= 29) {
					$is_cambio_cam = TRUE;
					$frecce_cam = $pre[$i]['stile_spe'] % 10;
				}
				
				// Se la data di arrivo è prima della data corrente
				if($pre[$i]['data_arrivo'] < $data_sel) {
					// Se la prenotazione è in casa
					if($pre[$i]['data_partenza'] > $data_sel) {
						// Aggiorniamo il numero di pax per arrangiamento
						$info_day[0][$pre[$i]['arrangiamento'].'_pax'] += $pre[$i]['pax'];
						
						if(!$is_share) {
							$info_day[0]['in_casa_cam']++;
							// Aggiorniamo il numero di camere per arrangiamento
							$info_day[0][$pre[$i]['arrangiamento'].'_cam']++;
						}
						
						$info_day[0]['in_casa_pax'] += $pre[$i]['pax'];
						
						if($pre[$i]['arrangiamento'] == 'BB') {
							if(!$is_share) $info_day[0]['b_cam']++;	$info_day[0]['b_pax'] += $pre[$i]['pax'];
						}
						elseif($pre[$i]['arrangiamento'] == 'HB') {
							if(!$is_share) $info_day[0]['b_cam']++;	$info_day[0]['b_pax'] += $pre[$i]['pax'];
							if(!$is_share) $info_day[0]['d_cam']++;	$info_day[0]['d_pax'] += $pre[$i]['pax'];
						}
						elseif($pre[$i]['arrangiamento'] == 'FB') {
							if(!$is_share) $info_day[0]['b_cam']++;	$info_day[0]['b_pax'] += $pre[$i]['pax'];
							if(!$is_share) $info_day[0]['l_cam']++;	$info_day[0]['l_pax'] += $pre[$i]['pax'];
							if(!$is_share) $info_day[0]['d_cam']++;	$info_day[0]['d_pax'] += $pre[$i]['pax'];
						}
					}
					// Se la prenotazione è in partenza
					elseif($pre[$i]['data_partenza'] == $data_sel) {
						// Se è uno share si aggiornano solo i pax in partenza
						if($is_share) {
							$info_day[0]['partenze_pax'] += $pre[$i]['pax'];
						}
						// Se è una principale o una camera aggiuntiva o un cambio camera non in partenza (per evitare conteggio doppio sul cambio camera)
						elseif($frecce_cam == 0) {
							$info_day[0]['partenze_cam']++;
							$info_day[0]['partenze_pax'] += $pre[$i]['pax'];
						}
						elseif($frecce_cam == 1) {
							$info_day[0]['partenze_cam']++;
							$info_day[0]['partenze_pax'] += $pre[$i]['pax'];
						}
						
						// Se si tratta di una partenza vera e propria
						if($frecce_cam != 2 && $frecce_cam != 3) {
							if($pre[$i]['arrangiamento'] == 'BB') {
								if(!$is_share) $info_day[0]['b_cam']++; $info_day[0]['b_pax'] += $pre[$i]['pax'];
							}
							elseif($pre[$i]['arrangiamento'] == 'HB') {
								if(!$is_share) $info_day[0]['b_cam']++; $info_day[0]['b_pax'] += $pre[$i]['pax'];
								
								if($pre[$i]['ultimo_pasto'] == 3) {
									if(!$is_share) $info_day[0]['d_cam']++; $info_day[0]['d_pax'] += $pre[$i]['pax'];
								}
							}
							elseif($pre[$i]['arrangiamento'] == 'FB') {
								if($pre[$i]['ultimo_pasto'] == 1) {
									if(!$is_share) $info_day[0]['b_cam']++; $info_day[0]['b_pax'] += $pre[$i]['pax'];
								}
								elseif($pre[$i]['ultimo_pasto'] == 2) {
									if(!$is_share) $info_day[0]['b_cam']++; $info_day[0]['b_pax'] += $pre[$i]['pax'];
									if(!$is_share) $info_day[0]['l_cam']++; $info_day[0]['l_pax'] += $pre[$i]['pax'];
								}
								else {
									if(!$is_share) $info_day[0]['b_cam']++; $info_day[0]['b_pax'] += $pre[$i]['pax'];
									if(!$is_share) $info_day[0]['l_cam']++; $info_day[0]['l_pax'] += $pre[$i]['pax'];
									if(!$is_share) $info_day[0]['d_cam']++; $info_day[0]['d_pax'] += $pre[$i]['pax'];
								}
							}
						}
						// Altrimenti al posto della partenza lo segnamo in casa
						else {
							if($pre[$i]['arrangiamento'] == 'BB') {
								if(!$is_share) $info_day[0]['b_cam']++;	$info_day[0]['b_pax'] += $pre[$i]['pax'];
							}
							elseif($pre[$i]['arrangiamento'] == 'HB') {
								if(!$is_share) $info_day[0]['b_cam']++;	$info_day[0]['b_pax'] += $pre[$i]['pax'];
								if(!$is_share) $info_day[0]['d_cam']++;	$info_day[0]['d_pax'] += $pre[$i]['pax'];
							}
							elseif($pre[$i]['arrangiamento'] == 'FB') {
								if(!$is_share) $info_day[0]['b_cam']++;	$info_day[0]['b_pax'] += $pre[$i]['pax'];
								if(!$is_share) $info_day[0]['l_cam']++;	$info_day[0]['l_pax'] += $pre[$i]['pax'];
								if(!$is_share) $info_day[0]['d_cam']++;	$info_day[0]['d_pax'] += $pre[$i]['pax'];
							}
						}
					}
				}
				// Se la prenotazione è in arrivo
				else {
					// Aggiorniamo il numero di pax per arrangiamento
					$info_day[0][$pre[$i]['arrangiamento'].'_pax'] += $pre[$i]['pax'];
					
					// Aggiorniamo il numero di camere per arrangiamento
					if(!$is_share) $info_day[0][$pre[$i]['arrangiamento'].'_cam']++;
					
					// Se è un cambio camera con freccia a sinistra e non un arrivo
					if($is_cambio_cam && ($frecce_cam == 1 || $frecce_cam == 3)) {
						$info_day[0]['in_casa_cam']++;
						$info_day[0]['in_casa_pax'] += $pre[$i]['pax'];
					}
					// Se è uno share si aggiorna solo il pax
					elseif($is_share) {
						$info_day[0]['arrivi_pax'] += $pre[$i]['pax'];
					}
					// Se è un arrivo vero e proprio
					else {
						$info_day[0]['arrivi_cam']++;
						$info_day[0]['arrivi_pax'] += $pre[$i]['pax'];
					}
					// Se si tratta di un arrivo vero e proprio
					if($frecce_cam != 1 && $frecce_cam != 3) {
						if($pre[$i]['arrangiamento'] == 'BB' && $pre[$i]['primo_pasto'] == 1) {
							if(!$is_share) $info_day[0]['b_cam']++; $info_day[0]['b_pax'] += $pre[$i]['pax'];
						}
						elseif($pre[$i]['arrangiamento'] == 'HB') {
							if($pre[$i]['primo_pasto'] == 1) { if(!$is_share) $info_day[0]['b_cam']++; $info_day[0]['b_pax'] += $pre[$i]['pax']; }
							else 										{ if(!$is_share) $info_day[0]['d_cam']++; $info_day[0]['d_pax'] += $pre[$i]['pax']; }
						}
						elseif($pre[$i]['arrangiamento'] == 'FB') {
							if($pre[$i]['primo_pasto'] == 1) {
								if(!$is_share) $info_day[0]['b_cam']++; $info_day[0]['b_pax'] += $pre[$i]['pax'];
								if(!$is_share) $info_day[0]['l_cam']++; $info_day[0]['l_pax'] += $pre[$i]['pax'];
								if(!$is_share) $info_day[0]['d_cam']++; $info_day[0]['d_pax'] += $pre[$i]['pax'];
							}
							elseif($pre[$i]['primo_pasto'] == 2) {
								if(!$is_share) $info_day[0]['l_cam']++; $info_day[0]['l_pax'] += $pre[$i]['pax'];
								if(!$is_share) $info_day[0]['d_cam']++; $info_day[0]['d_pax'] += $pre[$i]['pax'];
							}
							else {
								if(!$is_share) $info_day[0]['d_cam']++; $info_day[0]['d_pax'] += $pre[$i]['pax'];
							}
						}
					}
				}
			}
			
			// Calcoliamo le presenze totali del giorno
			$info_day[0]['presenze_tot_cam'] = $info_day[0]['arrivi_cam'] + $info_day[0]['in_casa_cam'];
			$info_day[0]['presenze_tot_pax'] = $info_day[0]['arrivi_pax'] + $info_day[0]['in_casa_pax'];
		
			// Stampiamo i risultati ottenuti

				 
			// Stampiamo i vari arrangiamenti in casa
			echo '<div class="mo_riepilogo float_dx">'
				 .'<div class="testa_riep"><div class="tipo_riep">RIEPILOGO PASTI DEL GIORNO</div><div class="pax_riep">PAX</div><div class="cam_riep">CAM</div><p style="clear:both"></p></div>'
				 .'<div class="riga_riep">'
				 .'<div class="tipo_riep">COLAZIONE</div><div class="pax_riep">'.$info_day[0]['b_pax'].'</div><div class="cam_riep">'.$info_day[0]['b_cam'].'</div>'
				 .'<p style="clear:both"></p></div>'
				 .'<div class="riga_riep">'
				 .'<div class="tipo_riep">PRANZO</div><div class="pax_riep">'.$info_day[0]['l_pax'].'</div><div class="cam_riep">'.$info_day[0]['l_cam'].'</div>'
				 .'<p style="clear:both"></p></div>'
				 .'<div class="riga_riep">'
				 .'<div class="tipo_riep">CENA</div><div class="pax_riep">'.$info_day[0]['d_pax'].'</div><div class="cam_riep">'.$info_day[0]['d_cam'].'</div>'
				 .'<p style="clear:both"></p></div>'
				 .'</div>';
			
			echo '<div class="mo_riepilogo">'
				 .'<div class="testa_riep"><div class="tipo_riep">RIEPILOGO MOVIMENTI</div><div class="pax_riep">PAX</div><div class="cam_riep">CAM</div><p style="clear:both"></p></div>'
				 .'<div class="riga_riep">'
				 .'<div class="tipo_riep">PARTENZE DEL GIORNO</div><div class="pax_riep">'.$info_day[0]['partenze_pax'].'</div><div class="cam_riep">'.$info_day[0]['partenze_cam'].'</div>'
				 .'<p style="clear:both"></p></div>'
				 .'<div class="riga_riep">'
				 .'<div class="tipo_riep">RIMANENZA IN CASA</div><div class="pax_riep">'.$info_day[0]['in_casa_pax'].'</div><div class="cam_riep">'.$info_day[0]['in_casa_cam'].'</div>'
				 .'<p style="clear:both"></p></div>'
				 .'</div>';

			echo '<div class="mo_riepilogo">'
				 .'<div class="riga_riep no_border">'
				 .'<div class="tipo_riep">ARRIVI DEL GIORNO</div><div class="pax_riep">'.$info_day[0]['arrivi_pax'].'</div><div class="cam_riep">'.$info_day[0]['arrivi_cam'].'</div>'
				 .'<p style="clear:both"></p></div>'
				 .'<div class="riga_riep">'
				 .'<div class="tipo_riep">PREVISTE IN CASA IL <b>'.date('d/m', $data_arrivo).'</b></div><div class="pax_riep">'.$info_day[0]['presenze_tot_pax'].'</div><div class="cam_riep">'.$info_day[0]['presenze_tot_cam'].'</div>'
				 .'<p style="clear:both"></p></div>'
				 .'</div>';

			echo '<div class="mo_riepilogo">';
			// Stampiamo il numero di camere e pax per arrangiamento
			$arr = array('RS','BB','HB','FB');
			$nome_arr = array('RESIDENCE','SOLO COLAZIONE','MEZZA PENSIONE','PENSIONE COMPLETA');
			for($i = 0, $prima_riga = TRUE ; $i < 4 ; $i++) {
				if($info_day[0][$arr[$i].'_pax'] == 0) continue;
			
				if($prima_riga == TRUE) { echo '<div class="riga_riep no_border">'; $prima_riga = FALSE; }
				else 							  echo '<div class="riga_riep">';
				
				echo '<div class="tipo_riep">DI CUI IN '.$nome_arr[$i].'</div><div class="pax_riep">'.$info_day[0][$arr[$i].'_pax'].'</div><div class="cam_riep">'.$info_day[0][$arr[$i].'_cam'].'</div>';
				echo '<p style="clear:both"></p></div>';
			}
			echo '</div>';
		}
	}
	
	
	echo '</body></html>';
} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>