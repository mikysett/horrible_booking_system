<?php
// Per i test il valore dev'essere On, invice quando il sito è online deve essere off
ini_set('display_errors', 'On');
ini_set('default_charset', 'utf-8');
date_default_timezone_set('Europe/Rome');

// unico punto per connessione al database
function db_connect() {
	// dati per connessione al database in locale
	return $db = new PDO('mysql:host='.$_SESSION['host'].';dbname='.$_SESSION['dbname'].';charset=utf8', $_SESSION['user'], $_SESSION['pass']);
}

// formatta il testo per la corretta visualizzazione al momento della stampa su schermo
function formatta_visualizzazione(&$testo_grezzo) {
	return stripslashes(htmlspecialchars_decode($testo_grezzo));
}

function formatta_salvataggio(&$testo_grezzo) {
	return htmlentities(strtoupper(addslashes($testo_grezzo)), ENT_COMPAT, "UTF-8");
}

function controllo_data(&$data) {
	$data_smistata = explode('/', str_replace(array('.', ',', ';', '-', '_'), '/', $data));
	$num_data_smistata = count($data_smistata);

	// Se non esiste nemmeno la prima entrata o non è un int ritorniamo errore
	if($num_data_smistata > 0 && is_numeric($data_smistata[0]) == true) {
		
		// Se il mese non è stato inserito o non è un int si inserisce il mese corrente
		if($num_data_smistata == 1 || is_numeric($data_smistata[1]) == false)
			$data_smistata[1] = $_SESSION['oggi_mese'];
		
		// Se l'anno non è stato inserito o non è un int si inserisce l'anno corrente
		if($num_data_smistata == 2 || is_numeric($data_smistata[2]) == false) {
			// Se il mese scelto è più piccolo del mese corrente si setta come anno di default l'anno prossimo
			if($data_smistata[1] < $_SESSION['oggi_mese']) $data_smistata[2] = $_SESSION['oggi_anno'] + 1;
			// Altrimenti l'anno di default è quello attuale
			else 												 		  $data_smistata[2] = $_SESSION['oggi_anno'];
		}

		$data_corretta = mktime(0, 0, 0, $data_smistata[1], $data_smistata[0], $data_smistata[2]);
	}
	else return NULL;
	
	return $data_corretta;
}

/*
CODICI PROBLEMI
PRENOTAZIONE 0, CAMERA AGGIUNTIVA 10, CAMBIO CAMERA 20 = CAMERA INESISTENTE
PRENOTAZIONE 1, CAMBIO CAMERA 21, SHARE 31				 = DATE INCOERENTI
PRENOTAZIONE 2, CAMERA AGGIUNTIVA 12, CAMBIO CAMERA 22 = CAMERA GIA OCCUPATA
PRENOTAZIONE 3, CAMERA AGGIUNTIVA 13, SHARE 33			 = PAX PRENOTAZIONE > PAX CAMERA
PRENOTAZIONE 4														 = TIPOLOGIA PRENOTAZIONE != TIPOLOGIA CAMERA

CONTROLLA QUESTI POSSIBILI PROBLEMI SU UNA SINGOLA PRENOTAZIONE E SU I SUOI CORRELATI SPECIALI
*/

