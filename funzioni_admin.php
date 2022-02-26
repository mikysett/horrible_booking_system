<?php
// Per i test il valore dev'essere On, invice quando il sito è online deve essere off
ini_set('display_errors', 'On');
ini_set('default_charset', 'utf-8');
date_default_timezone_set('Europe/Rome');

// unico punto per connessione al database
function db_connect() {
	// dati per connessione al database in locale
	return $db = new PDO('mysql:host='.$_SESSION['host'].';dbname='.$_SESSION['dbname'].';charset=utf8', $_SESSION['user'], $_SESSION['pass']);
		
	// dati per connessione al database su ovh
	// return $db = new PDO('mysql:host=mysql51-51.perso;dbname=mascheranova;charset=utf8', 'mascheranova', '5bb90sn');
}

// carica i valori di default
function valori_default() {
	// Per il Database
	$_SESSION['host'] 	= 'localhost';
	$_SESSION['dbname'] = 'booking';
	$_SESSION['user'] 	= 'phpmyadmin';
	$_SESSION['pass'] 	= 'root';
	
	// Generici
	$_SESSION['nome_struttura'] = 'HOTEL NAME HERE';
	$_SESSION['inizio_estate']	 = mktime(0, 0, 0, 7, 1, 2016);
	$_SESSION['fine_estate']	 = mktime(0, 0, 0, 9, 4, 2016);
	$_SESSION['inizio_inverno'] = mktime(0, 0, 0, 12, 1, 2017);
	$_SESSION['fine_inverno']	 = mktime(0, 0, 0, 4, 8, 2018);
	$_SESSION['giorno_cambio']	 = 6; // Sabato
	
	// Per motore di booking
	$_SESSION['cella_w']	 = 30; // Larghezza
	$_SESSION['cella_h']	 = 16; // Altezza, prima 28
	$_SESSION['show_cam'] = TRUE;
	
	// Per la ricerca nelle prenotazioni
	$_SESSION['li_pre_periodo'] = 'tutti';
	$_SESSION['li_pre_tipo'] = 'privati';
	$_SESSION['li_pre_nome'] = '';
	$_SESSION['li_pre_agenzia'] = '';
	$_SESSION['li_pre_data_arrivo'] = '';
	$_SESSION['li_pre_data_partenza'] = '';
	$_SESSION['li_pre_pagina'] = 0;
	
	// Per le date
	$_SESSION['mesi'] = array('GEN', 'FEB', 'MAR', 'APR', 'MAG', 'GIU', 'LUG', 'AGO', 'SET', 'OTT', 'NOV', 'DIC');
	$_SESSION['giorni'] = array('Do', 'Lu', 'Ma', 'Me', 'Gi', 'Ve', 'Sa');
	
	// Recuperiamo la table delle camere per velocizzare poi tutti i processi
	$db = db_connect();
	$risposta_cam = $db->query('SELECT * FROM camere WHERE numero > 0 ORDER BY numero ASC');
	$db->connection = NULL;
	
	$_SESSION['cam'] = $risposta_cam->fetchAll(PDO::FETCH_ASSOC);
	$_SESSION['num_cam'] = count($_SESSION['cam']);
}

// formatta il testo per la corretta visualizzazione al momento della stampa su schermo
function formatta_visualizzazione(&$testo_grezzo) {
	return stripslashes(htmlspecialchars_decode($testo_grezzo));
}

// Formatta testo per visualizzazione + non breaking spaces
function set_visual(&$testo_grezzo) {
	return str_replace(' ', '&nbsp;', stripslashes(htmlspecialchars_decode($testo_grezzo)));
}

function formatta_salvataggio(&$testo_grezzo) {
	return htmlentities(strtoupper(addslashes($testo_grezzo)), ENT_COMPAT, "UTF-8");
}

function formatta_edizione(&$testo_grezzo) {
	return str_replace('"', '&quot;', $testo_grezzo);
}


function header_standard() { ?>
<link rel="stylesheet" href="layout/admin.css" type="text/css" />
<link rel="icon" href="favicon.png" type="image/png" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="content-language" content="it" />
<meta name="author" content="michele" />
<meta name="copyright" content="Michele Sessa" />
<meta name="robots" content="none" /><?php
}

function header_rl() { ?>
<link rel="stylesheet" href="layout/printing.css" type="text/css" />
<link rel="icon" href="favicon.png" type="image/png" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="content-language" content="it" />
<meta name="author" content="michele" />
<meta name="copyright" content="Michele Sessa" />
<meta name="robots" content="none" /><?php
}

function header_mo() { ?>
<link rel="stylesheet" href="layout/movimenti.css" type="text/css" />
<link rel="icon" href="favicon.png" type="image/png" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="content-language" content="it" />
<meta name="author" content="michele" />
<meta name="copyright" content="Michele Sessa" />
<meta name="robots" content="none" /><?php
}

function header_gra_cal() { ?>
<link rel="stylesheet" href="layout/gratuita_cal.css" type="text/css" />
<link rel="icon" href="favicon.png" type="image/png" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="content-language" content="it" />
<meta name="author" content="michele" />
<meta name="copyright" content="Michele Sessa" />
<meta name="robots" content="none" /><?php
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
		if($num_data_smistata <= 2 || ($num_data_smistata == 3 && is_numeric($data_smistata[2]) == false)) {
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

function controllo_data_ora(&$data, $ora) {
	$data_smistata = explode("/", str_replace(array(".", ",", ";", "-", "_"), "/", $data));
	
	$ora_smistata = explode(":", $ora);
	
	$data_corretta = mktime($ora_smistata[0], $ora_smistata[1], 0, $data_smistata[1], $data_smistata[0], $data_smistata[2]);
	
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
					if($pre[$j]['tipo_pre'] >= 30 && $pre[$j]['tipo_pre'] <= 39) continue; // Se stiamo comparando a uno share saltiamo
					
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
			
			$pre[$i]['stile_spe'] = $stile_cam_agg + $stile_cambio_cam + $stile_share;
		}
	}
}

