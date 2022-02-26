<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore

	$azione = 0;
	$pasti = array('B', 'L', 'D');
	$camere = array();
	$vuoto = '';
	$print_cam_na = '';
	
	$tot_camere_all = 0;
	$tot_pax_all = 0;
	$tot_na_all = 0;
	
	$info_menu = '';
	
	// Se abbiamo deciso di uscire dalla pagina
	if(isset($_POST['annulla'])) {
		header('Location: lista_pre.php');
	}
	
	// Recuperiamo l'id del gruppo e altre informazioni generiche
	elseif(isset($_POST['gruppo']) || isset($_GET['id_gruppo'])) {
		if(isset($_GET['id_gruppo'])) $id_gruppo = intval($_GET['id_gruppo']);
		else 									$id_gruppo = intval($_POST['gruppo']);
		
		$db = db_connect();
		$risp_gruppo = $db->query('SELECT * FROM gruppi WHERE id='.$id_gruppo);
		$gruppo = $risp_gruppo->fetchAll(PDO::FETCH_ASSOC);
		$db->connection = NULL;
	}
	
	// Se stiamo assegnando le camere
	if(isset($_POST['assegna'])) {
		
		$db = db_connect();
		
		// Recuoperiamo i dati del gruppo per la gestione dei problemi
		$dati_pre = $db->query('SELECT * FROM prenotazioni WHERE gruppo='.$id_gruppo);
		$pre = $dati_pre->fetchAll(PDO::FETCH_ASSOC);
		$num_pre = count($pre);
		
		// Inseriamo le camere scelte per gli id corrispettivi prima di controllare i problemi
		for($i = 0 ; isset($_POST['camera_'.$i]) ; $i++) {
			if($_POST['camera_'.$i] != '') {
				for($j = 0 ; $j < $num_pre ; $j++) {
					if($pre[$j]['id'] == $_POST['id_'.$i]) {
						$pre[$j]['camera'] = intval($_POST['camera_'.$i]);
						break;
					}
				}
			}
		}
		
		// Controlliamo i problemi
		controllo_pre($pre);
		
		// Prepariamo la request per aggiornare il database con i dati inseriti per singola camera
		$sql_update = 'UPDATE prenotazioni A inner join(';
		for($i = 0 ; $i < $num_pre ; $i++) {
			if($i > 0) {
				$sql_update .= ' UNION SELECT '.$pre[$i]['id'].', '.$pre[$i]['camera'].', \''.$pre[$i]['problemi'].'\'';
			}	
			else 		$sql_update .= ' SELECT ' .$pre[$i]['id']. ' id, '.$pre[$i]['camera'].' camera, \''.$pre[$i]['problemi'].'\' problemi';
		}
		
		$sql_update .= ') B USING (id) SET A.id = B.id, A.camera = B.camera, A.problemi = B.problemi;';
		

		// Aggiorniamo il database
		$request = $db->prepare($sql_update); $request->execute();
		$db->connection = NULL;
		
		// Recuperiamo le preferenze di assegnazione
		if($_POST['piani_preferiti'] != '') {
			$piani_preferiti = explode(',', str_replace(array(';','.','/',':','-', '_'), ',', str_ireplace(' ', '', $_POST['piani_preferiti'])));
			$num_piani_preferiti = count($piani_preferiti);
		}
		else {
			$piani_preferiti = array();
			$num_piani_preferiti = 0;
		}
		
		// Recuperiamo le escusioni
		$esclusioni = FALSE;
		
		if($_POST['piani_esclusi'] != '') {
			$piani_esclusi = explode(',', str_replace(array(';','.','/',':','-', '_'), ',', str_ireplace(' ', '', $_POST['piani_esclusi'])));
			$num_piani_esclusi = count($piani_esclusi);
			$esclusioni = TRUE;
		}
		else {
			$piani_esclusi = array();
			$num_piani_esclusi = 0;
		}
		
		if($_POST['tipologie_escluse'] != '') {
			$tipologie_escluse = explode(',', str_replace(array(';','.','/',':','-', '_'), ',', str_ireplace(' ', '', $_POST['tipologie_escluse'])));
			$num_tipologie_escluse = count($tipologie_escluse);
			$esclusioni = TRUE;
		}
		else {
			$tipologie_escluse = array();
			$num_tipologie_escluse = 0;
		}
		
		if($_POST['vestizioni_escluse'] != '') {
			$vestizioni_escluse = explode(',', str_replace(array(';','.','/',':','-', '_'), ',', str_ireplace(' ', '', $_POST['vestizioni_escluse'])));
			$num_vestizioni_escluse = count($vestizioni_escluse);
			$esclusioni = TRUE;
		}
		else {
			$vestizioni_escluse = array();
			$num_vestizioni_escluse = 0;
		}
		
		$camere_escluse = array();
		if($_POST['camere_escluse'] != '') {
			$camere_escluse = explode(',', str_replace(array('.', '/', ';', '-', '_'), ',', str_replace(' ', '', $_POST['camere_escluse'])));
			$num_camere_escluse = count($camere_escluse);
			$esclusioni = TRUE;
		}
		else {
			$camere_escluse = array();
			$num_camere_escluse = 0;
		}

		// Se ci sono parametri d'esclusione
		$parametri_escusione = '';
		if($esclusioni == TRUE) {
			for($i = 0 ; $i < $num_piani_esclusi ; $i++)			 $parametri_escusione .= ' AND piano != '.$piani_esclusi[$i];
			for($i = 0 ; $i < $num_tipologie_escluse ; $i++)	 $parametri_escusione .= ' AND descrizione_breve != \''.$tipologie_escluse[$i].'\'';
			for($i = 0 ; $i < $num_vestizioni_escluse ; $i++)	 $parametri_escusione .= ' AND vestizione_max != \''.$vestizioni_escluse[$i].'\'';
			for($i = 0 ; $i < $num_camere_escluse ; $i++)		 $parametri_escusione .= ' AND numero != '.$camere_escluse[$i];
		}
		
		// Recuperiamo la liste delle camere libere ed escludiamo quelle scartate
		$cam_dispo = cam_disp_full_array($gruppo[0]['data_arrivo'], $gruppo[0]['data_partenza'], $parametri_escusione);
		$num_cam_dispo = count($cam_dispo);
		
		
		// Recuperiamo la lista dei piani disponibili
		$piani_dispo = array();
		$num_piani_dispo = 0;
		for($i = 0 ; $i < $num_cam_dispo ; $i++) {
			$piano_inserito = FALSE;
			
			// Controlliamo se il piano è già stato inserito
			for($j = 0 ; $j < $num_piani_dispo && $piano_inserito == FALSE ; $j++) {
				if($cam_dispo[$i]['piano'] == $piani_dispo[$j]) {
					$piano_inserito = TRUE;
					break;
				}
			}
			
			if($piano_inserito == FALSE) {
				$piani_dispo[] = $cam_dispo[$i]['piano'];
				$num_piani_dispo++;
			}
		}
		
		// Variabile per combinare i piani preferiti ai piani disponibili
		$num_piani_totali = $num_piani_preferiti;
		
		// Inseriamo i piani disponibili nei piani preferiti
		for($i = 0 ; $i < $num_piani_dispo ; $i++) {
			$piano_inserito = FALSE;
			
			// Controlliamo se il piano è già stato inserito
			for($j = 0 ; $j < $num_piani_preferiti && $piano_inserito == FALSE ; $j++) {
				if($piani_dispo[$i] == $piani_preferiti[$j]) {
					$piano_inserito = TRUE;
					break;
				}
			}
			
			if($piano_inserito == FALSE) {
				$piani_preferiti[] = $piani_dispo[$i];
				$num_piani_totali++;
			}
		}
		
		$num_na_after = 0;
		// Procediamo con l'assegnazione
		for($i = 0 ; $i < $num_pre ; $i++) {
			// Se la prenotazione ha già una camera o se gli si è dato la camera 0
			if($pre[$i]['camera'] != 0 || $_POST['camera_'.$i] != '') continue;
			$pre_assegnata = FALSE;
			$ass_possib = NULL;
			
			// Si inizia per piano preferito, poi ci saranno i piani disponibili
			for($j = 0 ; $j < $num_piani_totali && $pre_assegnata == FALSE ; $j++) {
				
				// Controlliamo prima secondo le preferenze dei piani
				for($cam_sel = 0 ; $cam_sel < $num_cam_dispo && $pre_assegnata == FALSE ; $cam_sel++) {
					
					// Se siamo su un piano diverso da quello scelto e siamo tra i piani preferiti dall'utente
					if($cam_dispo[$cam_sel]['piano'] != $piani_preferiti[$j] && $j < $num_piani_preferiti) continue;
					
					// Se le camere corrispondono per tipologia
					if($cam_dispo[$cam_sel]['descrizione_breve'] == $pre[$i]['tipologia'] || //
										($pre[$i]['tipologia'] == '' && $cam_dispo[$cam_sel]['descrizione_breve'] == 'SB')) {
						// Se l'occupazione letti è del 100% si assegna la camera
						if($cam_dispo[$cam_sel]['pax_max'] == $pre[$i]['pax']) {
							$pre[$i]['camera'] = $cam_dispo[$cam_sel]['numero'];
							unset($cam_dispo[$cam_sel]);
							$cam_dispo = array_values($cam_dispo);
							$pre_assegnata = TRUE;
						}
						// Se l'occupazione è inferiore a quella scelta
						elseif($cam_dispo[$cam_sel]['pax_max'] > $pre[$i]['pax']) {
							if($ass_possib == NULL) $ass_possib = $cam_sel;
							// Se la camera attuale ha un'occupazione letti migliore rispetto a quella possibile la si preferisce
							elseif($cam_dispo[$ass_possib]['pax_max'] > $cam_dispo[$cam_sel]['pax_max']) $ass_possib = $cam_sel;
						}
					}
					// Se la camera ha il balcone e la prenotazione no
					elseif($cam_dispo[$cam_sel]['descrizione_breve'] == 'B' && $pre[$i]['tipologia'] == '') {
						// Se l'occupazione letti è del 100% o meno si assegna la camera
						if($cam_dispo[$cam_sel]['pax_max'] >= $pre[$i]['pax']) {
							if($ass_possib == NULL) $ass_possib = $cam_sel;
							// Se la camera attuale ha un'occupazione letti migliore rispetto a quella possibile la si preferisce
							elseif($cam_dispo[$ass_possib]['pax_max'] > $cam_dispo[$cam_sel]['pax_max']) $ass_possib = $cam_sel;
						}
					}
					// Se la camera ha una tipologia qualunque e la prenotazione non ha tipologia specificata
					elseif($pre[$i]['tipologia'] == '') {
						// Se l'occupazione letti è del 100% o meno si assegna la camera
						if($cam_dispo[$cam_sel]['pax_max'] >= $pre[$i]['pax']) {
							if($ass_possib == NULL) $ass_possib = $cam_sel;
							// Se la camera attuale ha un'occupazione letti migliore rispetto a quella possibile la si preferisce
							elseif($cam_dispo[$ass_possib]['pax_max'] > $cam_dispo[$cam_sel]['pax_max']) $ass_possib = $cam_sel;
						}
					}
				}
				
				// Se un'assegnazione possibile è stata individuata si procede, non avendo trovato di meglio
				if($ass_possib != NULL && $pre_assegnata == FALSE) {
					$pre[$i]['camera'] = $cam_dispo[$ass_possib]['numero'];
					unset($cam_dispo[$ass_possib]);
					$cam_dispo = array_values($cam_dispo);
					$pre_assegnata = TRUE;
				}
				
			}
			// Se non è stato trovato niente fino ad ora si inserisce la prenotazione nel contatore di quelle non inserite
			if($pre_assegnata == FALSE) $num_na_after++;
			
			// Ricontiamo le camere disponibili
			$num_cam_dispo = count($cam_dispo);
		}
		
		// Prepariamo la request per aggiornare il database con le camere assegnate
		$sql_update = 'UPDATE prenotazioni A inner join(';
		for($i = 0 ; $i < $num_pre ; $i++) {
			if($i > 0) {
					$sql_update .= ' UNION SELECT '.$pre[$i]["id"].', '.$pre[$i]["camera"];
			}	
			else 		$sql_update .= ' SELECT ' .$pre[$i]["id"]. ' id, '.$pre[$i]["camera"].' camera';
		}
		$sql_update .= ') B USING (id) SET A.id = B.id, A.camera = B.camera;';
		
		// Aggiorniamo il database
		$db = db_connect();
		$request = $db->prepare($sql_update); $request->execute();
		$db->connection = NULL;
		
		aggiornamento_gruppo($id_gruppo);
		$data_arrivo_ov = array($gruppo[0]['data_arrivo']);
		$data_partenza_ov = array($gruppo[0]['data_partenza']);
		
		$azione = 'ROOMS ASSIGNED';
	}
	
	// Se stiamo assegnando le camere
	elseif(isset($_POST['azzera'])) {
		$db = db_connect();
		$reponse = $db->query('UPDATE prenotazioni SET camera = 0, problemi = NULL WHERE gruppo='.$id_gruppo);
		$db->connection = NULL;
		
		aggiornamento_gruppo($id_gruppo);
		$data_arrivo_ov = array($gruppo[0]['data_arrivo']);
		$data_partenza_ov = array($gruppo[0]['data_partenza']);
		
		$azione = 'ROOMS RESET';
	}
	
	// Stampiamo tutto
	if($id_gruppo != NULL) {
		if(isset($_GET['id_gruppo'])) $id_gruppo = intval($_GET['id_gruppo']);
		else 									$id_gruppo = intval($_POST['gruppo']);
		
		$db = db_connect();
		$reponse = $db->query('SELECT * FROM prenotazioni WHERE gruppo='.$id_gruppo.' ORDER BY data_arrivo, data_partenza, camera, vestizione, tipologia, nome');
		$db->connection = NULL;
		
		$pre = $reponse->fetchAll(PDO::FETCH_ASSOC);
		$num_pre = count($pre);
		
		// Iseriamo la testata
		$tab_gruppi = '<div class="form1_all">';
		$tab_gruppi .= '<p class="titolo">'.formatta_visualizzazione($gruppo[0]['nome']);
		if($gruppo[0]['note'] != NULL) $tab_gruppi .= ' ['.formatta_visualizzazione($gruppo[0]['note']).']';
		$tab_gruppi .= ' - ASSEGNAZIONE AUTOMATICA CAMERE</p>';
		$tab_gruppi .= '<table>' //
							.'<tr><th class="small_tab1"></th>' // PROGRESSIVO
							.'<th class="small_tab1"></th>' // TIPO PRENOTAZIONE
							.'<th class="small_tab1">CAM.</th>' // INPUT CAMERA
							.'<th>PERIODO</th>' //
							.'<th class="medium_tab1">NOME</th>' // 
							.'<th class="small_tab1">VEST+TIPO</th>' //
							.'<th class="small_tab1">PAX</th>' //
							.'<th class="medium_tab1">ARR.</th>' //
							.'<th class="big_tab1">NOTE</th>' //
							.'</tr>';
		
		
		if(isset($_POST['proponi'])) {
			// inserire funzione per camere
		}
		
		else {
			for($i = 0 ; $i < $num_pre ; $i++) {
				if($pre[$i]['camera'] != 0) $camere[$i] = $pre[$i]['camera'];
				else 								 $camere[$i] = '';
			}
		}
	
		// Inseriamo tutte le entrate in un array
		for($num_righe = 0 ; $num_righe < $num_pre ; $num_righe++) {
			$tot_camere_all++;
			
			// Se non si tratta di un cambio camera si aumenta il pax
			if($pre[$num_righe]['tipo_pre'] < 20 || $pre[$num_righe]['tipo_pre'] > 29)
				$tot_pax_all += $pre[$num_righe]['pax'];
			
			// Se la camera non è assegnata e se non si tratta di uno share si aumenta il numero di camere na
			if($camere[$num_righe] == 0 && ($pre[$num_righe]['tipo_pre'] < 30 || $pre[$num_righe]['tipo_pre'] > 39))
				$tot_na_all++;
		
			$tab_gruppi .= '<tr class="border_bottom">';
			
			// Progressivo
			$tab_gruppi .= '<td class="monospace_style_dx">' . ($num_righe + 1) . '</td>';
			
			$tab_gruppi .= '<td>';
			// Se si tratta di una principale con speciali correlate
			if($pre[$num_righe]['id_rif'] == 0 && $pre[$num_righe]['stile_spe'] > 0) $tab_gruppi .= '<span class="tipo_pre_gru">MAIN</span>';
			elseif($pre[$num_righe]['tipo_pre'] == 0) $tab_gruppi .= 'CF';
			elseif($pre[$num_righe]['tipo_pre'] == 1) $tab_gruppi .= 'OP';
			// Se si tratta di una speciale
			else {
				$tab_gruppi .= '<span class="tipo_pre_gru">';
				if($pre[$num_righe]['tipo_pre'] >= 10 && $pre[$num_righe]['tipo_pre'] <= 19)		$tab_gruppi .= 'CAM AGG';
				elseif($pre[$num_righe]['tipo_pre'] >= 20 && $pre[$num_righe]['tipo_pre'] <= 29) $tab_gruppi .= '<- ->';
				elseif($pre[$num_righe]['tipo_pre'] >= 30 && $pre[$num_righe]['tipo_pre'] <= 39) $tab_gruppi .= 'SHARE';
				$tab_gruppi .= '</span>';
			}
			
			$tab_gruppi .= '</td>';
			// Se si tratta di uno share creamo il campo read only e settiamo su 0 se non c'è camera assegnata
			if($pre[$num_righe]['tipo_pre'] >= 30 && $pre[$num_righe]['tipo_pre'] <= 39) {
				if($camere[$num_righe] == '') $camere[$num_righe] = 0;
				$tab_gruppi .= '<td><input type="text" name="camera_' . $num_righe . '" class="field field_protected" readonly="readonly" value="' .$camere[$num_righe]. '" />';
			}
			else
				$tab_gruppi .= '<td><input type="text" name="camera_' . $num_righe . '" class="field" value="' .$camere[$num_righe]. '" />';

			$tab_gruppi .= '<input type="hidden" name="id_' . $num_righe . '" value="'.$pre[$num_righe]['id'].'" />';
			$tab_gruppi .= '</td>';
			
			$tab_gruppi .= '<td class="monospace_style">'.formatta_periodo($pre[$num_righe]['data_arrivo'], $pre[$num_righe]['data_partenza']).'</td>';
			$tab_gruppi .= '<td>' . formatta_visualizzazione($pre[$num_righe]['nome']) . '</td>';
			

			$tab_gruppi .= '<td class="monospace_style">';
			$tab_gruppi .= formatta_visualizzazione($pre[$num_righe]['vestizione']);
			if($pre[$num_righe]['tipologia'] != "") $tab_gruppi .= '+' . formatta_visualizzazione($pre[$num_righe]['tipologia']);
			$tab_gruppi .= '</td>';
			
			$tab_gruppi .= '<td class="monospace_style_dx">' . $pre[$num_righe]['pax'] . '</td>';
			
			$tab_gruppi .= '<td class="monospace_style_dx">' . formatta_visualizzazione($pre[$num_righe]['arrangiamento']);
			if($pre[$num_righe]['arrangiamento'] != 'RS') $tab_gruppi .= '-' . $pasti[$pre[$num_righe]['primo_pasto'] - 1] . '-' . $pasti[$pre[$num_righe]['ultimo_pasto'] - 1];
			$tab_gruppi .= '</td>';
	
			$tab_gruppi .= '<td>' . formatta_visualizzazione($pre[$num_righe]['note']) . '</td>';
			
			$tab_gruppi .= '</tr>';
		}

		if($num_righe > 0) {
			
			$tab_gruppi .= '<tr><td colspan="10" class="monospace_style">';
			
			if($num_righe > 1) $tab_gruppi .= $num_righe . ' PRENOTAZIONI - ';
			
			if($tot_na_all > 0)  $tab_gruppi .= '<span class="clr_red">NA: ' . $tot_na_all . '</span>';
			else 						$tab_gruppi .= 'NA: 0';
			
			$tab_gruppi .= ' PAX GRUPPO: ' . $tot_pax_all;
			
			$tab_gruppi .= '</td></tr>';
	
		}
		$tab_gruppi .= '</table>';
		
		// Creiamo la lista delle camere da assegnare
		$cam_na = camere_da_assegnare($pre);
		
		// Formattiamo la lista di camere da assegnare
		$num_righe = count($cam_na) - 1; // Una riga è per i totali
			
		$print_cam_na .= '<div class="liste_sx">';
		
		$print_cam_na .= '<table class="lista_sx">';
		$print_cam_na .= '<tr class="testa"><th class="titolo_lista_sx">CAM NA</th><th>NUM</th><th>PAX NA</th></tr>';
		
		for($i = 0 ; $i < $num_righe ; $i++) {
			$print_cam_na .= '<tr class="riga">';
			
			$print_cam_na .= '<td class="tipo">';
			if($cam_na[$i]['vestizione'] == '') $print_cam_na .= '?';
			else 												$print_cam_na .= $cam_na[$i]['vestizione'];
			if($cam_na[$i]['tipologia'] != '')  $print_cam_na .= '+' . $cam_na[$i]['tipologia'];
			$print_cam_na .= '</td>';
			
			$print_cam_na .= '<td>' . $cam_na[$i]['camere'] . '</td>';
			$print_cam_na .= '<td>' . $cam_na[$i]['pax'] . '</td>';
			$print_cam_na .= '</tr>';
		}
		
		// Stampiamo i totali
		$print_cam_na .= '<tr class="totali"><td class="tipo">TOTALI</td><td>' . $cam_na['totali']['camere'] . '</td>';
		$print_cam_na .= '<td>' . $cam_na['totali']['pax'] . '</td>';
		$print_cam_na .= '</tr>';
		$print_cam_na .= '</table>';
		$print_cam_na .= '</div>';
		
		// ----------------------------------------------------------------
		
		// Cerchiamo le camere disponibili
		$cam_dispo = camere_disponibili($gruppo[0]['data_arrivo'], $gruppo[0]['data_partenza']);
		$print_cam_dispo = '';
		$piano_cam = 0;
		$piano_pax = 0;
		
		
		// Stampiamo le camere disponibili
		$num_righe = count($cam_dispo) - 1; // Una riga è per i totali
			
		$print_cam_dispo .= '<div class="liste_sx">';
		
		$print_cam_dispo .= '<table class="lista_sx">';
		$print_cam_dispo .= '<tr class="testa"><th class="titolo_lista_sx" colspan="3">CAMERE DISPONIBILI</th></tr>';
		
		for($i = 0 ; $i < $num_righe ; $i++) {
			if($i == 0)
				$print_cam_dispo .= '<tr class="testa"><th class="titolo_lista_sx">'.$cam_dispo[$i]["piano"].' PIANO</th><th>NUM</th><th>PAX</th></tr>';
			
			elseif($cam_dispo[$i-1]["piano"] != $cam_dispo[$i]["piano"]) {
				// Stampiamo i subtotali del piano
				$print_cam_dispo .= '<tr class="riga"><td>TOTIALI PIANO</td><td>'.$piano_cam.'</td><td>'.$piano_pax.'</td></tr>';
				$print_cam_dispo .= '<tr class="testa"><th class="titolo_lista_sx">'.$cam_dispo[$i]["piano"].' PIANO</th><th>NUM</th><th>PAX</th></tr>';
				
				$piano_cam = 0; // Resettiamo i totali del piano
				$piano_pax = 0;
			}
			
			$print_cam_dispo .= '<tr class="riga">';
			
			$print_cam_dispo .= '<td class="tipo per_comparsa">';
			if($cam_dispo[$i]["vestizione"] == '') $print_cam_dispo .= "?";
			else 												$print_cam_dispo .= $cam_dispo[$i]["vestizione"];
			if($cam_dispo[$i]["tipologia"] != '')  $print_cam_dispo .= "+" . $cam_dispo[$i]["tipologia"];
			// Inseriamo i numeri delle camere a comparsa
			$print_cam_dispo .= '<span class="a_comparsa">'.$cam_dispo[$i]["numeri_camere"].'</tspan>';
			$print_cam_dispo .= '</td>';
			
			$print_cam_dispo .= '<td>' . $cam_dispo[$i]["camere"] . '</td>';
			$print_cam_dispo .= '<td>' . $cam_dispo[$i]["pax"] . '</td>';
			$print_cam_dispo .= '</tr>';
			
			// Aggiorniamo i sub totali del piano
			$piano_cam += $cam_dispo[$i]["camere"];
			$piano_pax += $cam_dispo[$i]["pax"];
			
			
			// Se siamo all'ultima riga stampiamo anche i subtotali piano
			if($i == $num_righe -1) $print_cam_dispo .= '<tr class="riga"><td>TOTIALI PIANO</td><td>'.$piano_cam.'</td><td>'.$piano_pax.'</td></tr>';
		}
		
		// Stampiamo i totali
		if($num_righe > 0) {
			$print_cam_dispo .= '<tr class="totali"><td class="tipo">TOTALI</td><td>' . $cam_dispo["totali"]["camere"] . '</td>';
			$print_cam_dispo .= '<td>' . $cam_dispo["totali"]["pax"] . '</td>';
			$print_cam_dispo .= '</tr>';
		}
		
		$print_cam_dispo .= '</table>';
		
		// --------------------------------------------------------
		// Inseriamo una lista di piani preferiti
		$print_cam_dispo .= '<table class="lista_sx">';
		$print_cam_dispo .= '<tr class="testa"><th class="titolo_lista_sx" colspan="3">PIANI PREFERITI</th></tr>';
		$print_cam_dispo .= '<tr class="riga">';
		$print_cam_dispo .= '<td style=""><input type="text" class="field" placeholder="ES: 0,1,2,3,4" name="piani_preferiti" /></td>';
		$print_cam_dispo .= '</tr>';
		$print_cam_dispo .= '</table>';
		
		// --------------------------------------------------------
		// Inseriamo una lista di piani esclusi
		$print_cam_dispo .= '<table class="lista_sx">';
		$print_cam_dispo .= '<tr class="testa"><th class="titolo_lista_sx" colspan="3">PIANI ESCLUSI</th></tr>';
		$print_cam_dispo .= '<tr class="riga">';
		$print_cam_dispo .= '<td style=""><input type="text" class="field" placeholder="ES: 0,1,2,3,4" name="piani_esclusi" value="0" /></td>';
		$print_cam_dispo .= '</tr>';
		$print_cam_dispo .= '</table>';
		
		// --------------------------------------------------------
		// Inseriamo la possibilità di escludere delle tipologie
		$print_cam_dispo .= '<table class="lista_sx">';
		$print_cam_dispo .= '<tr class="testa"><th class="titolo_lista_sx">TIPOLOGIE ESCLUSE</th></tr>';
		$print_cam_dispo .= '<tr class="riga">';
		$print_cam_dispo .= '<td style=""><input type="text" class="field" placeholder="ES: BILO,TRILO,B,SB" name="tipologie_escluse" /></td>';
		$print_cam_dispo .= '</tr>';
		$print_cam_dispo .= '</table>';
		// --------------------------------------------------------
		// Inseriamo la possibilità di escludere delle vestizioni
		$print_cam_dispo .= '<table class="lista_sx">';
		$print_cam_dispo .= '<tr class="testa"><th class="titolo_lista_sx">VESTIZIONI ESCLUSE</th></tr>';
		$print_cam_dispo .= '<tr class="riga">';
		$print_cam_dispo .= '<td style=""><input type="text" class="field" placeholder="ES: D,MXX,XXXX" name="vestizioni_escluse" value="D" /></td>';
		$print_cam_dispo .= '</tr>';
		$print_cam_dispo .= '</table>';
		// --------------------------------------------------------
		// Inseriamo la possibilità di escludere camere
		$print_cam_dispo .= '<table class="lista_sx">';
		$print_cam_dispo .= '<tr class="testa"><th class="titolo_lista_sx">CAMERE ESCLUSE</th></tr>';
		$print_cam_dispo .= '<tr class="riga">';
		$print_cam_dispo .= '<td style=""><input type="text" class="field" placeholder="ES: 110,112,115,210" name="camere_escluse" /></td>';
		$print_cam_dispo .= '</tr>';
		$print_cam_dispo .= '</table>';
		
		$print_cam_dispo .= '<br/><br/>';
		$print_cam_dispo .= '<input class="bottone bottone_t1 bottone_r" type="submit" name="azzera" value="AZZERA" />';
		$print_cam_dispo .= '<input class="bottone bottone_t1" type="submit" name="annulla" value="ANNULLA" />';
		$print_cam_dispo .= '<input class="bottone bottone_t1" type="submit" name="assegna" value="ASSEGNA" />';
		$print_cam_dispo .= '<input type="hidden" name="gruppo" value="'.$id_gruppo.'" />';
		$print_cam_dispo .= '<br/><br/><br/></div>';
	}
	


	

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head>
	<title>ASSEGNAZIONE AUTOMATICA CAMERE</title><?php
	header_standard();