function controllo_pre(&$pre) {
	$num_pre = count($pre);
	$num_id_pre = 0;
	$id_pre = array();
	$err_date = array();
	$cam_ctrl = array();
	$num_cam_ctrl = 0;
	
	// Se le date sono incoerenti si da questo valore alla data di partenza
	$partenza_default = strtotime('+7 day', $_SESSION['oggi']);
	
	$prima_data = 0;
	$ultima_data = 0;
	
	// Cerchiamo la prima e l'ultima data delle prenotazioni per verificare possibili problemi di overbooking
	for($i = 0 ; $i < $num_pre ; $i++) {
		
		// Se la prenotazione esiste già nel database la inseriamo nelle prenotazioni da non prendere
		if($pre[$i]['id'] != NULL) { $id_pre[] = &$pre[$i]['id']; $num_id_pre++; }
		
		// Se le date sono coerenti
		if($pre[$i]['data_arrivo'] != NULL && $pre[$i]['data_partenza'] != NULL && $pre[$i]['data_arrivo'] < $pre[$i]['data_partenza']) {

			$err_date[$i] = FALSE;
			
			// Estrapoliamo la prima e l'ultima data
			if($pre[$i]['data_arrivo'] < $prima_data || $prima_data == 0) {
				$prima_data = $pre[$i]['data_arrivo'];
			}
			if($pre[$i]['data_partenza'] > $ultima_data) {
				$ultima_data = $pre[$i]['data_partenza'];
			}
		}

		else 	{
			$pre[$i]['data_arrivo'] = $_SESSION['oggi'];
			$pre[$i]['data_partenza'] = $partenza_default;
			$err_date[$i] = TRUE;
		}
	}
	
	// Iniziamo a controllare le prenotazioni
	for($i = 0 ; $i < $num_pre ; $i++) {
		// Resettiamo gli errori per evitare che si moltiplichino in modo esponenziale
		$pre[$i]['problemi'] = '';
		
		// Estrapoliamo il tipo di pre (principale o speciale)
		$tipo_pre = floor($pre[$i]['tipo_pre'] / 10) * 10;
		
		// Si controlla la coerenza delle date
		if($err_date[$i] == TRUE) {
			if($pre[$i]['problemi'] != '') $pre[$i]['problemi'] .= '#';
			$pre[$i]['problemi'] .= ($tipo_pre + 1);
		}
		
		// Se la camera è stata assegnata
		if($pre[$i]['camera'] != 0) {
			// Controlliamo che la camera esista
			for($j = 0 ; $j < $_SESSION['num_cam'] ; $j++) {
				if($pre[$i]['camera'] == $_SESSION['cam'][$j]['numero']) {
					// Se la camera può essere soggetta all'overbooking si inserisce nella lista di camere da controllare
					if($err_date[$i] == FALSE) {
						for($cam_sel = 0 ; $cam_sel < $num_cam_ctrl ; $cam_sel++) {
							if($pre[$i]['camera'] == $cam_ctrl[$cam_sel]) {
								break;
							}
						}
						// Se la camera non è stata trovata nell'array di camere da controllare
						if($cam_sel == $num_cam_ctrl) {
							$cam_ctrl[] = $pre[$i]['camera'];
							$num_cam_ctrl++;
						}
					}
					
					// Controlliamo che il pax della camera non sia inferiore a quello della prenotazione
					if($pre[$i]['pax'] > $_SESSION['cam'][$j]['pax_max']) {
						if($pre[$i]['problemi'] != '') $pre[$i]['problemi'] .= '#';
						$pre[$i]['problemi'] .= ($tipo_pre + 3);
					}

					// Controlliamo che la tipologia della camera non sia diversa da quella della prenotazione
					if($pre[$i]['tipologia'] != '' && $pre[$i]['tipologia'] != $_SESSION['cam'][$j]['descrizione_breve']) {
						if($pre[$i]['problemi'] != '') $pre[$i]['problemi'] .= '#';
						$pre[$i]['problemi'] .= ($tipo_pre + 4);
					}
					// La camera è stata trovata, si smette di cercare
					break;
				}
			}

			// Se la camera non esiste registriamo il problema
			if($j == $_SESSION['num_cam']) {
				$pre[$i]['camera'] = 0;
				if($pre[$i]['problemi'] != '') $pre[$i]['problemi'] .= '#';
				$pre[$i]['problemi'] .= ($tipo_pre);
			}
		}
	}
	
	// Se esistono almeno due date coerenti si procede col controllo overbooking
	if($prima_data != 0 && $ultima_data != 0) {
		$pre_overbooking = array();
		
		// Recuperiamo e formattiamo le prenotazioni per il periodo massimo in questione
		$sql = 'SELECT id, camera, data_arrivo, data_partenza FROM prenotazioni '
				 . 'WHERE (tipo_pre < 30 OR tipo_pre > 39) AND ' // Scartiamo anche gli share
				 . '((data_arrivo>=' . $prima_data . ' AND data_arrivo<' . $ultima_data . ')'
				 . ' OR (data_partenza>' . $prima_data . ' AND  data_partenza<' . $ultima_data . ')'
				 . ' OR (data_arrivo<=' . $prima_data . ' AND  data_partenza>=' . $ultima_data . '))';
		
		// Cerchiamo solo nelle camere soggette all'overbooking
		if($num_cam_ctrl > 0) {
			$sql .= ' AND (';
			for($i = 0 ; $i < $num_cam_ctrl ; $i++) {
				if($i > 0) $sql .= ' OR ';
				$sql .= 'camera=' . $cam_ctrl[$i];
			}
			$sql .= ')';
		}
		
		// Scartiamo le prenotazioni già presenti nel db che stiamo trattando adesso
		if($num_id_pre > 0) {
			$sql .= ' AND (';
			for($i = 0 ; $i < $num_id_pre ; $i++) {
				if($i > 0) $sql .= ' AND ';
				$sql .= 'id!=' . $id_pre[$i];
			}
			$sql .= ')';
		}
		
		$sql .= ' ORDER BY camera';
	
		// Repuceriamo i dati per controllo overbooking
		$db = db_connect();
		$pre_over_grezze = $db->query($sql);
		$db->connection = NULL;
		
		$pre_over = $pre_over_grezze->fetchAll(PDO::FETCH_ASSOC);
		$num_pre_over = count($pre_over);
		
		// Se sono state trovate prenotazioni per quel periodo
		if($num_pre_over > 0) {
			
			for($i = 0 ; $i < $num_pre ; $i++) {
				// Se la camera non è soggetta all'overbooking si skippa (date non valide, nessuna camera, share)
				if($err_date[$i] == TRUE || $pre[$i]['camera'] == 0 || ($pre[$i]['tipo_pre'] >= 30 && $pre[$i]['tipo_pre'] <= 39)) continue;
				
				// Controlliamo nelle altre prenotazioni presenti nel db
				for($j = 0 ; $j < $num_pre_over ; $j++) {
					if($pre_over[$j]['camera'] > $pre[$i]['camera']) break; // Se abbiamo superato i possibili over
					
					if($pre_over[$j]['camera'] == $pre[$i]['camera'] 
						&&(($pre_over[$j]['data_arrivo'] >= $pre[$i]['data_arrivo'] && $pre_over[$j]['data_arrivo'] < $pre[$i]['data_partenza']) //
						 ||($pre_over[$j]['data_partenza'] > $pre[$i]['data_arrivo'] && $pre_over[$j]['data_partenza'] < $pre[$i]['data_partenza']) //
						 ||($pre_over[$j]['data_arrivo'] <= $pre[$i]['data_arrivo'] && $pre_over[$j]['data_partenza'] >= $pre[$i]['data_partenza']))) {

						if($pre[$i]['problemi'] != '') $pre[$i]['problemi'] .= '#';
						$pre[$i]['problemi'] .= 2;
						break;
					}
				}
				// Controlliamo nelle prenotazioni stesse che stiamo esaminando
				for($j = $i+1 ; $j < $num_pre ; $j++) {
					if($pre[$j]['camera'] == $pre[$i]['camera'] // Skippiamo se stiamo esaminando la camera stessa in questione
						&&(($pre[$j]['data_arrivo'] >= $pre[$i]['data_arrivo'] && $pre[$j]['data_arrivo'] < $pre[$i]['data_partenza']) //
						 ||($pre[$j]['data_partenza'] > $pre[$i]['data_arrivo'] && $pre[$j]['data_partenza'] < $pre[$i]['data_partenza']) //
						 ||($pre[$j]['data_arrivo'] <= $pre[$i]['data_arrivo'] && $pre[$j]['data_partenza'] >= $pre[$i]['data_partenza']))) {

						if($pre[$i]['problemi'] != '') $pre[$i]['problemi'] .= '#';
						$pre[$i]['problemi'] .= ($tipo_pre + 2);
						
						if($pre[$j]['problemi'] != '') $pre[$j]['problemi'] .= '#';
						$pre[$j]['problemi'] .= ($tipo_pre + 2);
					}
				}
			}
		}
	}
}