// CAMPI NECESSARI: NOME, GRUPPO, AGENZIA, DATA_ARRIVO, DATA_PARTENZA, CAMERA, PROBLEMI
function print_problemi(&$info_pre, &$pagina) {
	$num_pre = count($info_pre);
	$giorni_settimana = array('Do', 'Lu', 'Ma', 'Me', 'Gi', 'Ve', 'Sa');
	$tot_problemi = 0;
	$stampa_problemi = '';
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
		$prog = $i + 1;
		if($info_pre[$i]['problemi'] == '') continue;
		
		$problema_sel = explode('#', $info_pre[$i]['problemi']);
		$num_problemi_pre = count($problema_sel);
		$tot_problemi += $num_problemi_pre;
		
		$stampa_problemi .= '<div class="problemi_pre">';
		
		$stampa_problemi .= '<p class="testa_problema">';
		if($pagina == 'gestione_gruppi') $stampa_problemi .= '<span class="progressivo">'.$prog.': </span>';
		elseif($pagina == 'prenotazioni') {
			$stampa_problemi .= '<span class="progressivo">' . $info_pre[$i]["camera"] . '</span> '//
								  .'<b>'.$giorni_settimana[date('w', $info_pre[$i]['data_arrivo'])].'</b> '.date('d/m', $info_pre[$i]["data_arrivo"]) . ' ' //
								  .'<b>'.$giorni_settimana[date('w', $info_pre[$i]['data_arrivo'])].'</b> '.date('d/m', $info_pre[$i]["data_partenza"])//
								  . ' | ';
			
		}
		$stampa_problemi .= '<span class="pb_nome">' . formatta_visualizzazione($info_pre[$i]['nome']) . '</span> ';
		if($info_pre[$i]['nome'] != '' && $info_pre[$i]['agenzia'] != '' && $info_pre[$i]['nome_gruppo'] == NULL) $stampa_problemi .= '<span class="pb_separator"> - </span>';
		if($pagina == 'prenotazioni' && $info_pre[$i]['nome_gruppo'] != NULL) {
			$stampa_problemi .= '<span class="pb_gruppo c_'.$info_pre[$i]['colore_gruppo'].'">' . formatta_visualizzazione($info_pre[$i]['nome_gruppo']) . '</span> ';
		}
		if($info_pre[$i]['agenzia'] != '') $stampa_problemi .= '<span class="pb_agenzia">' . formatta_visualizzazione($info_pre[$i]['agenzia']) . '</span>';
		$stampa_problemi .= '</p>';
		
		
		for($j = 0 ; $j < $num_problemi_pre ; $j++) {
			$stampa_problemi .= '<p class="prob">' . $problemi_possibili[$problema_sel[$j]] . '</p>';
		}
		
		$stampa_problemi .= '</div>';
	}

	if($tot_problemi > 1) {
		if($pagina == 'prenotazioni') $stampa_problemi = '<div class="tutti_problemi"><p class="num_problemi">' . $tot_problemi . '</p>' . $stampa_problemi . '</div>';
		else 									$stampa_problemi = '<div class="tutti_problemi"><p class="num_problemi">' . $tot_problemi . ' PROBLEMI</p>' . $stampa_problemi . '</div>';
		return $stampa_problemi;
	}

	elseif($tot_problemi == 1) {
		if($pagina == 'prenotazioni') $stampa_problemi = '<div class="tutti_problemi"><p class="num_problemi">1</p>' . $stampa_problemi . '</div>';
		else 									$stampa_problemi = '<div class="tutti_problemi"><p class="num_problemi">1 PROBLEMA</p>' . $stampa_problemi . '</div>';
		return $stampa_problemi;
	}
	
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

/* backup the db OR just a table */
function backup_tables($tables = '*')
{
	$return = "";
	$db = db_connect();
	
	//get all of the tables
	if($tables == '*')
	{
		$tables = array();
		$result = $db->query('SHOW TABLES');
		while($row = $result->fetch())
		{
			$tables[] = $row[0];
		}
	}
	else
	{
		$tables = is_array($tables) ? $tables : explode(',',$tables);
	}
	
	//cycle through
	foreach($tables as $table)
	{
		$result = $db->query('SELECT * FROM '.$table);
		$num_fields = $result->columnCount();
		
		$return.= 'DROP TABLE '.$table.';';
		$row2 = $db->query('SHOW CREATE TABLE '.$table);
		$row2 = $row2->fetch();
		$return.= "\n\n".$row2[1].";\n\n";
		
		for ($i = 0; $i < $num_fields; $i++) 
		{
			for($x = 0 ; $row = $result->fetch() ; $x++)
			{
				if($x == 0) $return.= 'INSERT INTO '.$table.' VALUES';
				else 			$return.= ',';
				$return.= "\n(";
				for($j=0; $j < $num_fields; $j++) 
				{
					$row[$j] = addslashes($row[$j]);
					$row[$j] = str_replace("\n","\\n",$row[$j]);
					if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
					if ($j < ($num_fields-1)) { $return.= ','; }
				}
				$return.= ")";
			}
			if($x > 0) $return.= ';';
			
		}
		$return.="\n\n\n";
	}
	
	//save file
	$file_name = 'backup_booking_'.date("d-m-y_H-i", time()).'.sql';
	$handle = fopen($file_name,'w+');
	fwrite($handle,$return);
	
	fclose($handle);
	
	
	header("Cache-Control: public");
   header("Content-Description: File Transfer");
   header("Content-Disposition: attachment; filename= " . $file_name);
   header("Content-Transfer-Encoding: binary");
   // Leggo il contenuto del file
   readfile($file_name);
   
   unlink($file_name);
}