?></head>
<body><?php
	$testo_menu_top = ' | <a href="ge_gru.php?mg='.$id_gruppo.'">GESTIONE GRUPPO</a>';
	menu_top($testo_menu_top);
		
		echo '<form name="dati" action="ass_cam.php" method="post" enctype="multipart/form-data">';
		
		echo '<div id="corpo_a">';
		
		// COLONNA SINISTRA
		$pagina = 'gestione_gruppi';
		

		// Necessario per non avere errori in print problemi
		for($i = 0 ; $i < $num_pre ; $i++) $pre[$i]['nome_gruppo'] = &$gruppo['nome'];
		
		echo colonna_sinistra($vuoto, print_problemi($pre, $pagina), $vuoto, $print_cam_na.$print_cam_dispo);
		
		echo '<div class="cont_dx">';
		
		if($azione === 'ROOMS ASSIGNED') {
			echo '<div class="form1_all form_green">';
			echo '<p class="titolo">CAMERE ASSEGNATE CON SUCCESSO';
			if($num_na_after > 0) echo ' - '.$num_na_after.' CAMERE NON ASSEGNATE!';
			else 						 echo ' - ASSEGNATE TUTTE ;)';
			echo '</p>';
			echo '</div>';
		}
		
		elseif($azione === 'ROOMS RESET') {
			echo '<div class="form1_all form_green">';
			echo '<p class="titolo">CAMERE AZZERATE CON SUCCESSO</p>';
			echo '</div>';
		}
		
		echo $tab_gruppi;
		
		echo '</div>';
		
		echo '</div>';
		
		echo '</form>';
//echo $test;
	?></body>
</html><?php
} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>