// 1 = freccia Sinistra, 2 = freccia Destra, 3 = freccia Sinistra e Destra (cambi camera)
// decine = numero di camere aggiuntive
// centinaia = numero di share
function set_stile_pre(&$pre) {
	$num_pre = count($pre);
	$spe_trovate = FALSE;
	
	// Settiamo prima gli stili delle principali
	for($i = 0 ; $i < $num_pre ; $i++) {
		// Se è una speciale si skippa
		if($pre[$i]['tipo_pre'] > 9) {
			$spe_trovate = TRUE;
			continue;
		}
		
		$stile_cam_agg = 0;
		$stile_cambio_cam = 0;
		$stile_share = 0;
		
		for($j = 0 ; $j < $num_pre ; $j++) {
			// Si salta la prenotazione esaminata quando si trova o se siamo su una prenotazione non connessa
			if($j == $i || $pre[$j]['id_rif'] != $pre[$i]['id']) continue;
			
			// Se si tratta di una camera aggiuntiva
			if($pre[$j]['tipo_pre'] >= 10 && $pre[$j]['tipo_pre'] <= 19) {
				$stile_cam_agg += 10;
			}
			// Se si tratta di un cambio camera
			elseif($pre[$j]['tipo_pre'] >= 20 && $pre[$j]['tipo_pre'] <= 29) {
				if($stile_cambio_cam == 3) continue; // Se ci sono già le due frecce si continua
				if($pre[$j]['data_arrivo'] < $pre[$i]['data_arrivo']) {
					if($stile_cambio_cam == 0)		 $stile_cambio_cam = 1; // Se non ci sono frecce si assegna <
					elseif($stile_cambio_cam == 1) continue; // Se la freccia < è già presente si continua
					elseif($stile_cambio_cam == 2) $stile_cambio_cam = 3; // Se la freccia > è già presente si assegnano le due frecce
				}
				if($pre[$j]['data_partenza'] > $pre[$i]['data_partenza']) {
					if($stile_cambio_cam == 0)		 $stile_cambio_cam = 2; // Se non ci sono frecce si assegna >
					elseif($stile_cambio_cam == 2) continue; // Se la freccia > è già presente si continua
					elseif($stile_cambio_cam == 1) $stile_cambio_cam = 3; // Se la freccia < è già presente si assegnano le due frecce
				}
			}
			// Se si tratta di uno share
			if($pre[$j]['tipo_pre'] >= 30 && $pre[$j]['tipo_pre'] <= 39) {
				$stile_share += 100;
			}
		}
echo 'PRINCIPALE ['.$stile_cambio_cam.']';
		$pre[$i]['stile_spe'] = $stile_cam_agg + $stile_cambio_cam + $stile_share;
	}
	
	// Si setta lo stile delle speciali se ce ne sono
	if($spe_trovate == TRUE) {
		for($i = 0 ; $i < $num_pre ; $i++) {
			// Se è una principale o se è uno share si skippa
			if($pre[$i]['tipo_pre'] <= 9 || ($pre[$i]['tipo_pre'] >= 30 && $pre[$i]['tipo_pre'] <= 39)) {
				continue;
			}
			
			$stile_cam_agg = 0;
			$stile_cambio_cam = 0;
			$stile_share = 0;
			
			for($j = 0 ; $j < $num_pre ; $j++) {
				if($j == $i					// Se siamo alla prenotazione stessa che si esamina
					|| ($pre[$i]['id_rif'] != $pre[$j]['id'] && $pre[$i]['id_rif'] != $pre[$j]['id_rif']) // se la pre non è connessa
					|| ($pre[$j]['tipo_pre'] >= 30 && $pre[$j]['tipo_pre'] <= 39)) // Se si tratta di uno share
					continue;
				
				// Se si tratta della principale connessa si recuperano i valori di stile_spe comuni
				if($pre[$i]['id_rif'] == $pre[$j]['id']) {
					$stile_cam_agg = floor(($pre[$j]['stile_spe'] % 100) / 10) * 10;
					$stile_share 	= floor($pre[$j]['stile_spe'] / 100) * 100;
					
					// Se la prenotazione in $i è una camera aggiuntiva si settano le stesse frecce della principale
					if($pre[$i]['tipo_pre'] >= 10 && $pre[$i]['tipo_pre'] <= 19) {
						$stile_cambio_cam = $pre[$j]['stile_spe'] % 10;
						break;
					}
				}
				
				// Settiamo le frecce
				if($stile_cambio_cam == 3) continue; // Se ci sono già le due frecce si continua
				if($pre[$i]['data_arrivo'] >= $pre[$j]['data_partenza']) {
					if($stile_cambio_cam == 0)		 $stile_cambio_cam = 1; // Se non ci sono precce si assegna <
					elseif($stile_cambio_cam == 1) continue; // Se la freccia < è già presente si continua
					elseif($stile_cambio_cam == 2) $stile_cambio_cam = 3; // Se la freccia > è già presente si assegnano le due frecce
				}
				if($pre[$i]['data_partenza'] <= $pre[$j]['data_arrivo']) {
					if($stile_cambio_cam == 0)		 $stile_cambio_cam = 2; // Se non ci sono precce si assegna >
					elseif($stile_cambio_cam == 2) continue; // Se la freccia > è già presente si continua
					elseif($stile_cambio_cam == 1) $stile_cambio_cam = 3; // Se la freccia < è già presente si assegnano le due frecce
				}
			}
			
echo ' - spe '.date('d/m',$pre[$i]['data_partenza']).' ['.$stile_cambio_cam.']';
			
			$pre[$i]['stile_spe'] = $stile_cam_agg + $stile_cambio_cam + $stile_share;
		}
	}
}