function colonna_sinistra(&$pulsanti, $stampa_problemi, &$liste, $testo_non_elaborato) {
	$current_url = $_SERVER['REQUEST_URI'];
	
	$colonna_sx = "";
	
	$colonna_sx = '<div class="colonna_sx">';
	
	if($pulsanti != "") {
		$colonna_sx .= '<div class="pulsanti_sx">';
		$colonna_sx .= $pulsanti;
		$colonna_sx .= '</div>';
	}
	
	if($stampa_problemi != "") {
		$colonna_sx .= '<div class="problemi_sx">';
		$colonna_sx .= $stampa_problemi;
		$colonna_sx .= '</div>';
	}
	
	if(is_array($liste) && array_key_exists(0, $liste)) {
		$num_liste = count($liste);
		
		for($i = 0 ; $i < $num_liste ; $i++) {
			// Una riga è per i totali
			$num_righe = count($liste[$i]) - 1;
			
			$colonna_sx .= '<div class="liste_sx">';
			
			$colonna_sx .= '<table class="lista_sx">';
			$colonna_sx .= '<tr class="testa"><th class="titolo_lista_sx">RIEPILOGO</th><th>CAM</th><th>NA</th><th>PAX</th></tr>';
			
			for($j = 0 ; $j < $num_righe ; $j++) {
				$colonna_sx .= '<tr class="riga">';
				
				$colonna_sx .= '<td class="tipo">';
				if($liste[$i][$j]["vestizione"] == "") $colonna_sx .= "?";
				else 												$colonna_sx .= $liste[$i][$j]["vestizione"];
				if($liste[$i][$j]["tipologia"] != "")  $colonna_sx .= "+" . $liste[$i][$j]["tipologia"];
				$colonna_sx .= '</td>';
				
				$colonna_sx .= '<td>' . $liste[$i][$j]["camere"] . '</td>';
				if($liste[$i][$j]["na"] > 0) $colonna_sx .= '<td class="clr_red">';
				else 								  $colonna_sx .= '<td>';
				$colonna_sx .= $liste[$i][$j]["na"] . '</td>';
				$colonna_sx .= '<td>' . $liste[$i][$j]["pax"] . '</td>';
				$colonna_sx .= '</tr>';
			}
			
			// Stampiamo i totali
			$colonna_sx .= '<tr class="totali"><td class="tipo">TOTALI</td><td>' . $liste[$i]["totali"]["camere"] . '</td>';
			
			if($liste[$i]["totali"]["na"] > 0) $colonna_sx .= '<td class="clr_red">';
			else 								  $colonna_sx .= '<td>';
			$colonna_sx .= $liste[$i]["totali"]["na"] . '</td>';
			
			$colonna_sx .= '<td>' . $liste[$i]["totali"]["pax"] . '</td>';
			
			$colonna_sx .= '</tr>';
			
			$colonna_sx .= '</table>';
			
			$colonna_sx .= '</div>';
		}
	}
	
	$colonna_sx .= $testo_non_elaborato;
	
	$colonna_sx .= '</div>';

	return $colonna_sx;
}

function menu_top(&$pagina) {
	$giorni_settimana = array("DOM", "LUN", "MAR", "MER", "GIO", "VEN", "SAB");
	
	echo '<div class="testata_booking">';
	
	echo '<a href="menu.php">MENU</a>';

	echo $pagina;
	
	echo '<p class="data_refresh">';
	echo $giorni_settimana[date("w", $_SESSION['oggi'])].' '.$_SESSION['oggi_giorno'].'/'.$_SESSION['oggi_mese'].' | ';
	echo '<a href="disconnettersi.php">LOGOUT</a>';
	echo '</p>';
	
	echo '</div>';
}

function assegna_colori_camere($db) {
	$camere = $db->query('SELECT * FROM camere WHERE numero!=0 ORDER BY piano, ubicazione');
	$colore_principale = 0;
	$sottocolore = 0;
	$array_update = array();
	
	$num_colori = 6;
	$num_sottocolori = 6;
	$colors = array();
	$colors[] = array('FFD8AA', 'FBC17F', 'EFB573', 'FFB764', 'FCCB93', 'F09C3B');
	$colors[] = array('D0FF4B', 'C9F550', 'B8E442', 'DAFA81', 'C4E46A', 'E1FC99');
	$colors[] = array('99C7FC', '70A8E8', '70B3FF', 'BFDDFF', '56A3FB', 'AEC8E4');
	$colors[] = array('FFB2F3', 'E292D5', 'FF9AEF', 'EE73D9', 'E4BADD', 'CE92C4');
	$colors[] = array('FFF25C', 'FFF795', 'E5DA5D', 'FFED1F', 'FFF8A0', 'FFEE24');
	$colors[] = array('C1FFFE', 'ABEEEC', '7DDFDB', '77FFFB', '74E0DC', '2FFFF9');
	
	for($num_camere = 0 ; $camera = $camere->fetch() ; $num_camere++) {

		$id = $camera['id'];
		$piano = $camera['piano'];
		$ubicazione = $camera['ubicazione'];
		
		if($num_camere == 0) {
			// Si inizializzano i valori
			$piano_vecchio = $piano;
			$ubicazione_vecchia = $ubicazione;
		}
		
		if($piano != $piano_vecchio) {
			$sottocolore = 0;
			$colore_principale++;
			if($colore_principale >= $num_colori) $colore_principale = 0;
			
			$piano_vecchio = $piano;
			$ubicazione_vecchia = $ubicazione;
		}
		
		elseif($ubicazione != $ubicazione_vecchia) {
			$sottocolore++;
			if($sottocolore >= $num_sottocolori) $sottocolore = 0;
			
			$ubicazione_vecchia = $ubicazione;
		}

		$array_update[$num_camere]['id'] = $id;
		$array_update[$num_camere]['colore'] = $colors[$colore_principale][$sottocolore];
		
	}
	
	// Si modificano tutti gli elementi nel db
	$sql = 'UPDATE camere A inner join(';
	
	for($i = 0 ; $i < $num_camere ; $i++) {
		if($i > 0)  { $sql .= ' UNION '; }
		
		if($i == 0)
   		$sql .= ' SELECT ' . $array_update[$i]['id'] . ' id, \'' . $array_update[$i]['colore'] . '\' colore';
		else
   		$sql .= ' SELECT ' . $array_update[$i]['id'] . ', \'' . $array_update[$i]['colore'] . '\'';
	}
	
	$sql .= ') B USING (id) SET A.colore = B.colore;';
	
	$request = $db->prepare($sql);
	$request->execute();
}
	
