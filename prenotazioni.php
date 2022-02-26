<?php
session_start(); // Si lancia la sezione
$before = microtime(true);
require('funzioni_booking.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore

	// Spazi per camere e date
	$spazio_top = 112;
	$spazio_left = 62;

	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'
			.'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it"><head><title>BOOKING</title>'
			.'<link rel="stylesheet" href="layout/prenotazioni.css" type="text/css" /><link rel="icon" href="favicon.png" type="image/png" />'
			.'<meta http-equiv="content-type" content="text/html; charset=UTF-8" /><meta http-equiv="content-language" content="it" />'
			.'<meta name="author" content="michele" /><meta name="copyright" content="Michele Sessa" /><meta name="robots" content="none" />'
			.'<style>.na_cam,.camera_tab,.riga_cam{height:'.($_SESSION['cella_h']+1).'px;}</style>'
			.'<script src="js/external.js" type="text/javascript" async="async"></script></head>'
			.'<body>';

	$oggi_date_time = time(); $day_oggi = date('d', $oggi_date_time); $month_oggi = date('m', $oggi_date_time);
	
	$oggi = mktime(0, 0, 0, $month_oggi, $day_oggi, date('Y', $oggi_date_time)); // togliamo le ore per evitare errori di visualizzazione
	
	$before_db = microtime(true);
	
	$ricerca = FALSE;
	$id_pre_rc = NULL;
	$id_gruppo_rc = NULL;
	
	$db = db_connect();
	
	// Se si vuole guardare il booking
	if(isset($_POST['guarda_booking'])) {
		$data_scelta = 0;
		$data_scelta_ultima = 0;
		
		// Per guardare tutta l'estate
		if(isset($_POST['estate'])) {
			$data_scelta = $_POST['inizio_estate'];
			$data_scelta_ultima = $_POST['fine_estate'];
		}
		
		// Per guardare tutto l'inverno
		elseif(isset($_POST['inverno'])) {
			$data_scelta = $_POST['inizio_inverno'];
			$data_scelta_ultima = $_POST['fine_inverno'];
		}
		
		// Per guardare tra due date scelte
		elseif(isset($_POST['cerca'])) {
			if($_POST['data_arrivo'] != '') {
				$data_scelta = controllo_data($_POST['data_arrivo']);
				if($data_scelta == 0) $data_scelta = $oggi;
			}
			else $data_scelta = $oggi;
		
			if($_POST['data_partenza'] != "") {
				$data_scelta_ultima = controllo_data($_POST['data_partenza']);
				if($data_scelta_ultima <= $data_scelta) $data_scelta_ultima = strtotime('+30 day', $oggi);
			}
			else $data_scelta_ultima = strtotime('+30 day', $oggi);
		}
		
		else {
			// Controlliamo nei vari mesi se se ne è scelto uno, per un massimo di 12
			for($i = 0 ; $i < 12 ; $i++) {
				if(isset($_POST['mese_es_'.$i])) {
					$data_scelta = $_POST['inizio_mese_es_'.$i];
					$data_scelta_ultima = $_POST['fine_mese_es_'.$i];
					break;
				}
				elseif(isset($_POST['mese_inv_'.$i])) {
					$data_scelta = $_POST['inizio_mese_inv_'.$i];
					$data_scelta_ultima = $_POST['fine_mese_inv_'.$i];
					break;
				}
			}
		}
		
		// Nel caso non si sia inserito niente o ci sia stato qualche problema
		if($data_scelta == 0) $data_scelta = $oggi;
		if($data_scelta_ultima == 0) $data_scelta_ultima = strtotime('+30 day', $oggi);
		
		$prenotazioni_da_prendere = 'WHERE (prenotazioni.data_arrivo>='.$data_scelta.' AND prenotazioni.data_arrivo<'.$data_scelta_ultima.') || '// Se l'arrivo è nel periodo
									.'(prenotazioni.data_partenza>' . $data_scelta . ' AND prenotazioni.data_partenza<=' . $data_scelta_ultima.') || '// Se la partenza è nel periodo
									.'(prenotazioni.data_partenza>='.$data_scelta_ultima.' AND prenotazioni.data_arrivo<='.$data_scelta.')'; // Se la prenotazione ingloba il periodo
	}
	
	// Se si stanno cercando delle prenotazioni
	elseif(isset($_POST['cerca_prenotazioni'])) {
		$ricerca = TRUE;
		$num_rc = 0;
		$campi_ricerca = '';
		$campo_precedente = 0;
		$data_scelta = 0;
		$data_scelta_ultima = 0;

		$tipo_data = &$_POST['tipo_data'];
		
		if(isset($_POST['nome']) && $_POST['nome'] != "")	$nome_rc   = formatta_salvataggio($_POST['nome']);
		else 								$nome_rc   = '';
		if(isset($_POST['agenzia']) && $_POST['agenzia'] != "") $agenzia_rc = formatta_salvataggio($_POST['agenzia']);
		else 								 $agenzia_rc = '';
		if(isset($_POST['camera']) && $_POST['camera'] != "")	$camera_rc = intval($_POST['camera']);
		else 								$camera_rc = '';
		if(isset($_POST['data_arrivo']) && $_POST['data_arrivo'] != "")		$data_arrivo_rc = controllo_data($_POST['data_arrivo']);
		else 											$data_arrivo_rc = '';
		if(isset($_POST['data_partenza']) && $_POST['data_partenza']	!= "")	$data_partenza_rc = controllo_data($_POST['data_partenza']);
		else 											$data_partenza_rc = '';
		
		if(isset($_POST['gruppo']) && $_POST['gruppo'] != "")	{
			$gruppo_rc = formatta_salvataggio($_POST['gruppo']);
			// Ricerchiamo tutti i gruppi che corrispondono ai dati inseriti
			$sql_id_gruppo = "SELECT id FROM gruppi WHERE nome LIKE '%" . $gruppo_rc . "%'";
			$id_gruppo_grezzo = $db->query($sql_id_gruppo);

			// Se dei gruppi sono stati trovati
			$gruppo_rc = $id_gruppo_grezzo->fetchAll(PDO::FETCH_ASSOC);
			if($gruppo_rc != NULL) $num_gruppi_rc = count($gruppo_rc);
		}
		else 			$gruppo_rc = NULL;

		// Se il gruppo è stato ricercato e trovato
		if($_POST['gruppo'] != "" && $gruppo_rc != NULL || $_POST['gruppo'] == "") {
			if($nome_rc != "") { $campi_ricerca .= "nome LIKE '%" . $nome_rc . "%'"; $campo_precedente = 1; }
			// Inseriamo gli id di tutti i gruppi trovati durante la ricerca
			if($gruppo_rc != NULL) {
				if($campo_precedente == 1) $campi_ricerca .= " AND "; else $campo_precedente = 1;
				
				if($num_gruppi_rc > 1) $campi_ricerca .= '(';
				
				for($i = 0 ; $i < $num_gruppi_rc ; $i++) {
					if($i > 0) $campi_ricerca .= ' || ';
					$campi_ricerca .= 'gruppo=' . $gruppo_rc[$i]['id'];
				}
				if($num_gruppi_rc > 1) $campi_ricerca .= ')';
			}
			if($agenzia_rc != '') {
				if($campo_precedente == 1) $campi_ricerca .= ' AND '; else $campo_precedente = 1;
				
				$campi_ricerca .= 'agenzia LIKE \'%' . $agenzia_rc . '%\'';
			}
			if($camera_rc != '') {
				if($campo_precedente == 1) $campi_ricerca .= ' AND '; else $campo_precedente = 1;
				
				$campi_ricerca .= 'camera=' . $camera_rc;
			}
			if($data_arrivo_rc != '') {
				if($campo_precedente == 1) $campi_ricerca .= ' AND '; else $campo_precedente = 1;
				
				if($tipo_data == 'data_esatta')  $campi_ricerca .= 'data_arrivo=' . $data_arrivo_rc;
				else {
					if($data_partenza_rc != '')	$campi_ricerca .= 'data_arrivo>=' . $data_arrivo_rc . ' AND data_arrivo<' . $data_partenza_rc;
					else 									$campi_ricerca .= 'data_arrivo>=' . $data_arrivo_rc;
				}
			}
			if($data_partenza_rc != '') {
				if($campo_precedente == 1) $campi_ricerca .= ' AND '; else $campo_precedente = 1;
				
				if($tipo_data == 'data_esatta')  $campi_ricerca .= 'data_partenza=' . $data_partenza_rc;
				else {
					if($data_arrivo_rc != '')		$campi_ricerca .= 'data_partenza>' . $data_arrivo_rc . ' AND data_arrivo<=' . $data_partenza_rc;
					else 									$campi_ricerca .= 'data_partenza<=' . $data_partenza_rc;
				}
			}
			
			// Se non sono stati inseriti campi di ricerca si prende a partire da oggi e per un mese
			if($campi_ricerca == '') {
				// Nel caso non si sia inserito niente o ci sia stato qualche problema
				if($data_scelta == 0) $data_scelta = $oggi;
				if($data_scelta_ultima == 0) $data_scelta_ultima = strtotime('+30 day', $oggi);
				
				$campi_ricerca = '(prenotazioni.data_arrivo>='.$data_scelta.' AND prenotazioni.data_arrivo<'.$data_scelta_ultima.') || '// Se l'arrivo è nel periodo
									.'(prenotazioni.data_partenza>' . $data_scelta . ' AND prenotazioni.data_partenza<=' . $data_scelta_ultima.') || '// Se la partenza è nel periodo
									.'(prenotazioni.data_partenza>='.$data_scelta_ultima.' AND prenotazioni.data_arrivo<='.$data_scelta.')'; // Se la prenotazione ingloba il periodo
			}
						
			// Si estrapola la prima data in base ai risultati della ricerca
			if($data_arrivo_rc == '') {
				$prima_prenotazione_grezza = $db->query('SELECT data_arrivo FROM prenotazioni WHERE '.$campi_ricerca.' ORDER BY data_arrivo ASC LIMIT 0,1');
	
				if($prima_prenotazione_grezza->rowCount() > 0) {
					$prima_prenotazione_tab = $prima_prenotazione_grezza->fetch(PDO::FETCH_ASSOC);
					$data_scelta = $prima_prenotazione_tab['data_arrivo'];
				}
				else {
					$voci_rc_trovate = 0;
					$data_scelta = $oggi;
				}
			}
			else 		$data_scelta = $data_arrivo_rc;
			
			// Si estrapola l'ultima data in base ai risultati della ricerca
			if($data_partenza_rc == '') {
				$ultima_prenotazione_grezza = $db->query('SELECT data_partenza FROM prenotazioni WHERE '.$campi_ricerca.' ORDER BY data_partenza DESC LIMIT 0,1');
				
				if($ultima_prenotazione_grezza->rowCount() > 0) {
					$ultima_prenotazione_tab = $ultima_prenotazione_grezza->fetch(PDO::FETCH_ASSOC);
					$data_scelta_ultima	= $ultima_prenotazione_tab['data_partenza'];
				}
				else {
					$voci_rc_trovate = 0;
					$data_scelta_ultima = strtotime('+30 day', $data_scelta);
				}
			}
			else 		$data_scelta_ultima = $data_partenza_rc;
			
			// Se sono state trovate delle voci ne prendiamo gli id e ne contiamo il numero
			if(!isset($voci_rc_trovate)) {
				// Recuperiamo tutti gli id
				$id_voci_rc_grezze = $db->query('SELECT id FROM prenotazioni WHERE '.$campi_ricerca.' ORDER BY camera, data_arrivo, gruppo, agenzia');
				$voci_rc_trovate = $id_voci_rc_grezze->rowCount();
				// Prima voce da cercare
				$id_voce_rc = $id_voci_rc_grezze->fetch(PDO::FETCH_NUM);
			}
		}
		
		// Si settano solo le date nel caso non si sia trovato niente che corrispondesse al gruppo
		else {
			$voci_rc_trovate = 0;
			$data_scelta = $oggi;
			$data_scelta_ultima = strtotime('+30 day', $data_scelta);
		}
		
		$prenotazioni_da_prendere = 'WHERE (prenotazioni.data_arrivo>='.$data_scelta.' AND prenotazioni.data_arrivo<'.$data_scelta_ultima.') || '// Se l'arrivo è nel periodo
											.'(prenotazioni.data_partenza>' . $data_scelta . ' AND prenotazioni.data_partenza<=' . $data_scelta_ultima.') || '// Se la partenza è nel periodo
											.'(prenotazioni.data_partenza>='.$data_scelta_ultima.' AND prenotazioni.data_arrivo<='.$data_scelta.')'; // Se la prenotazione ingloba il periodo
	
		// Puliamo la memoria
		unset($campi_ricerca);
		unset($campo_precedente);
	}
	
	// Per andare a booking a partire dalla pagina di un gruppo
	elseif(isset($_GET['id_gruppo'])) {
		$id_gruppo_rc = intval($_GET['id_gruppo']);
		// Ricerchiamo il gruppo che si vuole visualizzare
		$sql_id_gruppo = 'SELECT data_arrivo, data_partenza FROM gruppi WHERE id='.$id_gruppo_rc;
		$gruppo_grezzo = $db->query($sql_id_gruppo);

		$gruppo_rc = $gruppo_grezzo->fetch(PDO::FETCH_ASSOC);
		
		// Se il gruppo effettivamente esiste
		if($gruppo_rc != NULL) {
			$data_scelta = $gruppo_rc['data_arrivo'];
			$data_scelta_ultima = $gruppo_rc['data_partenza'];
		}
		
		// Se il gruppo non esiste si settano i valori standard di prima data
		else {
			$data_scelta = $oggi;
			$data_scelta_ultima = strtotime('+30 day', $data_scelta);
		}
		
		$prenotazioni_da_prendere = 'WHERE (prenotazioni.data_arrivo>='.$data_scelta.' AND prenotazioni.data_arrivo<'.$data_scelta_ultima.') || '// Se l'arrivo è nel periodo
									.'(prenotazioni.data_partenza>' . $data_scelta . ' AND prenotazioni.data_partenza<=' . $data_scelta_ultima.') || '// Se la partenza è nel periodo
									.'(prenotazioni.data_partenza>='.$data_scelta_ultima.' AND prenotazioni.data_arrivo<='.$data_scelta.')'; // Se la prenotazione ingloba il periodo
	}
	
	// Per andare a booking a partire dalla pagina di una prenotazione
	elseif(isset($_GET['id_pre'])) {
		$id_pre_rc = intval($_GET['id_pre']);
		// Ricerchiamo il gruppo che si vuole visualizzare
		$sql_id_pre = 'SELECT data_arrivo, data_partenza FROM prenotazioni WHERE id='.$id_pre_rc;
		$pre_grezza = $db->query($sql_id_pre);

		$pre_rc = $pre_grezza->fetch(PDO::FETCH_ASSOC);
		
		// Se il gruppo effettivamente esiste
		if($pre_rc != NULL) {
			$data_scelta = $pre_rc['data_arrivo'];
			$data_scelta_ultima = $pre_rc['data_partenza'];
		}
		
		// Se il gruppo non esiste si settano i valori standard di prima data
		else {
			$data_scelta = $oggi;
			$data_scelta_ultima = strtotime('+30 day', $data_scelta);
		}
		
		$prenotazioni_da_prendere = 'WHERE (prenotazioni.data_arrivo>='.$data_scelta.' AND prenotazioni.data_arrivo<'.$data_scelta_ultima.') || '// Se l'arrivo è nel periodo
									.'(prenotazioni.data_partenza>' . $data_scelta . ' AND prenotazioni.data_partenza<=' . $data_scelta_ultima.') || '// Se la partenza è nel periodo
									.'(prenotazioni.data_partenza>='.$data_scelta_ultima.' AND prenotazioni.data_arrivo<='.$data_scelta.')'; // Se la prenotazione ingloba il periodo
	}
	
	// Se si è arrivati sulla pagina senza nessun tipo di informazione
	else {
		$data_scelta = $oggi;
		$data_scelta_ultima = strtotime('+30 day', $data_scelta);
		
		$prenotazioni_da_prendere = 'WHERE (prenotazioni.data_arrivo>='.$data_scelta.' AND prenotazioni.data_arrivo<'.$data_scelta_ultima.') || '// Se l'arrivo è nel periodo
									.'(prenotazioni.data_partenza>' . $data_scelta . ' AND prenotazioni.data_partenza<=' . $data_scelta_ultima.') || '// Se la partenza è nel periodo
									.'(prenotazioni.data_partenza>='.$data_scelta_ultima.' AND prenotazioni.data_arrivo<='.$data_scelta.')'; // Se la prenotazione ingloba il periodo
	}
	//--------------------------------------------------------------------
	
	// Si calcola la prima data e l'ultima per evitare errori di stampa
	// Operazione comune a tutti i tipi di visualizzazione booking
	if(!isset($prima_data)) {
		$prima_prenotazione_grezza = $db->query('SELECT data_arrivo FROM prenotazioni WHERE data_partenza>'.$data_scelta.' ORDER BY data_arrivo ASC LIMIT 0,1');
		$prima_prenotazione_tab = $prima_prenotazione_grezza->fetch(PDO::FETCH_ASSOC);
		
		// Se non sono state trovate prenotazioni
		if($prima_prenotazione_tab['data_arrivo'] == NULL) {
			$prima_data['timestamp'] = $data_scelta;
			$ultima_data['timestamp'] = $data_scelta_ultima;
		}
		
		else {
			$prima_data['timestamp'] = $prima_prenotazione_tab['data_arrivo'];
			
			$ultima_prenotazione_grezza = $db->query('SELECT data_partenza FROM prenotazioni WHERE data_arrivo<'.$data_scelta_ultima.' ORDER BY data_partenza DESC LIMIT 0,1');
			$ultima_prenotazione_tab = $ultima_prenotazione_grezza->fetch(PDO::FETCH_ASSOC);
	
			$ultima_data['timestamp']	= $ultima_prenotazione_tab['data_partenza'];
			
			// Se la prima data è più grande della data scelta la si modifica
			if($prima_data['timestamp'] > $data_scelta) $prima_data['timestamp'] = $data_scelta;
			// Se l'ultima data è più piccola della data scelta la si modifica
			if($ultima_data['timestamp'] < $data_scelta_ultima) $ultima_data['timestamp'] = $data_scelta_ultima;
		}
		
		// Se la prima data è più grande della data scelta la si modifica
		if($prima_data['timestamp'] > $data_scelta) $prima_data['timestamp'] = $data_scelta;
		// Se l'ultima data è più piccola della data scelta la si modifica
		if($ultima_data['timestamp'] < $data_scelta_ultima) $ultima_data['timestamp'] = $data_scelta_ultima;
	}

	$db->connection = NULL;

	$db_time = microtime(true) - $before_db;

	$mesi = array('GENNAIO', 'FEBBRAIO', 'MARZO', 'APRILE', 'MAGGIO', 'GIUGNO', 'LUGLIO', 'AGOSTO', 'SETTEMBRE', 'OTTOBRE', 'NOVEMBRE', 'DICEMBRE');
	$giorni_nel_mese = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	$giorni_settimana = &$_SESSION['giorni'];
	$linee_cam = array(); // 0 = riga; 1 = colore
	$num_linee_cam = 0;
	
	// Controllo se il febbraio dell'anno della prima data è bisestile
	if(date('L', $prima_data['timestamp'])) $giorni_nel_mese[1] = 29;
	
	// Inizializziamo le variabili necessarie alla creazione del calendario
	// 0 = timestamp, 1 = anno, 2 = mese, 3 = giorno_mese, 4 = giorno_settimana, 5 = stile
	$date_cal[0][0] = &$prima_data['timestamp'];
	$date_cal[0][1] = date('Y', $date_cal[0][0]); // ANNO
	$date_cal[0][2] = date('n', $date_cal[0][0]); // MESE
	$date_cal[0][3] = date('j', $date_cal[0][0]); // GIORNO DEL MESE
	$date_cal[0][4] = date('w', $date_cal[0][0]); // GIORNO SETTIMANA

	// Stampa della linea dei giorni e dei mesi
	$linee_mesi = array();
	for($num_col = 0, $giorni_stamp = '', $mesi_stamp = '', $colspan_mese = 1, $oscurata = FALSE ; $date_cal[$num_col][0] < $ultima_data['timestamp'] ; ) {
		
		// Se la data corrisponde alla data scelta
		if($date_cal[$num_col][0] == $data_scelta) {
			$first_date = $num_col;
			$num_col_before = &$first_date; // Numero di colonne da stampare prima della prima data
		}
		if($date_cal[$num_col][0] == $data_scelta_ultima) {
			$last_date = $num_col; // Dove finiscono le date selezionate e iniziano le colonne dopo
		}
		
		// Se siamo al di fuori del periodo scelto
		if($date_cal[$num_col][0] < $data_scelta || $date_cal[$num_col][0] >= $data_scelta_ultima) $oscurata = TRUE;
		else 																													 $oscurata = FALSE;

		// Se siamo di sabato o di domenica
		if($date_cal[$num_col][4] == 0 || $date_cal[$num_col][4] == 6) {
			if($oscurata == TRUE)  $giorni_stamp .= '<div class="we_osc day oscurata">';
			else 						  $giorni_stamp .= '<div class="we day">';
		}
		// Se è un giorno della settimana
		else {
			if($oscurata == TRUE)  $giorni_stamp .= '<div class="day oscurata">';
			else 						  $giorni_stamp .= '<div class="day">';
		}
		
		$giorni_stamp .= $giorni_settimana[$date_cal[$num_col][4]] . '<br />' . $date_cal[$num_col][3] . '</div>';
	
		// Si aggiunge un giorno alla data corrente
		$num_col++;
	
		$date_cal[$num_col][0] = strtotime('+1 day', $date_cal[$num_col - 1][0]);
		
		// Se si Sta cambiando Settimana
		if($date_cal[$num_col - 1][4] == 6) $date_cal[$num_col][4] = 0;
		else 											 $date_cal[$num_col][4] = $date_cal[$num_col - 1][4] + 1;
		
		// Se si sta cambiando mese
		if($date_cal[$num_col - 1][3] == $giorni_nel_mese[$date_cal[$num_col - 1][2] - 1]) {
			
			// Salviamo la distanza da sinistra della linea blu per il mese
			$linee_mesi[] = ($_SESSION['cella_w']+1)*$num_col;
			
			// !! inserire stampa di linea dei mesi
			$date_cal[$num_col][3] = 1; // Giorno mese
			
			// Se bisogna cambiare anno
			if($date_cal[$num_col - 1][2] == 12) {
				$date_cal[$num_col][2] = 1; // Si riparte da gennaio
				$date_cal[$num_col][1] = $date_cal[$num_col - 1][1] + 1; // Si aggiunge un anno
				
				// Se cambiamo anno controlliamo la bisestilità
				if(date("L", $date_cal[$num_col][0])) $giorni_nel_mese[1] = 29;
				else 											  $giorni_nel_mese[1] = 28;
				
			}
			else {
				$date_cal[$num_col][2] = $date_cal[$num_col - 1][2] + 1; // Si aggiunge un mese
				$date_cal[$num_col][1] = &$date_cal[$num_col - 1][1]; // Si rimane sull'anno corrente
			}
			
			// Si inserisce il mese nella tabella dei mesi
			$mesi_stamp .= '<div class="mese" style="width:'.($colspan_mese*($_SESSION['cella_w']+1)).'px">' . $mesi[$date_cal[$num_col-1][2] - 1] . '&nbsp;' . $date_cal[$num_col-1][1] . '</div>';
			$colspan_mese = 1; // Si ricomincia da zero
		}
		else {
			$date_cal[$num_col][3] = $date_cal[$num_col - 1][3] + 1; // Si aggiunge un giorno al giorno mese
			$date_cal[$num_col][2] = &$date_cal[$num_col - 1][2]; // Si rimane sul mese corrente
			$date_cal[$num_col][1] = &$date_cal[$num_col - 1][1]; // Si rimane sull'anno corrente
			
			$colspan_mese++;	// Si aggiunge un giorno ai giorni nel mese
		}
	}
	
	// Se l'ultima data e l'ultima data scelta coincidono
	if($ultima_data['timestamp'] == $data_scelta_ultima) $last_date = $num_col;
	
	// Se le date sono finite prima della fine del mese si aggiorna $mesi_stamp
	if($date_cal[$num_col][3] != 1) {
		$mesi_stamp .= '<div class="mese" style="width:'.(($colspan_mese-1)*($_SESSION['cella_w']+1)).'px">'.$mesi[$date_cal[$num_col][2]-1].'&nbsp;'.$date_cal[$num_col][1].'</div>';
	}

	// --------------------------------------------------------------
	
	// Settiamo lo spazio grigio dopo il periodo scelto
	$num_col_after = $num_col - $last_date;

	// A questo punto è necessario recuperare le info dal database
	$before_db = microtime(true);
	$db = db_connect();
	// Se non vanno visualizzate le camere ordiniamo diversamente la risposta
	if($_SESSION['show_cam'] == FALSE) $prenotazioni_grezze = $db->query('SELECT prenotazioni.*,gruppi.colore AS colore_gruppo,gruppi.nome AS nome_gruppo, gruppi.note AS note_gruppo FROM prenotazioni LEFT JOIN gruppi ON prenotazioni.gruppo=gruppi.id ' . $prenotazioni_da_prendere . ' ORDER BY data_arrivo, gruppo, agenzia,stile_spe DESC');
	else 										  $prenotazioni_grezze = $db->query('SELECT prenotazioni.*,gruppi.colore AS colore_gruppo,gruppi.nome AS nome_gruppo, gruppi.note AS note_gruppo FROM prenotazioni LEFT JOIN gruppi ON prenotazioni.gruppo=gruppi.id ' . $prenotazioni_da_prendere . ' ORDER BY camera, data_arrivo, gruppo, agenzia,stile_spe DESC');
	$db->connection = NULL;
	$db_time += microtime(true) - $before_db;

	// Formattiamo i dati necessari
	$pre = $prenotazioni_grezze->fetchAll(PDO::FETCH_ASSOC);
	$cam = $_SESSION['cam'];
	array_unshift($cam, array('numero'=>0, 'piano'=>0,'ubicazione'=>'','colore'=>'')); // Aggiungiamo la camera non assegnata ad inizio array
	$tot_num_pre = count($pre);
	$tot_num_cam = $_SESSION['num_cam'] + 1; // Aggiungiamo + 1 per camera non assegnata
	$tot_presenze = 0;
	$tot_presenze_trovate = 0;
	
	// Se si è scelto di non visualizzare i numeri di camera, TRANNE CHE PER GLI APPARTAMENTI
	if($_SESSION['show_cam'] == FALSE) {
		// Si piazzano gli appartamenti dopo le camere da non essagnare per evitare errori di visualizzazione
		
		$apt = array();
		
		for($i = 0, $num_apt = 0 ; $i < $tot_num_pre ; $i++) {
			// Se si tratta di un appartamento si salvano i dati in un array dedicato
			if($pre[$i]['camera'] > 0 && $pre[$i]['camera'] <= 4) {
				$apt[$pre[$i]['camera']][] = $pre[$i];
				$num_apt++;
				unset($pre[$i]); // Eliminiamo la voce dall'array delle prenotazioni per reinserirla poi alla fine
			}
			elseif($pre[$i]['camera'] != 0) $pre[$i]['camera'] = 0; // Togliamo l'assegnazione alla camera
		}

		// Se sono stati trovati degli appartamenti
		if($num_apt > 0) {
			$pre = array_values($pre);
			
			
			//Inseriamo gli appartamenti alla fine delle prenotazioni
			for($i = 0 ; $i <= 4 ; $i++) {
				if(isset($apt[$i])) $num_tipo_apt = count($apt[$i]); // Contiamo quanti appartamenti della stessa tipologia
				else continue;
				
				for($j = 0 ; $j < $num_tipo_apt ; $j++) {
					$pre[] = $apt[$i][$j];
				}
			}
		}

		unset($apt);
	}
	
	// Riorganizziamo i livelli di overbooking
	gestione_overbooking($pre, $tot_num_pre);

	$num_lvl_cam = array(0);
	$livello_attuale = 0;

	// Variabili generiche per stampa background
	
	$giorni_periodo = $last_date - $first_date;
	$w_periodo = $giorni_periodo*($_SESSION['cella_w']+1);
	if($num_col_before > 0) $w_col_before = ($_SESSION['cella_w']+1)*$num_col_before;
	else 							$w_col_before = 0;
	
	if($num_col_after > 0) $w_col_after = ($_SESSION['cella_w']+1)*$num_col_after;
	else 						  $w_col_after = 0;
	
	$larghezza_totale = $w_col_before+$w_periodo+$w_col_after;
	
	
	// Stampiamo le date
	echo '<div id="corpo_booking">'
		 .'<div class="booking">'
		 .'<div id="fixed_calendar" style="width:'.($larghezza_totale+14).'px" class="fixed_menu">'
		 .'<div class="mesi_pre">';

	echo $mesi_stamp.'</div><div class="day_tab">'.$giorni_stamp.'</div>';
	echo '</div>';

	echo '</div>';

	unset($giorni_stamp);
	unset($mesi_stamp);

	// Si stampa la colonna delle camere e si recupera il numero di righe
	echo '<div id="fixed_camere" class="table_cam fixed_menu">';
	
	// Estrapoliamo i dati necessari alle colonne
	$num_righe = 0;
	$num_righe_na = 0;
	$num_lvl_cam = array();
	
	// Se non c'è motivo di stampare la riga per le camere non assegnate
	if($tot_num_pre == 0 || ($pre[0]['camera'] != 0 && $_SESSION['show_cam'] == TRUE)) {
		unset($cam[0]); // Eliminiamo la camera na
		$cam = array_values($cam);
		$tot_num_cam--;
	}
	
	// Calcoliamo il numero di righe per camera tramite i livelli overbooking
	for($i = 0 ; $i < $tot_num_pre ; $i++) {
		// Se si tratta di uno share si skippa
		if($pre[$i]['tipo_pre'] >= 30 && $pre[$i]['tipo_pre'] <= 39) continue;
		
		if($pre[$i]['livello'] > 0) {
			// Se esiste già un livello di overbooking per la camera si controlla che quello trovato sia superiore
			if(isset($num_lvl_cam[$pre[$i]['camera']])) {
				if($pre[$i]['livello'] > $num_lvl_cam[$pre[$i]['camera']])
					$num_lvl_cam[$pre[$i]['camera']] = $pre[$i]['livello'];
			}
			// Se non esiste livello di overbooking precedente glie lo si assegna
			else $num_lvl_cam[$pre[$i]['camera']] = $pre[$i]['livello'];
		}
	}
	
	// Trattiamo prima la camera per prenotazioni non assegnate
	if($cam[0]['numero'] == 0) {
		$num_righe++;
		$num_righe_na++;
		echo '<div class="na_cam">NA: 1</div>';
		
		// Assegnamo alla camera la riga della tabella in cui si trova
		$riga_camera[0] = 0;
		
		// Se ci sono livelli di overbooking per la camera
		if(isset($num_lvl_cam[0])) {
			$lvl_cam = &$num_lvl_cam[0];
			for($j = 1 ; $j <= $lvl_cam ; ) {
				$j++;
				echo '<div class="na_cam">NA: '.$j.'</div>'; // Si stampa la camera
			}
			
			$num_righe_na = $lvl_cam+1; // aggiorniamo num_righe_na
			$num_righe += $lvl_cam; // aggiorniamo num_righe
		}
		
		$i = 1; // Facciamo avanzare il puntatore
	
		// Se ci sono più camere non assegnate del totale delle camere stampiamo una riga rossa
		
		/* -- RIGA ROSSA INCLUDE APPARTAMENTI
		if($num_righe_na > $_SESSION['num_cam']) {
			$linee_cam[0][0] = $_SESSION['num_cam'];
			$linee_cam[0][1] = 'f66';
			$num_linee_cam++;
		}*/
		
		// -- RIGA ROSSA ESCLUDE APPARTAMENTI
		if($num_righe_na > 94) {
			$linee_cam[0][0] = 94;
			$linee_cam[0][1] = 'f66';
			$num_linee_cam++;
		}
		
		// Riga che separa le non assegnate dalle assegnate
		$linee_cam[$num_linee_cam][0] = $num_righe_na;
		$linee_cam[$num_linee_cam][1] = 'bbb';
		$num_linee_cam++;
	}
	else $i = 0;
	
	$riga_over = array();
	
	// Stampiamo la colonna delle camere
	for( ; $i < $tot_num_cam ; $i++) {
		// Si stampa la camera
		echo '<div class="camera_tab"';
		if($cam[$i]['colore'] != '') echo ' style="background:#' . $cam[$i]['colore'] . '"';
		echo '>';
		
		if($cam[$i]['note'] != '')	echo '<span>' . $cam[$i]['note'] . '</span>';
		
		echo '<p class="vest_cam">'.$cam[$i]['vestizione_max'].'</p>'
		. $cam[$i]['numero'] . ' ' . $cam[$i]['descrizione_breve']
		. '</div>';
		
		// Assegnamo alla camera la riga della tabella in cui si trova
		$riga_camera[$cam[$i]['numero']] = $num_righe;
		
		// Se per la camera esistono livelli di overbooking
		if(isset($num_lvl_cam[$cam[$i]['numero']])) {
			$lvl_cam = &$num_lvl_cam[$cam[$i]['numero']];
			for($j = 0 ; $j < $lvl_cam ; $j++) {
				
				// Si stampa la camera
				echo '<div class="camera_tab">'
				. '<p class="vest_cam">'.$cam[$i]['vestizione_max'].'</p>'
				. $cam[$i]['numero'].' '.$cam[$i]['descrizione_breve']
				. $cam[$i]['vestizione_max']
				. '</div>';
				
				$riga_over[] = $num_righe+$j+1;
			}
			
			// aggiorniamo num_righe
			$num_righe += $lvl_cam + 1;
		}
		// Se non esistono livelli di overbooking
		else {
			$num_righe++;
			
			// Se stiamo cambiando piano o ubicazione
			if($i != 0) {
				// Se cambiamo piano
				if($cam[$i]['piano'] != $cam[$i-1]['piano']) {
					$linee_cam[$num_linee_cam][0] = $num_righe-1;
					$linee_cam[$num_linee_cam][1] = $cam[$i-1]['colore'];
					$num_linee_cam++;
				}
				// Se cambiamo ubicazione
				elseif($cam[$i]['ubicazione'] != $cam[$i-1]['ubicazione']) {
					$linee_cam[$num_linee_cam][0] = $num_righe-1;
					$linee_cam[$num_linee_cam][1] = 'bbb';
					$num_linee_cam++;
				}
			}
		}
	}
	echo '</div>';
	
	// Stampiamo la colonna delle righe
	$num_over = count($riga_over);
	
	echo '<div class="riga_tab" style="left:'.$spazio_left.'px;top:'.$spazio_top.'px">';
	for($i = 0, $j = 0 ; $i < $num_righe ; $i++) {
		if($j < $num_over && $riga_over[$j] == $i) {
			echo '<div class="riga_cam over_cam" style="width:'.$larghezza_totale.'px;top: '.($i*$_SESSION['cella_h']).'px"></div>';
		}
		else
			echo '<div class="riga_cam" style="width:'.$larghezza_totale.'px;top: '.($i*$_SESSION['cella_h']).'px"></div>';
		
	}
	echo '</div>';
	
	
	// -------------------------------------------------
	$altezza_totale = ($_SESSION['cella_h']+1)*$num_righe;

	// Stampiamo lo spazio per le camere non assegnate
	if($num_righe_na > 0) {
		echo '<div class="bk_na" style="left:'.($spazio_left+$w_col_before).'px;top:'.$spazio_top.'px;'//
		.'width:'.($w_periodo).'px;height:'.($num_righe_na*($_SESSION['cella_h']+1)).'px"></div>';
	}

	// Stampiamo le celle dei giorni con evidenziati sabato e domenica
	echo '<div class="celle_settimane" style="left:'.$spazio_left.'px;top:'.$spazio_top.'px;'//
	.'width:'.$larghezza_totale.'px;height:'.$altezza_totale.'px;'//
	.'background-position:-'.($date_cal[0][4]*($_SESSION['cella_w']+1)).'px 0px"></div>';

	// Stampiamo lo spazio prima della prima data scelta
	if($num_col_before > 0) {
		echo '<div class="bk_prima" style="left:'.$spazio_left.'px;top:'.$spazio_top.'px;'//
		.'width:'.$w_col_before.'px;height:'.$altezza_totale.'px"></div>';
	}


	if($num_col_after > 0) {
		echo '<div class="bk_dopo" style="left:'.($spazio_left+$w_col_before+$w_periodo).'px;top:'.$spazio_top.'px;'//
		.'width:'.$w_col_after.'px;height:'.$altezza_totale.'px"></div>';
	}
	
	// Stampiamo le eventuali linee per la divisione delle camere
	for($i = 0 ; $i < $num_linee_cam ; $i++) {
		echo '<div class="linea_cam" style="left:'.($spazio_left-1).'px;top:'.($spazio_top+($linee_cam[$i][0]*($_SESSION['cella_h']+1))-1).'px;width:'.$larghezza_totale.'px; background:#'.$linee_cam[$i][1].'"></div>';
	}
	unset($linee_cam);
	unset($num_linee_cam);

	// Stampiamo le eventuali linee dei mesi
	$num_linee_mesi = count($linee_mesi);
	for($i = 0 ; $i < $num_linee_mesi ; $i++) {
		echo '<div class="linea_mese" style="left:'.($linee_mesi[$i]+$spazio_left-1).'px;top:'.$spazio_top.'px;height:'.$altezza_totale.'px"></div>';
	}
	unset($linee_mesi);

	// --------------------------------------------
	// Stampiamo le prenotazioni
	$j = 0; $num_cam = 0;
	
	echo '<div class="all_pre">';
	
	for($i = 0 ; $i < $tot_num_pre ; $i++) {
		// Se si tratta di uno share si skippa il controllo overbooking
		if($pre[$i]['tipo_pre'] >= 30 && $pre[$i]['tipo_pre'] < 39) {
			// Se lo share è su un cambio camera prima di saltarlo resettiamo il contatore date
			if($i + 1 < $tot_num_pre && $pre[$i]['camera'] != $pre[$i+1]['camera']) {
				$j = 0;
				$num_cam++; // Avanziamo nelle camere
			}
			continue;
		}
		
		// Settiamo il puntatore delle camere alla camera della prenotazione
		$cam_numero = &$pre[$i]['camera'];
		while($cam[$num_cam]['numero'] != $cam_numero) $num_cam++;
		
		// Si calcola la distanza da sinistra con la data_arrivo
		while($date_cal[$j][0] < $pre[$i]['data_arrivo']) $j++;
		
		// Si formatta lo stile della data di arrivo prima di portare avanti il cursore
		$data_arrivo_formattata = $giorni_settimana[$date_cal[$j][4]] . ' ' . $date_cal[$j][3] . '/' . $date_cal[$j][2];
		$distanza_left = $j * ($_SESSION['cella_w']+1);
		
		// Calcolo numero notti con data_partenza
		$num_notti = $j;
		while($date_cal[$j][0] < $pre[$i]['data_partenza']) $j++;
		
		$num_notti = $j - $num_notti;

		// Si formatta lo stile della data di partenza
		$data_partenza_formattata = $giorni_settimana[$date_cal[$j][4]] . ' ' . $date_cal[$j][3] . '/' .$date_cal[$j][2];

		// Si riporta il cursore date alla data di arrivo per evitare errori
		$j = $j - $num_notti;

		// Calcoliamo la distanza da top
		$distanza_top = ($_SESSION['cella_h']+1) * ($pre[$i]['livello'] + $riga_camera[$pre[$i]['camera']]);

		// Creamo alias per velocizzare la stampa
		$stile_spe = &$pre[$i]['stile_spe'];
		$pre_pax = &$pre[$i]['pax'];
		$cam_pax_max = &$cam[$num_cam]['pax_max'];
		$pre_vestizione = &$pre[$i]['vestizione'];
		$pre_tipologia = &$pre[$i]['tipologia'];
		
		
		// Aggiorniamo il numero di presenze totali
		$tot_presenze += $pre_pax * $num_notti;

		// Si stampa la prenotazione
		echo '<div class="pre';
		// Stampiamo la voce oscurata se non corrisponde alla ricerca
		if($ricerca == TRUE) {
			if($num_rc >= $voci_rc_trovate || $pre[$i]['id'] != $id_voce_rc[0]) {
				echo ' no_find';
			}
			else {
				$num_rc++;
				$id_voce_rc = $id_voci_rc_grezze->fetch(PDO::FETCH_NUM);
				
				// Aggiorniamo il numero di presenze totali
				$tot_presenze_trovate += $pre_pax * $num_notti;
			}
		}
		// Se siamo a booking a partire dalla lista_pre e vogliamo un gruppo o un privato
		elseif(($id_gruppo_rc != NULL && $pre[$i]['gruppo'] != $id_gruppo_rc)
				||($id_pre_rc != NULL && $pre[$i]['id'] != $id_pre_rc)) {
			echo ' no_find';
		}
		
 		// Salviamo le variabili già pronte per la visualizzazione
 		$pre_agenzia = formatta_visualizzazione($pre[$i]['agenzia']);
 		$pre_nome_gruppo = formatta_visualizzazione($pre[$i]['nome_gruppo']);
 		$pre_nome = formatta_visualizzazione($pre[$i]['nome']);
 		$pre_arrangiamento = &$pre[$i]['arrangiamento'];
		
		// Stampiamo il colore del gruppo
		if($pre[$i]['gruppo'] > 0) echo ' c_' . $pre[$i]['colore_gruppo'];
		// Stampiamo lo stile che corrisponde al tipo di prenotazione
		if($pre[$i]['tipo_pre'] % 10 == 1) echo ' pre_op';
		if($cam_numero > 0 && $pre[$i]['livello'] > 0) echo ' pre_over';
		// Se c'è un'agenzia cambiamo bordo
		if($pre_agenzia != '') echo ' ag_brd';
		echo '"';
		// Stampiamo la posizione e la grandezza della prenotazione
		echo 'style="left:'.($distanza_left+$spazio_left+2).'px;top:'.($distanza_top+$spazio_top+1).'px;'
			 .'width:'.($num_notti*($_SESSION['cella_w']+1)-5).'px;height:'.($_SESSION['cella_h']-2).'px"'
			 .'>';

 		// Inizializziamo le informazioni per le speciali
		$print_frecce = '';
		$print_cam_agg = '';
		$print_share = '';
		$print_share_num = '';
 		
 		// Segnaliamo la presenza di share o multicamera o cambi camera
 		if($stile_spe != 0) {
			
			// Se ci sono dei cambi camera
 			if($frecce_cam = $stile_spe % 10) {
 				// Se vanno inserite entrambe le camere
 				if($frecce_cam == 3) 	 $print_frecce = '<span class="freccia_sx"><</span><span class="freccia_dx">></span>';
 				// Se va inserita solo la freccia sinistra
 				elseif($frecce_cam == 1) $print_frecce = '<span class="freccia_sx"><</span>';
 				// Se va inserita solo la freccia destra
 				elseif($frecce_cam == 2) $print_frecce = '<span class="freccia_dx">></span>';
 			}
 			// Se ci sono camere aggiuntive o share
 			if($stile_spe >= 10) {
 				// Se ci sono delle camere aggiuntive
 				if($cam_agg = floor(($stile_spe % 100)/10)) {
 					$print_cam_agg = '<span class="cam_agg">'.($cam_agg+1).'</span>';
 				}
 				else $cam_agg = 0;
 				
 				// Se ci sono share si recuperano e si inseriscono in una string formattati
 				if($stile_spe >= 100) {
 					$share = floor($stile_spe / 100);
 					$print_share_num = '<span class="share_num">'.$share.'</span>';
 					
 					for($z = 0, $share_trovati = 0 ; $z < $tot_num_pre ; $z++) {
 						// Se lo share è correlato alla prenotazione in questione
 						if(($pre[$z]['tipo_pre'] >= 30 && $pre[$z]['tipo_pre'] <= 39) && $pre[$z]['id_rif'] == $pre[$i]['id']) {
 							$print_share .= '<p class="share_info">'
 											 .'<span class="date_share">'.date('d',$pre[$z]['data_arrivo']).'-'.date('d',$pre[$z]['data_partenza']).'</span>';
 							
 							if($pre[$z]['arrangiamento'] != $pre[$i]['arrangiamento'])
 								$print_share .= '<span class="arr_share">'.formatta_visualizzazione($pre[$z]['arrangiamento']).'</span>';
 							
 							$print_share .= '<span class="pax_share">x'.$pre[$z]['pax'].'</span>'
 											 .'<span class="nome_share">'.formatta_visualizzazione($pre[$z]['nome']).'</span>'
 											 .'<span class="fix_share"> </span></p>';
 							
 							$share_trovati++;
 							if($share_trovati == $share) break; // Se abbiamo trovato tutti gli share smettiamo di cercare
 						}
 					}
 				}
 				else $share = 0;
 			}
 			else {
 				$cam_agg = 0;
 				$share = 0;
 			}
 		}

		// Stampiamo frecce per cambio camera, numero di camere aggiuntive e numero di share
		echo $print_frecce.$print_cam_agg.$print_share_num;

 		// Stampiamo la vestizione
 		if($pre_pax == 0 || $pre_pax == $cam_pax_max || $cam_numero == 0) {
 			echo '<p class="vest">' . $pre_vestizione;
 			if($pre_tipologia != '') { if($pre_vestizione != "") echo '+'; echo $pre_tipologia; }
 			echo '</p>';
 		}
 		elseif($pre_pax >= $cam_pax_max)	{
 			echo '<p class="vest_r">' . $pre_vestizione;
 			if($pre_tipologia != '') { if($pre_vestizione != "") echo '+'; echo $pre_tipologia; }
 			echo '</p>';
 		}
 		elseif($pre_vestizione != '') {
 			echo '<p class="vest_i">' . $pre_vestizione;
 			// Se la tipologia della prenotazione è diversa da quella della camera
 			if($pre_tipologia != '') echo '+' . $pre_tipologia;
 			echo '</p>';
 		}
 		// Se la tipologia della prenotazione è diversa da quella della camera
 		elseif($pre_tipologia != '') echo '<p class="vest">' . $pre_tipologia . '</p>';
 		
 		echo '<p class="dati">';

 		// Segnaliamo la presenza di note
 		if($pre[$i]['note'] != '') {
 			echo '<span class="info_note';
 			if($pre[$i]['colore_note'] != 0) echo ' cn_'.$pre[$i]['colore_note'];
 			echo '">*</span>';
 		}
 		
 		// Da implementare e migliorare
 		if($pre_nome == '') {
 			if($pre_agenzia != '')		echo '<i>' . $pre_agenzia . '</i> ';
 			if($pre_nome_gruppo != '') echo '<b>' . $pre_nome_gruppo . '</b> ';
 		}
 		else			echo $pre_nome;
 		
 		echo '</p>';
 		
		// Si formatta l'infobolla per la prenotazione
 		echo '<div class="info">';
 		echo '<p class="info_nome"><a rel="ex" href="ge_pre.php?mp='.$pre[$i]['id'].'">';
 		if($pre_nome != '') echo $pre_nome;
 		else 					  echo 'MODIFICA';
 		echo '</a></p>';
 		
 		
 		if($pre_agenzia != '')		echo '<p class="info_ag">'.$pre_agenzia . '</p>';
 		
 		echo '<p class="info_date"><b>'.$data_arrivo_formattata.'</b> - <b>'.$data_partenza_formattata.'</b></p>';
 		echo '<b>'.$num_notti.'</b> notti';
 		if($pre_pax != 0)			echo '<p class="info_pax">PAX: ' . $pre_pax.'</p>';
 		echo '<p class="info_arr_vest">ARR: ' . $pre_arrangiamento;
 		if($pre_vestizione != '')	echo ', ' . $pre_vestizione;
 		if($pre_tipologia != '')	echo ' ' . $pre_tipologia;
 		echo '<p>';
 		if($pre_nome_gruppo != '')	{
 			echo '<p class="info_gru c_'.$pre[$i]['colore_gruppo'].'"><a href="ge_gru.php?mg='.$pre[$i]['gruppo'].'">'.$pre_nome_gruppo.'</a></p>';
 			if($pre[$i]['note_gruppo'] != '') echo '<p class="info_nt_gru c_'.$pre[$i]['colore_gruppo'].'">'.formatta_visualizzazione($pre[$i]['note_gruppo']).'</p>';
 		}
 		if($pre[$i]['note'] != '')	echo '<p class="info_nt"><b>NOTE:</b><br />' . formatta_visualizzazione($pre[$i]['note']) . '</p>';
 		
 		// Stampiamo le informazioni sugli share
 		echo $print_share;
 		
 		echo '<p class="info_um">ULTIMA MODIFICA:' . date("d/m/Y", $pre[$i]['data_ultima_modifica']).'</p>';
 		echo '</div>';
 		
		
		echo '</div>';
		
		// Se stiamo cambiando camera azzeriamo torniamo alla prima data
		if($i + 1 < $tot_num_pre && $pre[$i]['camera'] != $pre[$i+1]['camera']) {
			$j = 0;
			$num_cam++; // Avanziamo nelle camere
		}
	}

	echo '</div>';
	
	// Si stampano i problemi e le note gruppi
	if($tot_num_pre > 0) {
		// Si stampano gli errori
		$pagina = "prenotazioni";
		echo '<div class="prob_book prob_book_red">'.print_problemi($pre).'</div>';
		
		// Si stampano le note dei gruppi
		echo '<div class="note_gruppi">'.print_note_gruppi($pre, $giorni_settimana).'</div>';
	}
	else {
		echo '<div class="prob_book"></div>';
		echo '<div class="note_gruppi"></div>';
	}
	
	echo '</div>' //
	. '<script src="js/funzioni.js" type="text/javascript"></script>';

	// Si stampa la testata, le informazioni verranno aggunte poi con ::after
	$giorni_settimana_lunghi = array("DOM", "LUN", "MAR", "MER", "GIO", "VEN", "SAB");
	
	$menu = '<div class="testata_booking"><a href="menu.php">MENU</a> | ';
	// Se stiamo cercano delle prenotazioni
	if(isset($_POST['cerca_prenotazioni'])) {
		if($voci_rc_trovate == 0) {
			$menu .= 'TROVATO NISBA - '.$tot_num_pre.' PRENOTAZIONI';
		}
		else {
			$menu .= ' '.$giorni_settimana[$date_cal[$first_date][4]].' '.$date_cal[$first_date][3].'/'.$date_cal[$first_date][2]
					.' - '.$giorni_settimana[$date_cal[$last_date][4]].' '.$date_cal[$last_date][3].'/'.$date_cal[$last_date][2]
					.' ('.$giorni_periodo.' NOTTI)'
					.' : ';
			if($voci_rc_trovate != $tot_num_pre) 
				$menu .= $voci_rc_trovate.'/'.$tot_num_pre.' PRENOTAZIONI '.$tot_presenze_trovate.'/'.$tot_presenze.' PRESENZE';
			else
				$menu .= $tot_num_pre.' PRENOTAZIONI '.$tot_presenze.' PRESENZE';
		}
	}
	else {
		$menu .= ' '.$giorni_settimana[$date_cal[$first_date][4]].' '.$date_cal[$first_date][3].'/'.$date_cal[$first_date][2];
		$menu .= ' - '.$giorni_settimana[$date_cal[$last_date][4]].' '.$date_cal[$last_date][3].'/'.$date_cal[$last_date][2];
		$menu .= ' ('.$giorni_periodo.' NOTTI)';
		$menu .= ' - '.$tot_num_pre.' PRENOTAZIONI '.$tot_presenze.' PRESENZE';
	}
	
	$after = microtime(true);
	echo $menu . '<p class="data_refresh"><span class="bench">TOT:' . round($after-$before, 3) . '=' . round($after-$before-$db_time, 3) . 's + DB:' . round($db_time, 4) . 's </span>'//
	.'AGG:' . $giorni_settimana_lunghi[date("w", $oggi)] . ' ' . $day_oggi . '/' . $month_oggi . ' ALLE ' . date('H:i', $oggi_date_time) . '</p></div>';
	
	echo '</body>';
	
	echo '<style>riga_cam{width:'.$larghezza_totale.'px}body{width:'.($larghezza_totale+$spazio_left+150).'px;height:'.($altezza_totale+$spazio_top+150).'px}</style>';
	
	echo '</html>';
	
} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>