// CAMPI NECESSARI: NOME, GRUPPO, AGENZIA, DATA_ARRIVO, DATA_PARTENZA, CAMERA, PROBLEMI
// PRINT PROBLEMI MODIFICATO E OTTIMIZZATO PER PRENOTAZIONI.PHP
// NO FORMATTA VISUALIZZAZIONE.. DA FARE UNA SOLA VOLTA SU TUTTE LE PRE
function print_problemi(&$info_pre) {
	$num_pre = count($info_pre);
	$giorni_settimana = &$_SESSION['giorni'];
	$stampa_problemi = '';
	$tot_problemi = 0;
	$problemi_possibili = array();
	$problemi_possibili[0] = 'CAMERA SCELTA INESISTENTE';
	$problemi_possibili[1] = 'DATE INCOERENTI';
	$problemi_possibili[2] = 'CAMERA GI&Agrave; OCCUPATA';
	$problemi_possibili[3] = 'PAX MAGGIORE A QUELLO DELLA CAMERA';
	$problemi_possibili[4] = 'TIPOLOGIA DIVERSA DA QUELLA DELLA CAMERA';
	
	$problemi_possibili[10] = 'CAMERA AGGIUNTIVA: CAMERA SCELTA INESISTENTE';
	$problemi_possibili[12] = 'CAMERA AGGIUNTIVA: CAMERA GI&Agrave; OCCUPATA';
	$problemi_possibili[13] = 'CAMERA AGGIUNTIVA: PAX MAGGIORE A QUELLO DELLA CAMERA';
	
	$problemi_possibili[20] = 'CAMBIO CAMERA: CAMERA SCELTA INESISTENTE';
	$problemi_possibili[21] = 'CAMBIO CAMERA: DATE INCOERENTI';
	$problemi_possibili[22] = 'CAMBIO CAMERA: CAMERA GI&Agrave; OCCUPATA';
	$problemi_possibili[23] = 'CAMBIO CAMERA: PAX MAGGIORE A QUELLO DELLA CAMERA';
	
	$problemi_possibili[31] = 'SHARE: DATE INCOERENTI';
	$problemi_possibili[32] = 'SHARE: CAMERA GI&Agrave; OCCUPATA';
	$problemi_possibili[33] = 'SHARE: PAX MAGGIORE A QUELLO DELLA CAMERA';
	
	for($i = 0 ; $i < $num_pre ; $i++) {
		if($info_pre[$i]['problemi'] == '') continue;
		
		$problema_sel = explode('#', $info_pre[$i]['problemi']);
		$num_problemi_pre = count($problema_sel);
		$tot_problemi += $num_problemi_pre;
		
		$stampa_problemi .= '<div class="problemi_pre">';
		
		$stampa_problemi .= '<p class="testa_problema">';
		
		$stampa_problemi .= '<span class="progressivo">' . $info_pre[$i]["camera"] . '</span> '//
							  .'<b>'.$giorni_settimana[date('w', $info_pre[$i]['data_arrivo'])].'</b> '.date('d/m', $info_pre[$i]['data_arrivo']) . ' '
							  .'<b>'.$giorni_settimana[date('w', $info_pre[$i]['data_arrivo'])].'</b> '.date('d/m', $info_pre[$i]['data_partenza']).'';
		
		if($info_pre[$i]['gruppo'] != 0) {
			$stampa_problemi .= ' <span class="pb_gruppo c_'.$info_pre[$i]['colore_gruppo'].'"><a href="ge_gru.php?mg='.$info_pre[$i]['gruppo'].'">'.$info_pre[$i]['nome_gruppo'].'</a></span> ';
		}
		if($info_pre[$i]['nome'] != '') {
			if($info_pre[$i]['id_rif'] != 0) $stampa_problemi .= ' <span class="pb_nome"><a href="ge_pre.php?mp='.$info_pre[$i]['id_rif'].'">'.$info_pre[$i]['nome'].'</a></span> ';
			else 										$stampa_problemi .= ' <span class="pb_nome"><a href="ge_pre.php?mp='.$info_pre[$i]['id'].'">'.$info_pre[$i]['nome'].'</a></span> ';
		}

		if($info_pre[$i]['agenzia'] != '') $stampa_problemi .= '<span class="pb_agenzia">'.$info_pre[$i]['agenzia'].'</span>';
		$stampa_problemi .= '</p>';
		
		
		for($j = 0 ; $j < $num_problemi_pre ; $j++) {
			$stampa_problemi .= '<p class="prob">' . $problemi_possibili[$problema_sel[$j]] . '</p>';
		}
		
		$stampa_problemi .= '</div>';
	}

	if($tot_problemi > 1)
		return '<div class="tutti_problemi"><p class="num_problemi">'.$tot_problemi.'</p>'.$stampa_problemi.'</div>';

	elseif($tot_problemi == 1)
		return '<div class="tutti_problemi"><p class="num_problemi">1</p>'.$stampa_problemi.'</div>';
	
	else return '';
}