function riepilogo_gruppo(&$info_pre) {
	$num_pre = count($info_pre);
	$gruppo = array();
	$num_vest_tipologia = 0;
	$gruppo['totali']['camere'] = 0;
	$gruppo['totali']['pax'] = 0;
	$gruppo['totali']['na'] = 0;
	$gruppo['totali']['data_arrivo_generale'] = 0;
	$gruppo['totali']['data_partenza_generale'] = 0;
	// $gruppo['totali']['data_ultima_modifica'] = 0;
	
	for($i = 0 ; $i < $num_pre ; $i++) {
			
		// Controlliamo la data_arrivo_generale e la data_partenza_generale
		if($i == 0) {
			$gruppo['totali']['data_arrivo_generale'] = $info_pre[$i]['data_arrivo'];
			$gruppo['totali']['data_partenza_generale'] = $info_pre[$i]['data_partenza'];
		}
		// Si aggiornano le date generali solo se la pre è principale o se si tratta di un cambio camera
		elseif($info_pre[$i]['tipo_pre'] <= 9 || ($info_pre[$i]['tipo_pre'] >= 20 && $info_pre[$i]['tipo_pre'] <= 29)) {
			if($info_pre[$i]['data_arrivo'] < $gruppo['totali']['data_arrivo_generale'])		 $gruppo['totali']['data_arrivo_generale']   = $info_pre[$i]['data_arrivo'];
			if($info_pre[$i]['data_partenza'] > $gruppo['totali']['data_partenza_generale'])  $gruppo['totali']['data_partenza_generale'] = $info_pre[$i]['data_partenza'];
		}
		
		$nuova_riga = TRUE;

		// Se si tratta di una principale o di una camera aggiuntiva
		if($info_pre[$i]['tipo_pre'] <= 19) {
			// Si cerca nelle vestizioni e tipolige già esistenti
			for($j = 0 ; $j < $num_vest_tipologia ; $j++) {
	
				// Se la vestizione e tipoligia sono già presenti nell'array gruppo
				if($gruppo[$j]['vestizione'] == $info_pre[$i]['vestizione'] && $gruppo[$j]['tipologia'] == $info_pre[$i]['tipologia']) {
					
					$gruppo[$j]['camere']++;
					$gruppo['totali']['camere']++;
					
					// Se stiamo analizzando una principale e ci sono degli share connessi
					if($info_pre[$i]['tipo_pre'] <= 9 && $info_pre[$i]['stile_spe'] >= 100 && $info_pre[$i]['stile_spe'] <= 999) {
						$num_share = floor($info_pre[$i]['stile_spe'] / 100);
						
						for($z = 0, $share_trovati = 0 ; $z < $num_pre ; $z++) {
							if($info_pre[$z]['id_rif'] == $info_pre[$i]['id'] && $info_pre[$z]['tipo_pre'] >= 30 && $info_pre[$z]['tipo_pre'] <= 39) {
								if(!isset($gruppo[$j]['pax'])) $gruppo[$j]['pax'] = $info_pre[$z]['pax'];
								else 									 $gruppo[$j]['pax'] += $info_pre[$z]['pax'];
								$gruppo['totali']['pax'] += $info_pre[$z]['pax'];
								
								$share_trovati++;
								if($share_trovati == $num_share) break;
							}
						}
					}
					else {
						if(!isset($gruppo[$j]['pax'])) $gruppo[$j]['pax'] = $info_pre[$i]['pax'];
						else 									 $gruppo[$j]['pax'] += $info_pre[$i]['pax'];
						$gruppo['totali']['pax'] += $info_pre[$i]['pax'];
					}
						
					// Si aggiorna il numero di camere non assegnate
					if($info_pre[$i]['camera'] == 0) { $gruppo[$j]['na']++; $gruppo['totali']['na']++; }
						
					$nuova_riga = FALSE;
					break;
				}
			}
			
			// Si crea una nuova entrata per quella tipologia e vestizione specifica
			if($nuova_riga == TRUE) {
				$gruppo[$j]['vestizione'] = $info_pre[$i]['vestizione'];
				$gruppo[$j]['tipologia'] = $info_pre[$i]['tipologia'];
				$gruppo[$j]['camere'] = 1;							$gruppo['totali']['camere']++;
				
				// Se stiamo analizzando una principale e ci sono degli share connessi
				if($info_pre[$i]['tipo_pre'] <= 9 && $info_pre[$i]['stile_spe'] >= 100 && $info_pre[$i]['stile_spe'] <= 999) {
					$num_share = floor($info_pre[$i]['stile_spe'] / 100);
					
					for($z = 0, $share_trovati = 0 ; $z < $num_pre ; $z++) {
						if($info_pre[$z]['id_rif'] == $info_pre[$i]['id'] && $info_pre[$z]['tipo_pre'] >= 30 && $info_pre[$z]['tipo_pre'] <= 39) {
							// Se è il primo pax che si aggiunge
							if($share_trovati == 0) $gruppo[$j]['pax'] = $info_pre[$z]['pax'];
							else 						   $gruppo[$j]['pax'] += $info_pre[$z]['pax'];
							$gruppo['totali']['pax'] += $info_pre[$z]['pax'];
							
							$share_trovati++;
							if($share_trovati == $num_share) break;
						}
					}
				}
				else {
					$gruppo[$j]['pax'] = $info_pre[$i]['pax'];
					$gruppo['totali']['pax'] += $info_pre[$i]['pax'];
				}
				
				if($info_pre[$i]['camera'] == 0) { $gruppo[$j]['na'] = 1; $gruppo['totali']['na']++; }
				else 										$gruppo[$j]['na'] = 0;
				
				$num_vest_tipologia++;
			}
		}
	}

	return $gruppo;
}
			
function camere_da_assegnare(&$info_pre) {
	$num_pre = count($info_pre);
	$camere_na = array();
	$num_vest_tipologia = 0;
	$camere_na['totali']['camere'] = 0;
	$camere_na['totali']['pax'] = 0;
	// $camere_na['totali']['data_ultima_modifica'] = 0;
	
	for($i = 0 ; $i < $num_pre ; $i++) {
		
		// Se la camera è già stata assegnata non si conta
		if($info_pre[$i]['camera'] != 0) continue;
				
		$nuova_riga = TRUE;

		// Si cerca nelle vestizioni e tipolige già esistenti
		for($j = 0 ; $j < $num_vest_tipologia ; $j++) {
			if($camere_na[$j]['vestizione'] == $info_pre[$i]['vestizione'] //
			&& $camere_na[$j]['tipologia'] == $info_pre[$i]['tipologia']) {
				
				
				// Se non si tratta di uno share si aggiunge una camera
				if($info_pre[$i]['tipo_pre'] < 30 || $info_pre[$i]['tipo_pre'] > 39) {
					$camere_na[$j]['camere']++;
					$camere_na['totali']['camere']++;
				}
				
				// Se non si tratta di un cambio camera si aumenta il pax
				if($info_pre[$i]['tipo_pre'] < 20 || $info_pre[$i]['tipo_pre'] > 29) {
					$camere_na[$j]['pax'] += $info_pre[$i]['pax'];
					$camere_na['totali']['pax'] += $info_pre[$i]['pax'];
				}
				
				$nuova_riga = FALSE;
				break;
			}
		}
		
		// Si crea una nuova entrata per quella tipologia e vestizione specifica
		if($nuova_riga == TRUE) {
			// Se non si tratta di uno share si aggiunge un'entrata
			if($info_pre[$i]['tipo_pre'] < 30 || $info_pre[$i]['tipo_pre'] > 39) {
				$camere_na[$j]['vestizione'] = $info_pre[$i]['vestizione'];
				$camere_na[$j]['tipologia'] = $info_pre[$i]['tipologia'];
				$camere_na[$j]['camere'] = 1;							$camere_na['totali']['camere']++;
				
				// Si aggiorna il pax solo se non si tratta di un cambio camera
				if($info_pre[$i]['tipo_pre'] < 20 || $info_pre[$i]['tipo_pre'] > 29) {
					$camere_na[$j]['pax'] = $info_pre[$i]['pax'];
					$camere_na['totali']['pax'] += $info_pre[$i]['pax'];
				}
				else {
					$camere_na[$j]['pax'] = 0;
				}
				
				$num_vest_tipologia++;
			}
			// Se si tratta di uno share si aggiorna solamente il pax totale
			elseif($info_pre[$i]['tipo_pre'] >= 30 && $info_pre[$i]['tipo_pre'] <= 39) {
				$camere_na['totali']['pax'] += $info_pre[$i]['pax'];
			}
		}
	}

	return $camere_na;
}

function print_riepilogo_gruppo(&$info_gruppo) {
		$num_righe = count($info_gruppo) - 1;
		$riepilogo = '';
		
		for($i = 0 ; $i < $num_righe ; $i++) {
			$riepilogo .= '<b>' . $info_gruppo[$i]['camere'] . '</b>';
			
			if($info_gruppo[$i]['vestizione'] == '')  $riepilogo .= '?';
			else 													$riepilogo .= $info_gruppo[$i]['vestizione'];
			
			if($info_gruppo[$i]['tipologia'] != '')   $riepilogo .= '+' . $info_gruppo[$i]['tipologia'];
			$riepilogo .= ' ';
		}
		return $riepilogo;
}

function lista_colori_gruppo($colore_gruppo) {
	$colori_gruppo = array('FFB477', '8FFF57', 'ED4AFF', 'FFFF82', '98C0EC', 'D2CC99', 'D4FF8F', '84F2D4', '8FFFC6', 'FFEE6B', 'BA81FF', 'FF75FF');
	$nome_colori_gruppo = array('ARANCIONE', 'VERDE', 'VIOLA', 'GIALLO', 'AZZURRO', 'MARRONE', 'VERDE', 'AZZURRO', 'AZZURRO', 'GIALLO', 'VIOLA', 'VIOLA');
	$num_colori_gruppo = count($colori_gruppo);
	$lista_colori = '<p class="titolo_colore_gruppo">COLORE GRUPPO</p><select name="colore_gruppo" class="lista_colori_gruppo">';

	for($i = 0 ; $i < $num_colori_gruppo ; $i++) {
		$lista_colori .= '<option value="' . $i . '" style="background:#' . $colori_gruppo[$i] . '; color:#'.$colori_gruppo[$i].'"';
		if($colore_gruppo == $i) $lista_colori .= ' selected="selected"';
		$lista_colori .= '>'.($i+1).' - ' . $nome_colori_gruppo[$i] . '</option>';
	}
	$lista_colori .= '</select>';
	
	return $lista_colori;
}

function lista_colori_note($colore_note, $prog = NULL, $disabled = NULL) {
	$colori_note = array(     '000',  'f00',   'ff0',    '00f', 'FFA0FF');
	$nome_colori_note = array('NERO', 'ROSSO', 'GIALLO', 'BLU', 'VIOLA');
	$num_colori_note = count($colori_note);

	$lista_colori = '<select name="colore_note';
	if($prog != NULL) $lista_colori .= $prog;
	$lista_colori .= '"';
	if($disabled != NULL) $lista_colori .= $disabled;
	$lista_colori .= '>';

	for($i = 0 ; $i < $num_colori_note ; $i++) {
		$lista_colori .= '<option value="' . $i . '" style="background:#' . $colori_note[$i] . '; color:#'.$colori_note[$i].'"';
		if($colore_note == $i) $lista_colori .= ' selected="selected"';
		$lista_colori .= '>'.($i+1).' - ' . $nome_colori_note[$i] . '</option>';
	}
	$lista_colori .= '</select>';
	
	return $lista_colori;
}