function print_note_gruppi(&$info_pre, &$giorni_settimana) {
	$num_pre = count($info_pre);
	$tot_note = 0;
	$stampa_note = '';

	for($i = 0 ; $i < $num_pre ; $i++) {
		// Se questa prenotazione non ha note
		if($info_pre[$i]['note_gruppo'] == '') continue;
		
		// Se le note sono già stampate per questo gruppo
		$gia_stampato = FALSE;
		for($j = 0 ; $j < $i ; $j++) {
			if($info_pre[$j]['gruppo'] == $info_pre[$i]['gruppo']) { $gia_stampato = TRUE; break; }
		}
		if($gia_stampato == TRUE) continue;
		
		$tot_note++;
		
		$stampa_note .= '<div class="note_gru">' //
							. '<p class="testa_nota">' //
							. '<span class="progressivo">'//
							. '<b>'.$giorni_settimana[date('w', $info_pre[$i]['data_arrivo'])].'</b> '.date('d/m', $info_pre[$i]['data_arrivo']) . ' '//
							. '<b>'.$giorni_settimana[date('w', $info_pre[$i]['data_partenza'])].'</b> '.date('d/m', $info_pre[$i]['data_partenza'])//
							. '</span>'//
							. '</p> '//
							. '<p class="gru_sx">'//
							. '<span class="gp_nome c_'.$info_pre[$i]['colore_gruppo'].'"><a href="ge_gru.php?mg='.$info_pre[$i]['gruppo'].'">'.formatta_visualizzazione($info_pre[$i]['nome_gruppo']).'</a></span>'//
							. '<span class="gru_not">'.formatta_visualizzazione($info_pre[$i]['note_gruppo']).'</span>'//
							. '</p>'//
							. '</p>'//
							. '</div>';
	}

	if($tot_note > 0) {
		return '<div class="all_note"><p class="num_note_gruppi">!' . $tot_note . '</p>' . $stampa_note . '</div>';
	}
}