function colore_gruppo_casuale() {
	$colori_gruppo = array('FFB477', '8FFF57', 'ED4AFF', 'FFFF82', '98C0EC', 'D2CC99', 'D4FF8F', '84F2D4', '8FFFC6', 'FFEE6B', 'BA81FF', 'FF75FF');
	
	return rand(0, count($colori_gruppo) - 1);
}

function ricerca_prenotazioni() {
	if($_SESSION['li_pre_data_arrivo'] != NULL) $data_arrivo = date('d/m/Y', $_SESSION['li_pre_data_arrivo']);
	else 								 					  $data_arrivo = '';
	if($_SESSION['li_pre_data_partenza'] != NULL) $data_partenza = date('d/m/Y', $_SESSION['li_pre_data_partenza']);
	else 														 $data_partenza = '';
	
	$result = '<form name="dati" class="ricerca_gruppi" action="lista_pre.php" method="post" enctype="multipart/form-data">';
	
	$result .= '<input type="radio" name="periodo_rc" id="r1" value="passato" ';
	if($_SESSION['li_pre_periodo'] == 'passato') $result .= 'checked="checked" ';
	$result .= 'class="radio_ricerca_gruppi"><label for="r1"> PASSATE</label> ';
	
	$result .= '<input type="radio" name="periodo_rc" id="r2" value="futuro" ';
	if($_SESSION['li_pre_periodo'] == 'futuro') $result .= 'checked="checked" ';
	$result .= 'class="radio_ricerca_gruppi"><label for="r2"> FUTURE</label><br />';
	
	$result .= '<input type="radio" name="periodo_rc" id="r3" value="presente" ';
	if($_SESSION['li_pre_periodo'] == 'presente') $result .= 'checked="checked" ';
	$result .= 'class="radio_ricerca_gruppi"><label for="r3"> IN CASA</label> ';
	$result .= '<input type="radio" name="periodo_rc" id="r4" value="tutti" ';
	if($_SESSION['li_pre_periodo'] == 'tutti') $result .= 'checked="checked" ';
	$result .= 'class="radio_ricerca_gruppi"><label for="r4"> TUTTE</label>';
	
	$result .= '<br /><br />';
	
	$result .= '<input type="radio" name="tipo_rc" id="r5" value="privati" ';
	if($_SESSION['li_pre_tipo'] == 'privati') $result .= 'checked="checked" ';
	$result .= 'class="radio_ricerca_gruppi"><label for="r5"> PRIVATI</label> ';
	$result .= '<input type="radio" name="tipo_rc" id="r6" value="gruppi" ';
	if($_SESSION['li_pre_tipo'] == 'gruppi') $result .= 'checked="checked" ';
	$result .= 'class="radio_ricerca_gruppi"><label for="r6"> GRUPPI</label>';
	
	$result .= '<br />';
	$result .= '<input type="text" name="nome_rc" class="field nome_gruppo_ricercato" placeholder="NOME" value="' . $_SESSION['li_pre_nome'] . '" autofocus="autofocus" />';
	$result .= '<input type="text" name="agenzia_rc" class="field nome_gruppo_ricercato" placeholder="AGENZIA" value="' . $_SESSION['li_pre_agenzia'] . '" />';
	$result .= '<input type="text" name="data_arrivo_rc" class="field nome_gruppo_ricercato" placeholder="DAL" value="' . $data_arrivo . '" />';
	$result .= '<input type="text" name="data_partenza_rc" class="field nome_gruppo_ricercato" placeholder="AL" value="' . $data_partenza . '" />';
	$result .= '<input class="bottone" type="submit" value="CERCA" />';
	
	$result .= '</form>';

	return $result;
}

// Funzione utilizzata solo in ge_pre.php quindi su singole prenotazioni
// Nome gruppo e agenzia modificabile solo dalla pagina gestione gruppi
// Tranne se il gruppo viene creato
function aggiornamento_gruppo(&$id_gruppo, &$pre_spe = NULL, $pagina = NULL) {
	$colore_casuale = NULL;
	
	// Se il gruppo non esiste ancora e va creato
	if($id_gruppo == 0) {
		$colore_casuale = colore_gruppo_casuale();
		
		$db = db_connect();
		$request = $db->prepare('INSERT INTO gruppi (nome,data_arrivo,data_partenza,totale_camere,agenzia,colore) VALUES ('//
					  .'\''.$pre_spe[0]['nome_gruppo'].'\','.$pre_spe[0]['data_arrivo'].','.$pre_spe[0]['data_partenza'].',1,\''.$pre_spe[0]['agenzia'].'\','.$colore_casuale.')');
		$request->execute();
		
		// Recuperiamo l'id del gruppo appena creato
		$gruppo_sel_grezzo = $db->query('SELECT id FROM gruppi ORDER BY id DESC LIMIT 0,1');
		$gruppo_sel_tab = $gruppo_sel_grezzo->fetch(PDO::FETCH_ASSOC);
		
		// Passato tramite referenza alla pagina principale
		$id_gruppo = $gruppo_sel_tab['id'];
		
		$db->connection = NULL;
		
		// Altrimenti il gruppo appena creato viene eliminato perché ancora non ha le pratiche agganciate
		if($pagina == 'ge_pre') return $colore_casuale;
	}
	
	
	// Si recuperano tutte le prenotazioni del gruppo
	$db = db_connect();
	$reponse = $db->query('SELECT id, id_rif, tipo_pre, stile_spe, gruppo, camera, vestizione, pax, tipologia, data_arrivo, data_partenza FROM prenotazioni WHERE gruppo=\'' . $id_gruppo . '\' ORDER BY vestizione, id_rif');
	$info_pre_gruppo = $reponse->fetchAll(PDO::FETCH_ASSOC);
	$num_pre = count($info_pre_gruppo);
	
	// Se il gruppo non ha più prenotazioni lo eliminiamo
	if($num_pre == 0) {
		$db->query('DELETE FROM gruppi WHERE id=' . $id_gruppo);
	}
	
	// Se per il gruppo sono presenti prenotazioni
	else {
		$gruppo = riepilogo_gruppo($info_pre_gruppo);
		
		// Aggiorniamo il database con le modifiche fatte
		$set_update = 'data_arrivo=?, data_partenza=?, riepilogo=?, totale_camere=?, camere_non_assegnate=?, totale_pax=?';
		$array_update = array($gruppo['totali']['data_arrivo_generale'], $gruppo['totali']['data_partenza_generale'], //
			print_riepilogo_gruppo($gruppo), $gruppo['totali']['camere'], $gruppo['totali']['na'], $gruppo['totali']['pax'], $id_gruppo);

		// Si modifica l'elemento nel db
		$sql = 'UPDATE gruppi SET ' . $set_update . ' WHERE id=?';
		
		$request = $db->prepare($sql);
		$request->execute($array_update);
	}
	$db->connection = NULL;
	
	return $colore_casuale;
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

function cam_disp_full_array(&$data_arrivo, &$data_partenza, &$parametri_escusione) {
	$camere_occupate = NULL;
	$occupazione_esistente = FALSE;
	
	
	// Creiamo una copia della lista camere
	$cam = $_SESSION['cam'];
	$num_cam = $_SESSION['num_cam'];
	
	// Recuperiamo le prenotazioni per quelle date
	$sql = 'SELECT camera FROM prenotazioni WHERE ';
	$sql .=  '((data_arrivo >= '.$data_arrivo.' && data_arrivo < '.$data_partenza.') || ' //
		  . ' (data_partenza > '.$data_arrivo.' && data_partenza <= '.$data_partenza.') || ' //
		  . ' (data_arrivo < '.$data_arrivo.' && data_partenza > '.$data_partenza.'))';
	$sql .=  ' AND camera != 0';
	$sql .= ' ORDER BY camera ASC';

	$db = db_connect();
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
	
	// Creiamo una tabella con le camere libere
	for($i = 0 ; $i < $num_cam_occ ; $i++) {
		for($j = 0 ; $j < $num_cam ; $j++) {
			if($camere_occupate[$i] == $cam[$j]['numero']) {
				unset($cam[$j]);				// Eliminiamo la camera occupata
				$cam = array_values($cam); // Reimpostiamo il progressivo per evitare errori
				$num_cam--;
				break;
			}
		}
	}
	
	return $cam;
}

function compara_prenotazioni(&$pre_1, &$pre_2) {
	if($pre_1['tipo_pre'] > 9 || $pre_1['stile_spe'] != 0
	   || $pre_1['nome'] != $pre_2['nome'] || $pre_1['tipo_pre'] != $pre_2['tipo_pre'] || $pre_1['camera'] != $pre_2['camera'] || $pre_1['vestizione'] != $pre_2['vestizione'] //
		|| $pre_1['tipologia'] != $pre_2['tipologia'] || $pre_1['pax'] != $pre_2['pax'] || $pre_1['arrangiamento'] != $pre_2['arrangiamento'] //
		|| $pre_1['primo_pasto'] != $pre_2['primo_pasto'] || $pre_1['ultimo_pasto'] != $pre_2['ultimo_pasto'] || $pre_1['data_arrivo'] != $pre_2['data_arrivo'] //
		|| $pre_1['data_partenza'] != $pre_2['data_partenza'] || $pre_1['note'] != $pre_2['note'] || $pre_1['colore_note'] != $pre_2['colore_note']) {
		return FALSE;		
	}
	return TRUE;
}

function calendario_menu() {
	$stagione_sel = NULL;

	// Se ci sono date per l'estate
	if($_SESSION['inizio_estate'] != 0) {
		// Se siamo attualmente nell'estate
		if($_SESSION['oggi'] >= $_SESSION['inizio_estate'] && $_SESSION['oggi'] < $_SESSION['fine_estate']) {
			$prima_data = $_SESSION['oggi'];
			$ultima_data = $_SESSION['fine_estate'];
			
			$stagione_sel = 'estate';
		}
		// Se siamo prima dell'estate e dopo l'inverno o se siamo prima dell'estate e ancora prima dell'inverno
		elseif($_SESSION['oggi'] < $_SESSION['inizio_estate'] && //
				($_SESSION['oggi'] > $_SESSION['fine_inverno'] || $_SESSION['inizio_estate'] < $_SESSION['inizio_inverno'])) {
			$prima_data = $_SESSION['inizio_estate'];
			$ultima_data = $_SESSION['fine_estate'];
			
			$stagione_sel = 'estate';
		}
	}
	// Se ci sono date per l'inverno
	if($stagione_sel == NULL && $_SESSION['inizio_inverno'] != 0) {
		// Se siamo attualmente nell'inverno
		if($_SESSION['oggi'] >= $_SESSION['inizio_inverno'] && $_SESSION['oggi'] < $_SESSION['fine_inverno']) {
			$prima_data = $_SESSION['oggi'];
			$ultima_data = $_SESSION['fine_inverno'];
			
			$stagione_sel = 'inverno';
		}
		// Se siamo prima dell'inverno
		elseif($_SESSION['oggi'] < $_SESSION['inizio_inverno'] && //
				($_SESSION['oggi'] > $_SESSION['fine_estate'] || $_SESSION['inizio_inverno'] < $_SESSION['inizio_estate'])) {
			$prima_data = $_SESSION['inizio_inverno'];
			$ultima_data = $_SESSION['fine_inverno'];
			
			$stagione_sel = 'inverno';
		}
	}
	if($stagione_sel == NULL) {
		echo "<script>console.log('stagione sel estate ultima data -1');</script>";
		$prima_data = $_SESSION['inizio_estate'];
		$ultima_data = $_SESSION['fine_estate'];
		$stagione_sel = 'estate';
	}

	// pd = prima data
	$day_week_pd = date('w', $prima_data);
	
	// Se il giorno attuale non è il giorno di cambio e non siamo a inizio stagione
	if($prima_data != $_SESSION['inizio_'.$stagione_sel] && $day_week_pd != $_SESSION['giorno_cambio']) {
		// Ci riportiamo al primo giorno di arrivo per avere una settimana completa
		$prima_data = strtotime('-'.((7-($_SESSION['giorno_cambio']-$day_week_pd))%7).' day', $prima_data);
	}
	
	$mese_corrente = 0;
	for($data_corrente = $prima_data ; $data_corrente <= $ultima_data ; ) {
		$mese_buff = date('n', $data_corrente);

		// Se abbiamo cambiato mese
		if($mese_corrente != $mese_buff) {
			// Se non siamo al primo mese chiudiamo il mese precedente
			if($mese_corrente != 0) echo '</div>';
			
			// Stampiamo l'intera Stagionalità
			else {
				if($data_corrente > $_SESSION['inizio_'.$stagione_sel]) $inizio_stagione = $data_corrente;
				else 																	  $inizio_stagione = $_SESSION['inizio_'.$stagione_sel];
				
				$anno_stagione = date('y', $inizio_stagione);
				
				echo '<div class="stagione_cal">';
				
				echo '<div class="stagione_container">';
				
				echo '<form name="guarda_booking" class="intera_stagione" action="prenotazioni.php" method="post" enctype="multipart/form-data">';
				echo '<span>';
				echo strtoupper($stagione_sel).' ';
				if($stagione_sel == 'inverno') echo $anno_stagione.'/'.($anno_stagione+1);
				else 									 echo date('Y', $inizio_stagione);
				echo '</span>';
				echo '<input type="submit" class="nome_stagione" name="cerca"value="" />';
				
				// Recuperiamo l'ultima data del mese
				echo '<input type="hidden" name="data_arrivo" value="'.date('d/m/y', $inizio_stagione).'" />';
				echo '<input type="hidden" name="data_partenza" value="'.date('d/m/y', $_SESSION['fine_'.$stagione_sel]).'" />';
				echo '<input type="hidden" name="guarda_booking" />';
				echo '</form>';
			
				echo '</div>';
				
				echo '<div class="mesi_stagione">';
			}
			
			// Calcoliamo i giorni per fine mese
			$giorno_del_mese = date('j', $data_corrente);
			$giorni_nel_mese = date('t', $data_corrente);
			
			$fine_mese = strtotime('+'.($giorni_nel_mese-$giorno_del_mese+1).' day', $data_corrente);
			
			// Settiamo il primo giorno del mese, se siamo a inizio stagione ci teniamo il giorno di inizio stagione
			if($data_corrente == $prima_data) $inizio_mese = $data_corrente;
			else 										 $inizio_mese = strtotime('-'.($giorno_del_mese-1).' day', $data_corrente);
			
			
			echo '<div class="container_mese">';
			
			echo '<div class="mese_bk">';
				echo '<form name="guarda_booking" action="prenotazioni.php" method="post" enctype="multipart/form-data">';
				echo '<span>'.$_SESSION['mesi'][$mese_buff-1].'</span>';
				echo '<input type="submit" class="nome_mese" name="cerca"value="" />';
				
				// Recuperiamo l'ultima data del mese
				echo '<input type="hidden" name="data_arrivo" value="'.date('d/m/y', $inizio_mese).'" />';
				echo '<input type="hidden" name="data_partenza" value="'.date('d/m/y', $fine_mese).'" />';
				echo '<input type="hidden" name="guarda_booking" />';
				echo '</form>';
			
			echo '</div>';
			
			$mese_corrente = $mese_buff;
		}
		
		// Stampiamo la settimana
		echo '<div class="settimana_bk">';
		
			// Stampiamo la prima data
		
			// Arriviamo fino al prossimo giorno di cambio
			$giorni_da_aggiungere = (7+$_SESSION['giorno_cambio']-date('w', $data_corrente))%7;
			if($giorni_da_aggiungere == 0) $giorni_da_aggiungere = 7;
			
			$data_buff = strtotime('+'.$giorni_da_aggiungere.' day', $data_corrente);
			
			// Se la data supera la fine stagione si modifica
			if($data_buff > $ultima_data) $data_buff = $ultima_data;
			
			echo '<form name="guarda_booking" action="prenotazioni.php" method="post" enctype="multipart/form-data">';
			echo '<span>'.date('d', $data_corrente).'<br />'.date('d', $data_buff).'</span>';
			echo '<input type="submit" class="date_settimana" name="cerca" value="" />';
			
			// Recuperiamo l'ultima data del mese
			echo '<input type="hidden" name="data_arrivo" value="'.date('d/m/y', $data_corrente).'" />';
			echo '<input type="hidden" name="data_partenza" value="'.date('d/m/y', $data_buff).'" />';
			echo '<input type="hidden" name="guarda_booking" />';
			echo '</form>';
		
		echo '</div>';
		
		// Se siamo all'ultima stampa chiudiamo il container mese
		if($data_buff == $ultima_data) {
			echo '</div>'; // Mese
			echo '</div>'; // Mesi Stagione
			echo '</div>'; // Stagione cal
			
			// Se c'è ancora una stagione da stampare
			if($stagione_sel == 'estate' && $_SESSION['inizio_estate'] < $_SESSION['inizio_inverno']) {
				$stagione_sel = 'inverno';
				$mese_corrente = 0;
				$data_corrente = $_SESSION['inizio_inverno'];
				$prima_data = $data_corrente;
				$ultima_data = $_SESSION['fine_inverno'];
				continue;
			}
			if($stagione_sel == 'inverno' && $_SESSION['inizio_inverno'] < $_SESSION['inizio_estate']) {
				$stagione_sel = 'estate';
				$mese_corrente = 0;
				$data_corrente = $_SESSION['inizio_estate'];
				$prima_data = $data_corrente;
				$ultima_data = $_SESSION['fine_estate'];
				continue;
			}
			break;
		}
		
		$data_corrente = $data_buff;
	}
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


function num_notti(&$data_arrivo, &$data_partenza) {
	$num_notti = 1;
	$buff = strtotime('+1 day', $data_arrivo);
	
	while($buff < $data_partenza) {
		$num_notti++;
	$buff = strtotime('+1 day', $buff);
	}
	return $num_notti;
}
?>