// YOU ROCK!! - Aggiorna table prenotazioni con i giusti livelli di overbooking
// Problema nella gestione dei livelli pre quando le prenotazioni che entrano nel periodo sono a loro volta a ridosso di altre prenotazioni precedenti
// Per risolverlo bisognerebbe integrare recursivitàm col risco di aumentare esponenzialmente la forchetta di date
function gestione_overbooking(&$pre, &$num_pre) {
	$before_overbooking = microtime(true);
	
	// po = possibili overbooking
	$po_data_partenza = array();
	$po_livello = array();
	$camera_precedente = -1;
	
	// Si analizza la singola prenotazione rispetto a quella precedente
	for($i = 0 ; $i < $num_pre ; $i++) {
		// Se si tratta di uno share si skippa il controllo overbooking
		if($pre[$i]['tipo_pre'] >= 30 && $pre[$i]['tipo_pre'] < 39) continue;

		// Se siamo ad un cambio camera resettiamo le veriabili
		if($camera_precedente != $pre[$i]['camera']) {
			unset($po_data_partenza);
			unset($po_livello);
			
			$po_data_partenza = array();
			$po_livello = array();
			
			$camera_precedente = $pre[$i]['camera'];
			$num_po = 0;			// Si ricomincia da capo
			$livello_over = 0;	// Si azzera il livello di overbooking
		}
		
		// Eliminiamo le prenotazioni non più in po
		else {
			for($j = 0 ; $j < $num_po ; $j++) {
				// Si elimina il possibile overbooking non più pericoloso
				if($pre[$i]['data_arrivo'] >= $po_data_partenza[$j]) 	{ unset($po_data_partenza[$j]); unset($po_livello[$j]); }
			}
			// Riorganizziamo gli array per avere un progressivo sequenziale per livello crescente
			$po_data_partenza = array_values($po_data_partenza);
			$po_livello = array_values($po_livello);
			
			$num_po = count($po_data_partenza);
			
			// Riorganiziamo i po per averli in ordine di livello crescente
			for($z = 0 ; $z < $num_po ; $z++) {
				for($j = $z + 1 ; $j < $num_po ; $j++) {
					if($po_livello[$j] < $po_livello[$z]) {
						$buff_lvl = $po_livello[$j];
						$buff_d_p = $po_data_partenza[$j];
						
						$po_livello[$j] = $po_livello[$z];
						$po_data_partenza[$j] = $po_data_partenza[$z];
						
						$po_livello[$z] = $buff_lvl;
						$po_data_partenza[$z] = $buff_d_p;
					}
				}
			}
			
		}
		//echo '---------num_po:'.$num_po.'--------<br />';
		// Si vede qual'è il livello di overbooking, 0 = no overbooking
		for($j = 0 ; $j < $num_po ; $j++) {
			
			// Se c'è uno spazio libero ci inseriamo la prenotazione lasciando $j a quello spazio
			if($j < $po_livello[$j] && ($j == 0 || $j - 1 != $po_livello[$j]))	{/*echo '$$$$';*/ break; }
			//else echo '@';
		}
		//echo 'id: ' . $pre['id'] . ' lvl:' . $j . '<br />';
		
		// Aggiorniamo i possibili overbooking
		$po_data_partenza[] = $pre[$i]['data_partenza'];
		$po_livello[] = $j;
		$num_po++;
		
		// Assegnamo alla prenotazione il suo livello
		$pre[$i]['livello'] = $j;
	}
}

function camere_disponibili(&$data_arrivo, &$data_partenza, &$cam_all = NULL) {
	$camere_occupate = NULL;
	$occupazione_esistente = FALSE;
	$nuova_riga = FALSE;
	$cam_dispo = array();
	$num_vest_tipologia = 0;
	
	// Creamo una copia della lista camere
	$cam = $_SESSION['cam'];
	$num_cam = $_SESSION['num_cam'];
	
	
	$db = db_connect();
	// Se stiamo cercando le camere disponibili
	if($data_arrivo != NULL && $data_partenza != NULL) {
		// Recuperiamo le prenotazioni per quelle date
		$sql = 'SELECT camera FROM prenotazioni WHERE ';
		$sql .=  '((data_arrivo >= '.$data_arrivo.' && data_arrivo < '.$data_partenza.') || ' //
			  . ' (data_partenza > '.$data_arrivo.' && data_partenza <= '.$data_partenza.') || ' //
			  . ' (data_arrivo < '.$data_arrivo.' && data_partenza > '.$data_partenza.'))';
		$sql .=  ' AND camera != 0 ORDER BY camera ASC';
		
		$risposta_pre = $db->query($sql);
		$db->connection = NULL;
		unset($sql);
		
		// Creiamo una tabella con le camere occupate
		for($i = 0 ; $pre = $risposta_pre->fetch() ; ) {
			// Se siamo alla prima camera occupata
			if($occupazione_esistente == FALSE)	{
				$camere_occupate[] = $pre['camera'];
				$occupazione_esistente = TRUE;
				$i++;
			}
			// Se la camera non è già stata inserita in quelle occupate
			elseif($camere_occupate[$i-1] != $pre['camera']) {
				$camere_occupate[] = $pre['camera'];
				$i++;
			}
		}
		
		$num_cam_occ = count($camere_occupate);
	}

	// Se vogliamo soltanto un riepilogo delle camere
	else {
		$db->connection = NULL;
		$num_cam_occ = 0;
	}
	
	
	// Inizializiamo le variabili necessarie
	$cam_dispo['totali']['camere'] = 0;
	$cam_dispo['totali']['pax'] = 0;
	
	// Creiamo una tabella con le camere libere
	for($i = 0, $num_cam_occupata = 0 ; $i < $num_cam ; $i++) {
		if($num_cam_occupata < $num_cam_occ && $cam[$i]['numero'] == $camere_occupate[$num_cam_occupata]) { $num_cam_occupata++; continue; }
		
		$nuova_riga = TRUE;
		
		// Si cerca nelle vestizioni e tipolige già esistenti
		for($j = 0 ; $j < $num_vest_tipologia ; $j++) {
			if($cam_dispo[$j]['vestizione'] == $cam[$i]['vestizione_max'] //
			&& $cam_dispo[$j]['tipologia'] == $cam[$i]['descrizione_breve'] && $cam_dispo[$j]['piano'] == $cam[$i]['piano']) {
				
				$cam_dispo[$j]['camere']++;
				$cam_dispo[$j]['pax'] += $cam[$i]['pax_max'];
				$cam_dispo[$j]['numeri_camere'] .= ' '.$cam[$i]['numero'];
			
				$cam_dispo['totali']['camere']++;
				$cam_dispo['totali']['pax'] += $cam[$i]['pax_max'];
				
				$nuova_riga = FALSE;
				break;
			}
		}
		
		// Si crea una nuova entrata per quella tipologia e vestizione specifica
		if($nuova_riga == TRUE) {
			$cam_dispo[$j]['vestizione'] = $cam[$i]['vestizione_max'];
			$cam_dispo[$j]['tipologia'] = $cam[$i]['descrizione_breve'];
			$cam_dispo[$j]['piano'] = $cam[$i]['piano'];
			$cam_dispo[$j]['camere'] = 1;
			$cam_dispo[$j]['pax'] = $cam[$i]['pax_max'];
			$cam_dispo[$j]['numeri_camere'] = $cam[$i]['numero'];
			
			$cam_dispo['totali']['camere']++;
			$cam_dispo['totali']['pax'] += $cam[$i]['pax_max'];
			
			$num_vest_tipologia++;
		}
	}
	
	return $cam_dispo;
}

function formatta_periodo(&$data_arrivo, &$data_partenza) {
	$arrivo_tab = explode('/', date('d/m/Y', $data_arrivo));
	$partenza_tab = explode('/', date('d/m/Y', $data_partenza));
	
	// Se rimaniamo nello stesso mese
	if($arrivo_tab[1] == $partenza_tab[1]) {
		// Se siamo nel medesimo anno di quello attuale
		if($partenza_tab[2] == $_SESSION['oggi_anno'])
			return '<b>'.$_SESSION['giorni'][date('w', $data_arrivo)].'</b> '.$arrivo_tab[0].' <b>'.$_SESSION['giorni'][date('w', $data_partenza)].'</b> '.$partenza_tab[0].' '.$_SESSION['mesi'][$partenza_tab[1]-1];
		else
			return '<b>'.$_SESSION['giorni'][date('w', $data_arrivo)].'</b> '.$arrivo_tab[0].' <b>'.$_SESSION['giorni'][date('w', $data_partenza)].'</b> '.$partenza_tab[0].' '.$_SESSION['mesi'][$partenza_tab[1]-1].' '.substr($partenza_tab[2], 2);
	}
	else {
		// Se le due date sono sullo stesso anno
		if($arrivo_tab[2] == $partenza_tab[2]) {
			// Se siamo sull'anno corrente
			if($partenza_tab[2] == $_SESSION['oggi_anno']) return '<b>'.$_SESSION['giorni'][date('w', $data_arrivo)].'</b> '.$arrivo_tab[0].'/'.$arrivo_tab[1].' <b>'.$_SESSION['giorni'][date('w', $data_partenza)].'</b> '.$partenza_tab[0].'/'.$partenza_tab[1];
			else 														  return '<b>'.$_SESSION['giorni'][date('w', $data_arrivo)].'</b> '.$arrivo_tab[0].'/'.$arrivo_tab[1].' <b>'.$_SESSION['giorni'][date('w', $data_partenza)].'</b> '.$partenza_tab[0].'/'.$partenza_tab[1].'/'.substr($partenza_tab[2], 2);
			
		}
		else  return '<b>'.$_SESSION['giorni'][date('w', $data_arrivo)].'</b> '.$arrivo_tab[0].'/'.$arrivo_tab[1].' <b>'.$_SESSION['giorni'][date('w', $data_partenza)].'</b> '.$partenza_tab[0].'/'.$partenza_tab[1].'/'.substr($partenza_tab[2], 2);
	}
}
?>