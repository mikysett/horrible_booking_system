<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore

	$azione = 0;
	$vuoto = '';
	
	$vista_compatta = TRUE;
	
	// valori standard
	$id_gruppo = '';

	// Visualizza la tabella per inserire un nuovo gruppo
	// mg = modifica gruppo
	if(!isset($_POST['inserisci']) && !isset($_GET['mg']) && !isset($_POST['aggiorna'])) {
		$nuovo_gruppo = TRUE;
		$id_gruppo = '';
		$tab_testata = '';
		
		$camere_standard = array();
		$vestizioni_standard = array('MXX', 'XXXX', 'MX', 'XXX', 'M', 'XX', 'X');
		$pax_standard			= array(4    , 4     , 3   , 3    , 2  , 2   , 1  );
		$num_cam_std = 7;
		
		for($insert_std = 0 ; $insert_std < $num_cam_std ; $insert_std++) {
			$camere_standard[$insert_std]['vestizione'] = $vestizioni_standard[$insert_std];
			$camere_standard[$insert_std]['pax']		  = $pax_standard[$insert_std];
		}

		$tab_testata .= '<div class="form1_all"><p class="titolo">DATI GENERICI</p><table class="center_block"><tr><th>TIPO P.</th><th class="medium_tab2">A</th><th class="date_ge_gru">DATA ARR.</th><th class="date_ge_gru">DATA PAR.</th><th>NOME GRUPPO</th><th>NOME AGENZIA</th><th>NOTE PER CAMERE GRUPPO</th></tr>';
		$tab_testata .= '<tr>';
		$tab_testata .= '<td>';
		$tab_testata .= '<select name="tipo_pre_gruppo">';
		$tab_testata .= '<option selected="selected" value="0">CF</option>';
		$tab_testata .= '<option value="1">OP</option>';
		$tab_testata .= '</select>';
		$tab_testata .= '</td>';
		$tab_testata .= '<td>';
		$tab_testata .= '<select name="arrangiamento_gruppo">';
		$tab_testata .= '<option selected="selected" value="HB">HB</option>';
		$tab_testata .= '<option value="BB">BB</option>';
		$tab_testata .= '<option value="FB">FB</option>';
		$tab_testata .= '<option value="RS">RS</option>';
		$tab_testata .= '</select> ';
		$tab_testata .= '<select name="primo_pasto_gruppo">';
		$tab_testata .= '<option value="1">B</option>';
		$tab_testata .= '<option value="2">L</option>';
		$tab_testata .= '<option selected="selected" value="3">D</option>';
		$tab_testata .= '</select> ';
		$tab_testata .= '<select name="ultimo_pasto_gruppo">';
		$tab_testata .= '<option selected="selected" value="1">B</option>';
		$tab_testata .= '<option value="2">L</option>';
		$tab_testata .= '<option value="3">D</option>';
		$tab_testata .= '</select>';
		$tab_testata .= '</td>';
		$tab_testata .= '<td><input type="text" name="data_arrivo_gruppo" class="field" value="" /></td>';
		$tab_testata .= '<td><input type="text" name="data_partenza_gruppo" class="field" value="" /></td>';
		$tab_testata .= '<td><input type="text" name="gruppo" class="field" value="" /></td>';
		$tab_testata .= '<td><input type="text" name="agenzia" class="field" value="" /></td>';
		$tab_testata .= '<td><input type="text" name="note_gruppo" class="field" value="" /></td>';
		$tab_testata .= '</tr>';
		$tab_testata .= '</table></div>';

		$tab_camere = '<div class="form1_all"><p class="titolo">INSERIMENTO PRENOTAZIONI</p><table><tr><th></th><th>TIPO P.</th><th class="date_ge_gru">DAL</th><th class="date_ge_gru">AL</th>' //
		. '<th>CAMERE (ES: 110,111)</th><th class="small_tab1">TIPO.</th><th class="small_tab1">VEST.</th>' //
		. '<th class="small_tab1">PAX</th><th class="small_tab1">Q.T&Agrave;</th><th>NOME</th><th>NOTE</th><th class="medium_tab2">A</th><th>COLORE NOTE</th></tr>';

		for($b = 0, $entrate_vuote = 5 ; $b < $num_cam_std + $entrate_vuote ; $b++) {
			$tab_camere .= '<tr>';
			$tab_camere .= '<td class="monospace_style_dx">';
			$tab_camere .= $b + 1;
			$tab_camere .= '</td>';
			$tab_camere .= '<td>';
			$tab_camere .= '<select name="tipo_pre' . $b . '">';
			$tab_camere .= '<option value=""></option>';
			$tab_camere .= '<option value="0">CF</option>';
			$tab_camere .= '<option value="1">OP</option>';
			$tab_camere .= '</select>';
			$tab_camere .= '</td>';
			$tab_camere .= '<td><input type="text" name="data_arrivo' . $b . '" class="field" value="" /></td>';
			$tab_camere .= '<td><input type="text" name="data_partenza' . $b . '" class="field" value="" /></td>';
			$tab_camere .= '<td><input type="text" name="camera' . $b . '" class="field" value="" /></td>';
			$tab_camere .= '<td><input type="text" name="tipologia' . $b . '" class="field" value="" /></td>';
			
			if($b < $num_cam_std) {
				$tab_camere .= '<td><input type="text" name="vestizione' . $b . '" class="field" value="' . $camere_standard[$b]["vestizione"] . '" /></td>';
				$tab_camere .= '<td><input type="text" name="pax' . $b . '" class="field" value="' . $camere_standard[$b]["pax"] . '" /></td>';
			}
			else {
				$tab_camere .= '<td><input type="text" name="vestizione' . $b . '" class="field" value="" /></td>';
				$tab_camere .= '<td><input type="text" name="pax' . $b . '" class="field" value="" /></td>';
			}
			
			$tab_camere .= '<td><input type="text" name="quantita' . $b . '" class="field" value="" /></td>';
			$tab_camere .= '<td><input type="text" name="nome' . $b . '" class="field" value="" /></td>';
			$tab_camere .= '<td><input type="text" name="note' . $b . '" class="field" value="" /></td>';
			$tab_camere .= '<td>';
			$tab_camere .= '<select name="arrangiamento' . $b . '">';
			$tab_camere .= '<option value=""></option>';
			$tab_camere .= '<option value="HB">HB</option>';
			$tab_camere .= '<option value="BB">BB</option>';
			$tab_camere .= '<option value="FB">FB</option>';
			$tab_camere .= '<option value="RS">RS</option>';
			$tab_camere .= '</select> ';
			$tab_camere .= '<select name="primo_pasto' . $b . '">';
			$tab_camere .= '<option value=""></option>';
			$tab_camere .= '<option value="1">B</option>';
			$tab_camere .= '<option value="2">L</option>';
			$tab_camere .= '<option value="3">D</option>';
			$tab_camere .= '</select> ';
			$tab_camere .= '<select name="ultimo_pasto' . $b . '">';
			$tab_camere .= '<option value=""></option>';
			$tab_camere .= '<option value="1">B</option>';
			$tab_camere .= '<option value="2">L</option>';
			$tab_camere .= '<option value="3">D</option>';
			$tab_camere .= '</select>';
			$tab_camere .= '</td>';
			
			$tab_camere .= '<td>';
			$tab_camere .= lista_colori_note(0, $b);
			$tab_camere .= '<input type="hidden" name="id_rif'.$b.'" value="0" />';
			$tab_camere .= '<input type="hidden" name="stile_spe'.$b.'" value="0" />';
			$tab_camere .= '</td>';
			$tab_camere .= '</tr>';
		}
		
		$tab_camere .= '</table></div>';
	}

	// Se bisogna stampare la tabella con i dati di un gruppo, che sia da aggiornare
	// o anche aggiornato o inserito
	else {
		if(isset($_POST['vista_allargata'])) 				$vista_compatta = FALSE;
		elseif(isset($_POST['vista_compatta']))			$vista_compatta = TRUE;
		elseif(isset($_POST['vista_allargata_scelta'])) $vista_compatta = FALSE;
		else 														 	$vista_compatta = TRUE;
		$num_delete = 0; $num_update = 0; $num_insert = 0;
		
		$totale_camere = 0;

		// Se il gruppo va creato
		if(isset($_POST['inserisci'])) {
			$azione = 'GROUP ADDED';
			$colore_gruppo = colore_gruppo_casuale();
			
			// dati da inserire nella table gruppi
			if($_POST['gruppo'] == '') $nome_gruppo = 'NO NAME GROUP';
			else 								$nome_gruppo = formatta_salvataggio($_POST['gruppo']);
			$agenzia		 = formatta_salvataggio($_POST['agenzia']);
			$note_gruppo = formatta_salvataggio($_POST['note_gruppo']);
			
			// Si inserisce un nuovo gruppo alla table gruppi e se ne recupera l'id
			$db = db_connect();
			$db->query('INSERT INTO gruppi (nome,agenzia,note,colore) VALUES (\'' . $nome_gruppo . '\',\'' . $agenzia . '\',\'' . $note_gruppo . '\',' . $colore_gruppo . ')');
			$id_gruppo_grezzo = $db->query('SELECT id FROM gruppi ORDER BY id DESC LIMIT 0,1');
			$db->connection = NULL;
			
			$id_gruppo_tab = $id_gruppo_grezzo->fetch();
			$id_gruppo = &$id_gruppo_tab['id'];

			// Si recuperano le informazioni generiche riguardo al gruppo
			$data_arrivo_generale   = $data_arrivo_gruppo = controllo_data($_POST['data_arrivo_gruppo']);
			$data_partenza_generale = $data_partenza_gruppo = controllo_data($_POST['data_partenza_gruppo']);
			
			if(isset($_POST['tipo_pre_gruppo']) && $_POST['tipo_pre_gruppo'] != '') $tipo_pre_gruppo = intval($_POST['tipo_pre_gruppo']);
			else 																							$tipo_pre_gruppo = intval($_POST['tipo_pre']);
			$arrangiamento_gruppo 	= formatta_salvataggio($_POST['arrangiamento_gruppo']);
			$primo_pasto_gruppo 		= &$_POST['primo_pasto_gruppo'];
			$ultimo_pasto_gruppo 	= &$_POST['ultimo_pasto_gruppo'];
			
			$data_ultima_modifica = time();
			
			// Si prendono i valori per ogni singola riga
			for($i = 0, $num_insert = 0 ; isset($_POST['quantita' . $i]) ; $i++) {
				if($_POST['quantita' . $i] == '') continue;
				
				$prenotazioni_riga = intval($_POST['quantita' . $i]);
				// Si gestisce l'array di camere
				if($_POST['camera' . $i] != '') {
					$camere_riga = explode(',', str_replace(' ', '', $_POST['camera' . $i]));
					$num_camere_riga = count($camere_riga);
				}
				else $num_camere_riga = 0;
				
				$totale_camere += $prenotazioni_riga;
				
				for($j = 0, $camera_attuale = 0 ; $j < $prenotazioni_riga ; $j++) {
					$da_inserire[$num_insert]['id'] = NULL;
					
					$da_inserire[$num_insert]['id_rif'] = intval($_POST['id_rif' . $i]);
					
					if($_POST['tipo_pre' . $i] !== '')
						$da_inserire[$num_insert]['tipo_pre'] = intval($_POST['tipo_pre' . $i]);
					else
						$da_inserire[$num_insert]['tipo_pre'] = &$tipo_pre_gruppo;
					
					if($_POST['data_arrivo' . $i] != '') {
						$da_inserire[$num_insert]['data_arrivo'] = controllo_data($_POST['data_arrivo' . $i]);
						
						if($data_arrivo_generale == 0 || $data_arrivo_generale > $da_inserire[$num_insert]['data_arrivo'])
							$data_arrivo_generale = &$da_inserire[$num_insert]['data_arrivo'];
					}
					else  $da_inserire[$num_insert]['data_arrivo'] = &$data_arrivo_gruppo;
					
					if($_POST['data_partenza' . $i] != '') {
						$da_inserire[$num_insert]['data_partenza'] = controllo_data($_POST['data_partenza' . $i]);
						
						if($data_partenza_generale == 0 || $data_partenza_generale > $da_inserire[$num_insert]['data_partenza'])
							$data_partenza_generale = &$da_inserire[$num_insert]['data_partenza'];
					}
					else  $da_inserire[$num_insert]['data_partenza'] = &$data_partenza_gruppo;
					
					if($camera_attuale < $num_camere_riga) {
						$da_inserire[$num_insert]['camera'] = &$camere_riga[$j];
						$camera_attuale++;
					}
					else 		$da_inserire[$num_insert]['camera'] = 0;
					
					
					$da_inserire[$num_insert]['tipologia'] = formatta_salvataggio($_POST['tipologia' . $i]);
					$da_inserire[$num_insert]['vestizione'] = formatta_salvataggio($_POST['vestizione' . $i]);
					$da_inserire[$num_insert]['pax'] = intval($_POST['pax' . $i]);
					$da_inserire[$num_insert]['nome'] = formatta_salvataggio($_POST['nome' . $i]);
					$da_inserire[$num_insert]['problemi'] = '';
					
					$da_inserire[$num_insert]['note'] = formatta_salvataggio($_POST['note' . $i]);
					
					if($_POST['arrangiamento' . $i] != '') $da_inserire[$num_insert]['arrangiamento'] = formatta_salvataggio($_POST['arrangiamento' . $i]);
					else  											$da_inserire[$num_insert]['arrangiamento'] = &$arrangiamento_gruppo;
					
					if($_POST['primo_pasto' . $i] != '') $da_inserire[$num_insert]['primo_pasto'] = $_POST['primo_pasto' . $i];
					else  										 $da_inserire[$num_insert]['primo_pasto'] = &$primo_pasto_gruppo;
					if($_POST['ultimo_pasto' . $i] != '') $da_inserire[$num_insert]['ultimo_pasto'] = $_POST['ultimo_pasto' . $i];
					else  										  $da_inserire[$num_insert]['ultimo_pasto'] = &$ultimo_pasto_gruppo;
					
					if(isset($_POST['colore_note' . $i]))
									$da_inserire[$num_insert]['colore_note'] = intval($_POST['colore_note' . $i]);
					else 			$da_inserire[$num_insert]['colore_note'] = 0;
					
					$num_insert++;
				}
			}
			
			// Si aggiorna il database
			if($num_insert > 0) {
				// Si recuperano gli eventuali problemi
				controllo_pre($da_inserire);
				
				$sql_insert = 'INSERT INTO prenotazioni (tipo_pre,camera,nome,gruppo,agenzia,vestizione,';
				$sql_insert .=	'tipologia,pax,arrangiamento,primo_pasto,ultimo_pasto,data_arrivo,data_partenza,note,colore_note,problemi,data_ultima_modifica) VALUES ';
				
				for($c = 0 ; $c < $num_insert ; $c++) {
					if($c != 0) $sql_insert .= ',';
					$sql_insert .= '(' . $da_inserire[$c]['tipo_pre'] . ',' . $da_inserire[$c]['camera'] . ',' //
											. '\'' . $da_inserire[$c]['nome'] . '\',' . $id_gruppo . ',\'' . $agenzia . '\',' //
											. '\'' . $da_inserire[$c]['vestizione'] . '\',' //
											. '\'' . $da_inserire[$c]['tipologia'] . '\',' . $da_inserire[$c]['pax'] . ',' //
											. '\'' . $da_inserire[$c]['arrangiamento'] . '\',' . $da_inserire[$c]['primo_pasto'] . ',' . $da_inserire[$c]['ultimo_pasto'] . ',' . $da_inserire[$c]['data_arrivo'] . ',' //
											. $da_inserire[$c]['data_partenza'] . ',\'' . $da_inserire[$c]['note'] . '\',' //
											. $da_inserire[$c]['colore_note'] . ',\'' . $da_inserire[$c]['problemi'] . '\',' //
											. $data_ultima_modifica . ')';
				}
				
				$sql_insert .= ';';
			
				// Inseriamo le nuove prenotazioni nella table prenotazioni
				$db = db_connect();
				$db->query($sql_insert);
				$db->connection = NULL;
			}
		}
		
		// Se il gruppo va modificato
		elseif(isset($_GET['mg'])) {
			$id_gruppo = intval($_GET['mg']);
			
			// Recuperiamo le informazioni del gruppo
			$db = db_connect();
			$gruppo_grezzo = $db->query('SELECT nome,agenzia,colore,note FROM gruppi WHERE id=' . $id_gruppo);
			$db->connection = NULL;
			
			$gruppo_tab = $gruppo_grezzo->fetch(PDO::FETCH_ASSOC);
			
			$agenzia = &$gruppo_tab['agenzia'];
			$note_gruppo = &$gruppo_tab['note'];
			$colore_gruppo = &$gruppo_tab['colore'];
			$data_arrivo_generale = 0;
			$data_partenza_generale = 0;
		}
		
		// Si aggiorna il database
		elseif(isset($_POST['aggiorna'])) {
			$id_gruppo = $_POST['aggiorna'];
			$data_arrivo_generale = 0;
			$data_partenza_generale = 0;
			$azione = 'GROUP MODIFIED';
			$da_eliminare  = array();
			$da_modificare = array();
			$da_inserire   = array();
			$new_id_rif 	= array();
			
			$colore_gruppo = $_POST['colore_gruppo'];
			$agenzia = formatta_salvataggio($_POST['agenzia']);
			$note_gruppo = formatta_salvataggio($_POST['note_gruppo']);
			if($_POST['gruppo'] == '') $nome_gruppo = 'NO NAME GROUP';
			else 								$nome_gruppo = formatta_salvataggio($_POST['gruppo']);
			
			$db = db_connect();
			// Aggiorniamo i dati generici del gruppo
			$db->query('UPDATE gruppi SET nome=\''.$nome_gruppo.'\',agenzia=\''.$agenzia.'\',note=\''.$note_gruppo.'\',colore='.$colore_gruppo.' WHERE id='.$id_gruppo);
			// Recuperiamo tutti gli id delle prenotazioni che fanno riferimento al gruppo
			$id_all_pre_grezzi = $db->query('SELECT id FROM prenotazioni WHERE gruppo='.$id_gruppo);
			$db->connection = NULL;
			
			$id_all_pre = $id_all_pre_grezzi->fetchAll(PDO::FETCH_NUM);
			$num_id_all_pre = count($id_all_pre);
			
			// Si prendono le modifiche generali alla testata del gruppo
			if($_POST['data_arrivo_gruppo'] != '') $data_arrivo_gruppo = controllo_data($_POST['data_arrivo_gruppo']);
			else 												$data_arrivo_gruppo = '';
			if($_POST['data_partenza_gruppo'] != '') $data_partenza_gruppo = controllo_data($_POST['data_partenza_gruppo']);
			else 												  $data_partenza_gruppo = '';

			if(isset($_POST['tipo_pre_gruppo']) && $_POST['tipo_pre_gruppo'] != '') $tipo_pre_gruppo = intval($_POST['tipo_pre_gruppo']);
			else 																							$tipo_pre_gruppo = '';
			$arrangiamento_gruppo = formatta_salvataggio($_POST['arrangiamento_gruppo']);
			$primo_pasto_gruppo = &$_POST['primo_pasto_gruppo'];
			$ultimo_pasto_gruppo = &$_POST['ultimo_pasto_gruppo'];
			
			$data_ultima_modifica = time();
			
			// Si strutturano i dati delle prenotazioni
			
			// Si prendono i valori per ogni singola riga
			for($i = 0, $num_pre = 0 ; isset($_POST['quantita' . $i]) ; $i++) {
				// Se la quantità è 0 oppure se si è spuntato elimina non si aggiunge la riga alle prenotazioni da inserire
				if($_POST['pax'.$i] === '' || $_POST['quantita'.$i] == '' || isset($_POST['elimina'.$i])) continue;
				
				$prenotazioni_riga = intval($_POST['quantita'.$i]);
				// Si gestisce l'array di camere
				if($_POST['camera' . $i] != '') {
					$camere_riga = explode(',', str_replace(' ', '', $_POST['camera' . $i]));
					$num_camere_riga = count($camere_riga);
				}
				else $num_camere_riga = 0;
				
				$totale_camere += $prenotazioni_riga;
				
				for($j = 0, $camera_attuale = 0 ; $j < $prenotazioni_riga ; $j++) {
					// Se non è una speciale e se abbiamo scelto di modificare il tipo pre generale
					if($tipo_pre_gruppo !== '' && !isset($_POST['disabled'.$i]))
						$da_inserire[$num_pre]['tipo_pre'] = &$tipo_pre_gruppo;
					else
						$da_inserire[$num_pre]['tipo_pre'] = intval($_POST['tipo_pre'.$i]);
					
					if(isset($_POST['id'.$i])) $da_inserire[$num_pre]['id'] = intval($_POST['id'.$i]);
					else 								$da_inserire[$num_pre]['id'] = NULL;
					$da_inserire[$num_pre]['id_rif'] = intval($_POST['id_rif'.$i]);
					$da_inserire[$num_pre]['stile_spe'] = intval($_POST['stile_spe'.$i]);
					
					if($data_arrivo_gruppo != '') $da_inserire[$num_pre]['data_arrivo'] = &$data_arrivo_gruppo;
					else 									$da_inserire[$num_pre]['data_arrivo'] = controllo_data($_POST['data_arrivo' . $i]);
					

					if($data_partenza_gruppo != '') $da_inserire[$num_pre]['data_partenza'] = &$data_partenza_gruppo;
					else 									  $da_inserire[$num_pre]['data_partenza'] = controllo_data($_POST['data_partenza' . $i]);
					
					if($camera_attuale < $num_camere_riga) {
						$da_inserire[$num_pre]['camera'] = &$camere_riga[$j];
						$camera_attuale++;
					}
					else 		$da_inserire[$num_pre]['camera'] = 0;
					
					
					$da_inserire[$num_pre]['tipologia'] = formatta_salvataggio($_POST['tipologia' . $i]);
					$da_inserire[$num_pre]['vestizione'] = formatta_salvataggio($_POST['vestizione' . $i]);
					$da_inserire[$num_pre]['pax'] = intval($_POST['pax' . $i]);
					$da_inserire[$num_pre]['nome'] = formatta_salvataggio($_POST['nome' . $i]);
					$da_inserire[$num_pre]['problemi'] = '';
					
					$da_inserire[$num_pre]['note'] = formatta_salvataggio($_POST['note' . $i]);
						
					if($arrangiamento_gruppo != '')  $da_inserire[$num_pre]['arrangiamento'] = &$arrangiamento_gruppo;
					else 										$da_inserire[$num_pre]['arrangiamento'] = formatta_salvataggio($_POST['arrangiamento' . $i]);
					
					if($primo_pasto_gruppo != '')  $da_inserire[$num_pre]['primo_pasto'] = &$primo_pasto_gruppo;
					else 									 $da_inserire[$num_pre]['primo_pasto'] = intval($_POST['primo_pasto' . $i]);
						
					if($ultimo_pasto_gruppo != '') $da_inserire[$num_pre]['ultimo_pasto'] = &$ultimo_pasto_gruppo;
					else 									 $da_inserire[$num_pre]['ultimo_pasto'] = intval($_POST['ultimo_pasto' . $i]);
					
					if(isset($_POST['colore_note' . $i]))
									$da_inserire[$num_pre]['colore_note'] = intval($_POST['colore_note' . $i]);
					else 			$da_inserire[$num_pre]['colore_note'] = 0;
					
					
					// Si inserisce negli aggiornamenti se una prenotazione esiste già
					if($num_update < $num_id_all_pre) {
						// Se la principale è correlata a delle speciali si prende il suo nuovo id
						if($da_inserire[$num_pre]['tipo_pre'] <= 9 && $da_inserire[$num_pre]['stile_spe'] != 0) {
							$new_id_rif[$da_inserire[$num_pre]['id']] = $id_all_pre[$num_pre][0];
						}
							
						// Assegnamo l'id per la modifica
						$da_inserire[$num_pre]['id'] = $id_all_pre[$num_pre][0];
						
						$num_update++;
					}
					// Si inserisce nelle nuove entrate se si è superato il numero di id
					else {
						$num_insert++;
						$da_inserire[$num_pre]['id'] = NULL;
					}
					
					
					$num_pre++;
				}
			}

			// Eliminiamo le prenotazioni da eliminare prima di controllare quelle che rimangono per evitare errori
			// Per eliminare quelle non più utili
			if($num_update < $num_id_all_pre) {
				$num_delete = $num_id_all_pre - $num_update;
				
				$sql_delete = 'DELETE FROM prenotazioni WHERE id IN (';
				
				for($i = $num_update ; $i < $num_id_all_pre ; $i++) {
					if($i != $num_update) $sql_delete .= ',';
					$sql_delete .= $id_all_pre[$i][0];
				}
				
				$sql_delete .= ')';
			
				$db = db_connect();
				$request = $db->prepare($sql_delete); $request->execute();
				$db->connection = NULL;
			}

			// Si controllano gli eventuali problemi per tutte le prenotazioni
			if($num_pre > 0) {
				$problemi_update = controllo_pre($da_inserire);
			}

			if($num_update > 0) {
				
				$sql_update = 'UPDATE prenotazioni A inner join(';
				
				for($d = 0 ; $d < $num_update ; $d++) {
					// Se si tratta di una speciale si aggiorna l'id_rif
					if($da_inserire[$d]['tipo_pre'] > 9) {
						$da_inserire[$d]['id_rif'] = $new_id_rif[$da_inserire[$d]['id_rif']];
					}
					
					
					if($d > 0)  { $sql_update .= ' UNION '; }
					if($d == 0)
			   		$sql_update .= ' SELECT '.$da_inserire[$d]['id'].' id, '.$da_inserire[$d]['id_rif'].' id_rif, '.$da_inserire[$d]['tipo_pre'].' tipo_pre,'.$da_inserire[$d]['stile_spe'].' stile_spe,' //
			   							. $da_inserire[$d]['camera'].' camera, \''.$da_inserire[$d]['nome'].'\' nome,' //
			   							. '\''.$agenzia.'\' agenzia, ' //
			   							. '\''.$da_inserire[$d]['vestizione'].'\' vestizione, \''.$da_inserire[$d]['tipologia'].'\' tipologia,' //
			   							. $da_inserire[$d]['pax'].' pax, \''.$da_inserire[$d]['arrangiamento'].'\' arrangiamento,' //
			   							. $da_inserire[$d]['primo_pasto'].' primo_pasto, '.$da_inserire[$d]['ultimo_pasto'].' ultimo_pasto,' //
			   							. $da_inserire[$d]['data_arrivo'].' data_arrivo, '.$da_inserire[$d]['data_partenza'].' data_partenza,' //
			   							. '\''.$da_inserire[$d]['note'].'\' note, '.$da_inserire[$d]['colore_note'].' colore_note,' //
			   							. '\''.$da_inserire[$d]['problemi'].'\' problemi, '.$data_ultima_modifica.' data_ultima_modifica';
					else
			   		$sql_update .= ' SELECT '.$da_inserire[$d]['id'].', '.$da_inserire[$d]['id_rif'].', '.$da_inserire[$d]['tipo_pre'].','.$da_inserire[$d]['stile_spe'].',' //
			   							. $da_inserire[$d]['camera'].', \''.$da_inserire[$d]['nome'].'\',' //
			   							. '\''.$agenzia.'\', ' //
			   							. '\''.$da_inserire[$d]['vestizione'].'\', \''.$da_inserire[$d]['tipologia'].'\',' //
			   							. $da_inserire[$d]['pax'].', \''.$da_inserire[$d]['arrangiamento'].'\',' //
			   							. $da_inserire[$d]['primo_pasto'].', '.$da_inserire[$d]['ultimo_pasto'].',' //
			   							. $da_inserire[$d]['data_arrivo'].', '.$da_inserire[$d]['data_partenza'].',' //
			   							. '\''.$da_inserire[$d]['note'].'\', '.$da_inserire[$d]['colore_note'].',' //
			   							. '\''.$da_inserire[$d]['problemi'].'\', '.$data_ultima_modifica;
				}
				
				$sql_update .= ') B USING (id) SET A.id_rif = B.id_rif, A.tipo_pre = B.tipo_pre, A.stile_spe = B.stile_spe, A.camera = B.camera,' //
									. 'A.nome = B.nome,A.agenzia = B.agenzia, A.vestizione = B.vestizione,' //
									. 'A.tipologia = B.tipologia, A.pax = B.pax,' //
									. 'A.arrangiamento = B.arrangiamento, A.primo_pasto = B.primo_pasto, A.ultimo_pasto = B.ultimo_pasto, A.data_arrivo = B.data_arrivo,' //
									. 'A.data_partenza = B.data_partenza, A.note = B.note, A.colore_note = B.colore_note,' //
									. 'A.problemi = B.problemi, A.data_ultima_modifica = B.data_ultima_modifica' //
									. ';';
			}
			
			if($num_insert > 0) {
				$sql_insert = 'INSERT INTO prenotazioni (tipo_pre,camera,nome,gruppo,agenzia,vestizione,';
				$sql_insert .=	'tipologia,pax,arrangiamento,primo_pasto,ultimo_pasto,data_arrivo,data_partenza,note,colore_note,problemi,data_ultima_modifica) VALUES ';
				
				for($c = $num_update ; $c < $num_insert + $num_update ; $c++) {
					if($c != $num_update) $sql_insert .= ',';
					$sql_insert .= '(' . $da_inserire[$c]['tipo_pre'] . ', ' . $da_inserire[$c]['camera'] . ',' //
											. '\'' . $da_inserire[$c]['nome'] . '\', ' . $id_gruppo . ', ' //
											. '\'' . $agenzia . '\', \'' . $da_inserire[$c]['vestizione'] . '\', ' //
											. '\'' . $da_inserire[$c]['tipologia'] . '\', ' . $da_inserire[$c]['pax'] . ', ' //
											. '\'' . $da_inserire[$c]['arrangiamento'] . '\', ' . $da_inserire[$c]['primo_pasto'] . ', ' . $da_inserire[$c]['ultimo_pasto'] . ', ' . $da_inserire[$c]['data_arrivo'] . ', ' //
											. $da_inserire[$c]['data_partenza'] . ', \'' . $da_inserire[$c]['note'] . '\', ' //
											. $da_inserire[$c]['colore_note'] . ', \'' . $da_inserire[$c]['problemi'] . '\', ' //
											. $data_ultima_modifica . ')';
				}
				
				$sql_insert .= ';';
			}

			// Aggiorniamo i dati nella table prenotazioni
			$db = db_connect();
try {
 	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			if($num_update > 0) { $request = $db->prepare($sql_update); $request->execute(); }
			if($num_insert > 0) { $request = $db->prepare($sql_insert);	$request->execute(); }
}
catch(Exception $e) {
	echo 'Exception -> ';
 	var_dump($e->getMessage());
}
			$db->connection = NULL;
		}

		// Recuperiamo i dati aggiornati
		$db = db_connect();
		$reponse = $db->query(//
		'SELECT prenotazioni.*, gruppi.nome AS nome_gruppo, gruppi.agenzia AS agenzia_gruppo, gruppi.colore AS colore_gruppo, gruppi.note AS note_gruppo FROM prenotazioni ' //
		. 'INNER JOIN gruppi ON gruppo=gruppi.id ' //
		. 'WHERE gruppo=' . $id_gruppo . ' ORDER BY data_arrivo, data_partenza, camera, vestizione, tipologia, nome, note');
		$db->connection = NULL;

		$tab_camere = '<div class="form1_all">';
		$tab_camere .= '<p class="titolo">MODIFICA GRUPPO</p>';
		$tab_camere .= '<table><tr><th></th><th>TIPO P.</th><th class="date_ge_gru">DAL</th><th class="date_ge_gru">AL</th><th class="small_tab1">CAM</th><th class="small_tab1">TIPO.</th><th class="small_tab1">VEST.</th><th class="small_tab1">PAX</th>';
		if($vista_compatta == TRUE) $tab_camere .= '<th class="small_tab1">Q.T&Agrave;</th>';
		$tab_camere .= '<th>NOME</th><th>NOTE</th><th class="medium_tab2">A</th><th>COLORE NOTE</th><th>CLX</th></tr>';
		
		$donnees = $reponse->fetchAll(PDO::FETCH_ASSOC);
		$num_pre = count($donnees);
		for($b = 1, $quantita_corrente = 1, $riga = 0 ; $b <= $num_pre ; $b++) {
			// HACK per stampare anche l'ultima linea
			if($vista_compatta == TRUE && $b != $num_pre) {
				if($b != 0 && compara_prenotazioni($donnees[$b - 1], $donnees[$b])) {
					$quantita_corrente++;
					continue;
				}
			}
			// Hack per la vista compatta
			$b--;
			
			$info_pre[$b]['id'] 				 = &$donnees[$b]['id'];
			$info_pre[$b]['id_rif'] 		 = &$donnees[$b]['id_rif'];
			$info_pre[$b]['tipo_pre'] 		 = &$donnees[$b]['tipo_pre'];
			$info_pre[$b]['stile_spe'] 	 = &$donnees[$b]['stile_spe'];
			$info_pre[$b]['nome']			 = formatta_visualizzazione($donnees[$b]['nome']);
			$info_pre[$b]['gruppo'] 		 = formatta_visualizzazione($donnees[$b]['gruppo']);
			$info_pre[$b]['data_arrivo']	 = &$donnees[$b]['data_arrivo'];
			$info_pre[$b]['data_partenza'] = &$donnees[$b]['data_partenza'];
			$info_pre[$b]['vestizione']	 = formatta_visualizzazione($donnees[$b]['vestizione']);
			$info_pre[$b]['tipologia']		 = formatta_visualizzazione($donnees[$b]['tipologia']);
			$info_pre[$b]['pax']				 = &$donnees[$b]['pax'];
			$info_pre[$b]['arrangiamento'] = formatta_visualizzazione($donnees[$b]['arrangiamento']);
			$info_pre[$b]['primo_pasto']	 = &$donnees[$b]['primo_pasto'];
			$info_pre[$b]['ultimo_pasto']	 = &$donnees[$b]['ultimo_pasto'];
			
			// Se la prenotazione è una speciale connessa a speciali
			if($info_pre[$b]['tipo_pre'] > 9 || $info_pre[$b]['stile_spe'] != 0) {
				$protected = ' readonly="readonly"';
				$disabled = 'disabled="disabled"';
				$field_dark = ' field_protected';
				
				// Se la spe è una camera aggiuntiva
				if($info_pre[$b]['tipo_pre'] >= 10 && $info_pre[$b]['tipo_pre'] <= 19) {
					$tipo_spe = 'CAM AGG';
				}
				// Se la spe è un cambio camera
				elseif($info_pre[$b]['tipo_pre'] >= 20 && $info_pre[$b]['tipo_pre'] <= 29) {
					$tipo_spe = '<- ->';
				}
				// Se la spe è uno share
				elseif($info_pre[$b]['tipo_pre'] >= 30 && $info_pre[$b]['tipo_pre'] <= 39) {
					$tipo_spe = 'SHARE';
				}
				else $tipo_spe = 'MAIN';
			}
			else {
				$protected = '';
				$disabled = '';
				$field_dark = '';
			}
			
			// Gestiamo la prima e l'ultima data del gruppo
			if($riga == 0) { $data_arrivo_generale = &$donnees[$b]['data_arrivo']; $data_partenza_generale = &$donnees[$b]['data_partenza']; }
			else {
				if($donnees[$b]['data_arrivo'] < $data_arrivo_generale)		 $data_arrivo_generale   = &$donnees[$b]['data_arrivo'];
				if($donnees[$b]['data_partenza'] > $data_partenza_generale)  $data_partenza_generale = &$donnees[$b]['data_partenza'];
			}
			
			$data_arrivo			 = date("d/m/Y", $donnees[$b]['data_arrivo']);
			$data_partenza 		 = date("d/m/Y", $donnees[$b]['data_partenza']);
			
			
			$info_pre[$b]['camera'] 				  = &$donnees[$b]['camera'];
			$info_pre[$b]['note']			 		  = formatta_visualizzazione($donnees[$b]['note']);
			$info_pre[$b]['colore_note'] 			  = $donnees[$b]['colore_note'];
			$info_pre[$b]['problemi']				  = &$donnees[$b]['problemi'];
			
			$tab_camere .= '<tr>';
			$tab_camere .= '<td class="monospace_style_dx">';
			$tab_camere .= '<div class="hover_hide">'.($riga+1).'</div>';
			
			// Se si tratta di una prenotazione principale e non ci sono più camere sulla stessa riga
			if($info_pre[$b]['tipo_pre'] <= 9 && ($vista_compatta == FALSE || $quantita_corrente <= 1))
				$tab_camere .= '<div class="hover_show"><a class="bottone bottone_small" href="ge_pre.php?mp='.$info_pre[$b]['id'].'">M</a></div>';
			// Se si tratta di una speciale si rimanda alla modifica della principale
			elseif($info_pre[$b]['tipo_pre'] > 9 && ($vista_compatta == FALSE || $quantita_corrente <= 1))
				$tab_camere .= '<div class="hover_show"><a class="bottone bottone_small" href="ge_pre.php?mp='.$info_pre[$b]['id_rif'].'">M</a></div>';
				
			$tab_camere .= '</td>';
			$tab_camere .= '<td>';
			if($protected != '') {
				$tab_camere .= '<span class="tipo_pre_gru">'.$tipo_spe.'</span>';
				$tab_camere .= '<input type="hidden" name="tipo_pre' . $riga . '" value="'.$info_pre[$b]['tipo_pre'].'" />';
			}
			else {
				$tab_camere .= '<select'.$protected.' name="tipo_pre' . $riga . '">';
				$tab_camere .= '<option '; if($info_pre[$b]['tipo_pre'] == 0) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="0">CF</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['tipo_pre'] == 1) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="1">OP</option>';
				$tab_camere .= '</select>';
			}
			$tab_camere .= '</td>'
			. '<td><input type="text" name="data_arrivo' . $riga . '" class="field'.$field_dark.'"'.$protected.' value="' . $data_arrivo . '" /></td>'
			. '<td><input type="text" name="data_partenza' . $riga . '" class="field'.$field_dark.'"'.$protected.' value="' . $data_partenza . '" /></td>'
			. '<td><input type="text" name="camera' . $riga . '" class="field'.$field_dark.'"'.$protected.' value="' . $info_pre[$b]['camera'] . '" /></td>'
			. '<td><input type="text" name="tipologia' . $riga . '" class="field'.$field_dark.'"'.$protected.' value="' . $info_pre[$b]['tipologia'] . '" /></td>'
			. '<td><input type="text" name="vestizione' . $riga . '" class="field'.$field_dark.'"'.$protected.' value="' . $info_pre[$b]['vestizione'] . '" /></td>'
			. '<td><input type="text" name="pax' . $riga . '" class="field'.$field_dark.'"'.$protected.' value="' . $info_pre[$b]['pax'] . '" /></td>';
			
			if($vista_compatta == TRUE)
				$tab_camere .= '<td><input type="text" name="quantita' . $riga . '" class="field'.$field_dark.'"'.$protected.' value="' . $quantita_corrente . '" /></td>';
			else 
				$tab_camere .= '<input type="hidden" name="quantita' . $riga . '" value="1" />';
			
			$quantita_corrente = 1;
			
			$tab_camere .= '<td><input type="text" name="nome' . $riga . '" class="field'.$field_dark.'"'.$protected.' value="' . $info_pre[$b]['nome'] . '" /></td>';
			$tab_camere .= '<td><input type="text" name="note' . $riga . '" class="field'.$field_dark.'"'.$protected.' value="' . $info_pre[$b]['note'] . '" /></td>';
			$tab_camere .= '<td>';
			if($disabled != '') {
				$tab_camere .= '<select name="dis_arr' . $riga . '"'.$disabled.'>';
				$tab_camere .= '<option '; if($info_pre[$b]['arrangiamento'] == 'HB' || $info_pre[$b]['arrangiamento'] == '') $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="HB">HB</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['arrangiamento'] == 'BB') $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="BB">BB</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['arrangiamento'] == 'FB') $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="FB">FB</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['arrangiamento'] == 'RS') $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="RS">RS</option>';
				$tab_camere .= '</select> ';
				$tab_camere .= '<select name="dis_primo_pasto' . $riga . '"'.$disabled.'>';
				$tab_camere .= '<option '; if($info_pre[$b]['primo_pasto'] == 1) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="1">B</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['primo_pasto'] == 2) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="2">L</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['primo_pasto'] == 3) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="3">D</option>';
				$tab_camere .= '</select> ';
				$tab_camere .= '<select name="dis_ultimo_pasto' . $riga . '"'.$disabled.'>';
				$tab_camere .= '<option '; if($info_pre[$b]['ultimo_pasto'] == 1) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="1">B</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['ultimo_pasto'] == 2) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="2">L</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['ultimo_pasto'] == 3) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="3">D</option>';
				$tab_camere .= '</select>';
				
				// HIDDEN PERCHE SE DISABLED I DATI DELL'INPUT NON SONO TRASMESSI
				$tab_camere .= '<input type="hidden" name="arrangiamento' . $riga . '" value="'.$info_pre[$b]['arrangiamento'].'" />';
				$tab_camere .= '<input type="hidden" name="primo_pasto' . $riga . '" value="'.$info_pre[$b]['primo_pasto'].'" />';
				$tab_camere .= '<input type="hidden" name="ultimo_pasto' . $riga . '" value="'.$info_pre[$b]['ultimo_pasto'].'" />';
				
				// CAMPO NASCOSTO CHE IDENTIFICA LA RIGA COME DISABILITATA
				$tab_camere .= '<input type="hidden" name="disabled' . $riga . '" value="1" />';
			}
			else {
				$tab_camere .= '<select name="arrangiamento' . $riga . '">';
				$tab_camere .= '<option '; if($info_pre[$b]['arrangiamento'] == 'HB' || $info_pre[$b]['arrangiamento'] == '') $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="HB">HB</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['arrangiamento'] == 'BB') $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="BB">BB</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['arrangiamento'] == 'FB') $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="FB">FB</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['arrangiamento'] == 'RS') $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="RS">RS</option>';
				$tab_camere .= '</select> ';
				$tab_camere .= '<select name="primo_pasto' . $riga . '">';
				$tab_camere .= '<option '; if($info_pre[$b]['primo_pasto'] == 1) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="1">B</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['primo_pasto'] == 2) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="2">L</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['primo_pasto'] == 3) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="3">D</option>';
				$tab_camere .= '</select> ';
				$tab_camere .= '<select name="ultimo_pasto' . $riga . '">';
				$tab_camere .= '<option '; if($info_pre[$b]['ultimo_pasto'] == 1) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="1">B</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['ultimo_pasto'] == 2) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="2">L</option>';
				$tab_camere .= '<option '; if($info_pre[$b]['ultimo_pasto'] == 3) $tab_camere .= 'selected="selected" '; $tab_camere .= 'value="3">D</option>';
				$tab_camere .= '</select>';
			}
		
			// CAMPO NASCOSTO PER STILE SPE E ID RIF
			$tab_camere .= '<input type="hidden" name="id' . $riga . '" value="'.$info_pre[$b]['id'].'" />';
			$tab_camere .= '<input type="hidden" name="id_rif' . $riga . '" value="'.$info_pre[$b]['id_rif'].'" />';
			$tab_camere .= '<input type="hidden" name="stile_spe' . $riga . '" value="'.$info_pre[$b]['stile_spe'].'" />';
			
			$tab_camere .= '</td>';
			
			$tab_camere .= '<td>';
			$tab_camere .= lista_colori_note($info_pre[$b]['colore_note'], $riga, $disabled);
			$tab_camere .= '</td>';
			
			$tab_camere .= '<td><input type="checkbox" name="elimina' . $riga . '" value="' . $info_pre[$b]['id'] . '"'.$disabled.' /></td>';
			$tab_camere .= '</tr>';
			
			// Hack per la vista compatta
			$b++;
			$riga++;
		}
		
		// INSERIAMO CAMERE NEL CASO SI VOGLIANO AGGIUNGERE
		$voci_da_aggiungere = 10;
		for($fine_tab = $riga + $voci_da_aggiungere, $j = $riga ; $j < $fine_tab ; $j++) {
			$tab_camere .= '<tr>'
			. '<td class="monospace_style_dx">'
			. ($j + 1)
			. '</td>'
			. '<td>'
			. '<select name="tipo_pre' . $j . '">'
			. '<option selected="selected" value="0">CF</option>'
			. '<option value="1">OP</option>'
			. '</select>'
			. '</td>'
			. '<td><input type="text" name="data_arrivo' . $j . '" class="field" value="' . date("d/m/Y", $data_arrivo_generale) . '" /></td>'
			. '<td><input type="text" name="data_partenza' . $j . '" class="field" value="' . date("d/m/Y", $data_partenza_generale) . '" /></td>'
			. '<td><input type="text" name="camera' . $j . '" class="field" value="" /></td>'
			. '<td><input type="text" name="tipologia' . $j . '" class="field" value="" /></td>'
			. '<td><input type="text" name="vestizione' . $j . '" class="field" value="" /></td>'
			. '<td><input type="text" name="pax' . $j . '" class="field" value="" /></td>';
	
			if($vista_compatta == TRUE)
				$tab_camere .= '<td><input type="text" name="quantita' . $j . '" class="field" value="" /></td>';
			else 
				$tab_camere .= '<input type="hidden" name="quantita' . $j . '" class="field" value="1" />';

			$tab_camere .= '<td><input type="text" name="nome' . $j . '" class="field" value="" /></td>'
			. '<td><input type="text" name="note' . $j . '" class="field" value="" /></td>'
			. '<td>'
			. '<select name="arrangiamento' . $j . '">'
			. '<option selected="selected" value="HB">HB</option>'
			. '<option value="BB">BB</option>'
			. '<option value="FB">FB</option>'
			. '<option value="RS">RS</option>'
			. '</select> '
			. '<select name="primo_pasto' . $j . '">'
			. '<option value="1">B</option>'
			. '<option value="2">L</option>'
			. '<option selected="selected" value="3">D</option>'
			. '</select> '
			. '<select name="ultimo_pasto' . $j . '">'
			. '<option selected="selected" value="1">B</option>'
			. '<option value="2">L</option>'
			. '<option value="3">D</option>'
			. '</select>'
			. '</td>'
			. '<td>'
			. lista_colori_note(0, $j);
			
			// CAMPO NASCOSTO PER STILE SPE E ID RIF
			$tab_camere .= '<input type="hidden" name="id_rif'.$j.'" value="0" />';
			$tab_camere .= '<input type="hidden" name="stile_spe'.$j.'" value="0" />';
			
			$tab_camere .= '</td>';
			$tab_camere .= '</tr>';
		}
		
		$tab_camere .= '</table></div>';
		
		// Si crea la testata del gruppo
		$tab_testata = '<div class="form1_all">'
		. '<p class="titolo">MODIFICHE GENERICHE</p>'
		. '<table class="center_block"><tr><th>TIPO P.</th><th>A</th><th class="date_ge_gru">MOD. ARR.</th><th class="date_ge_gru">MOD. PAR.</th><th>NOME GRUPPO</th><th>NOME AGENZIA</th><th>NOTE GRUPPO</th></tr>'
		. '<tr>'
		. '<td>'
		. '<select name="tipo_pre_gruppo">'
		. '<option value=""></option>'
		. '<option value="0">CF</option>'
		. '<option value="1">OP</option>'
		. '</select>'
		. '</td>'
		. '<td>'
		. '<select name="arrangiamento_gruppo">'
		. '<option value=""></option>'
		. '<option value="HB">HB</option>'
		. '<option value="BB">BB</option>'
		. '<option value="FB">FB</option>'
		. '<option value="RS">RS</option>'
		. '</select> '
		. '<select name="primo_pasto_gruppo">'
		. '<option value=""></option>'
		. '<option value="1">B</option>'
		. '<option value="2">L</option>'
		. '<option value="3">D</option>'
		. '</select> '
		. '<select name="ultimo_pasto_gruppo">'
		. '<option value=""></option>'
		. '<option value="1">B</option>'
		. '<option value="2">L</option>'
		. '<option value="3">D</option>'
		. '</select>'
		. '</td>'
		. '<td><input type="text" name="data_arrivo_gruppo" class="field" value="" /></td>'
		. '<td><input type="text" name="data_partenza_gruppo" class="field" value="" /></td>'
		. '<td><input type="text" name="gruppo" class="field" value="';
		if($num_pre > 0) $tab_testata .= $donnees[0]['nome_gruppo'];
		$tab_testata .= '" /></td>';
		$tab_testata .= '<td><input type="text" name="agenzia" class="field" value="';
		if($num_pre > 0) $tab_testata .= $donnees[0]['agenzia_gruppo'];
		$tab_testata .= '" /></td>';
		$tab_testata .= '<td><input type="text" name="note_gruppo" class="field" value="';
		if($num_pre > 0) $tab_testata .= $donnees[0]['note_gruppo'];
		$tab_testata .= '" /></td>';
		$tab_testata .= '</tr>';
		$tab_testata .= '</table></div>';
	}
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head><?php
	if(isset($nuovo_gruppo)) echo '<title>NUOVO GRUPPO</title>';
	else 							 echo '<title>MODIFICA GRUPPO</title>';
	header_standard();
?></head>
<body><?php
	$testo_menu_top = '';
	if(isset($nuovo_gruppo)) $testo_menu_top = '';
	else {
		$testo_menu_top = ' | <a href="rooming_list.php?id_gruppo='.$id_gruppo.'">ROOMING LIST</a>'//
							  .' | <a href="ass_cam.php?id_gruppo='.$id_gruppo.'">ASSEGNAZIONE CAMERE</a>'//
							  .' | <a href="prenotazioni.php?id_gruppo='.$id_gruppo.'">GUARDA A BOOKING</a>';
	}
	menu_top($testo_menu_top);
?>
		<div id="corpo_a"><?php
		
		// Se siamo nel formulario per inserire un nuovo gruppo
		if(isset($nuovo_gruppo)) {
			echo '<form style="display: inline;" name="dati" action="ge_gru.php" method="post" enctype="multipart/form-data">';
		
			$pulsanti = '<a href="lista_pre.php">INDIETRO</a> <input class="bottone" type="submit" value="INSERISCI" />';
			echo colonna_sinistra($pulsanti, $vuoto, $vuoto, lista_colori_gruppo(colore_gruppo_casuale()));
		
			echo '<div class="cont_dx">';

			echo $tab_testata . $tab_camere;
			echo '</div>';

			echo '<input type="hidden" name="inserisci" value="1" />';
			echo '</form>';
		}
		
		// Se siamo nel formulario per modificare un gruppo
		// O se l'abboamo modificato o creato riproponiamo il formulario di modifica gruppo
		else 	{
			echo '<form style="display: inline;" name="dati" action="ge_gru.php" method="post" enctype="multipart/form-data">';
			
			$pulsanti = '<a href="lista_pre.php">INDIETRO</a> <input class="bottone" type="submit" value="MODIFICA" />';
			if(isset($_POST['vista_allargata'])) {
				$pulsanti .= '<input class="bottone" type="submit" name="vista_compatta" value="VISTA COMPATTA" />';
				$pulsanti .= '<input type="hidden" name="vista_allargata_scelta" />';
			}
			elseif(isset($_POST['vista_compatta'])) {
				$pulsanti .= '<input class="bottone" type="submit" name="vista_allargata" value="VISTA ALLARGATA" />';
				$pulsanti .= '<input type="hidden" name="vista_compatta_scelta" />';
			}
			elseif(isset($_POST['vista_allargata_scelta'])) {
				$pulsanti .= '<input class="bottone" type="submit" name="vista_compatta" value="VISTA COMPATTA" />';
				$pulsanti .= '<input type="hidden" name="vista_allargata_scelta" />';
			}
			else {
				$pulsanti .= '<input class="bottone" type="submit" name="vista_allargata" value="VISTA ALLARGATA" />';
				$pulsanti .= '<input type="hidden" name="vista_compatta_scelta" />';
			}
			
			$liste[0] = riepilogo_gruppo($donnees);
			
			$pagina = "gestione_gruppi";
			
			if($donnees != NULL)
				echo colonna_sinistra($pulsanti, print_problemi($donnees, $pagina), $liste, lista_colori_gruppo($donnees[0]['colore_gruppo']));

			echo '<div class="cont_dx">';
			
			// Se si è modificato il gruppo eliminando tutte lecamere
			if($azione === 'GROUP MODIFIED' && $donnees == NULL) {
				echo '<div class="form1_all form_green">';
				echo '<p class="titolo">GRUPPO ELIMINATO CON SUCCESSO</p>';
				echo '</div>';
			}
			
			elseif($azione === 'GROUP MODIFIED') {
				echo '<div class="form1_all form_green">';
				echo '<p class="titolo">GRUPPO MODIFICATO CON SUCCESSO <a class="bottone guarda_b" href="prenotazioni.php?id_gruppo='.$id_gruppo.'">GUARDA A BOOKING</a></p>';
				echo '</div>';
			}
			
			elseif($azione === 'GROUP ADDED') {
				echo '<div class="form1_all form_green">';
				echo '<p class="titolo">GRUPPO AGGIUNTO CON SUCCESSO <a class="bottone guarda_b" href="prenotazioni.php?id_gruppo='.$id_gruppo.'">GUARDA A BOOKING</a></p>';
				echo '</div>';
			}
			
			echo $tab_testata . $tab_camere;
			
			echo '</div>';

			echo '<input type="hidden" name="aggiorna" value="' . $id_gruppo . '" />';
			echo '</form>';
		}
		
		?></div>
		
	</body>
</html><?php

	// Aggiorniamo i dati della table GRUPPI
	if($azione === 'GROUP MODIFIED' || $azione === 'GROUP ADDED') {
		$db = db_connect();
		if($liste[0]['totali']['camere'] > 0) {
			// Aggiorniamo il database con le modifiche fatte
			$set_update = 'nome=?, data_arrivo=?, data_partenza=?, riepilogo=?, totale_camere=?, camere_non_assegnate=?, totale_pax=?, agenzia=?, note=?, colore=?';
			$array_update = array($nome_gruppo, $data_arrivo_generale, $data_partenza_generale, print_riepilogo_gruppo($liste[0]), $liste[0]['totali']['camere'], //
										 $liste[0]['totali']['na'], $liste[0]['totali']['pax'], $agenzia, $note_gruppo, $colore_gruppo, $id_gruppo);
	
			// Si modifica l'elemento nel db
			$sql = 'UPDATE gruppi SET ' . $set_update . ' WHERE id=?';
			$request = $db->prepare($sql);
			$request->execute($array_update);
		}
		// Se non ci sono camere associate al gruppo si elimina il gruppo
		else {
			$db->query('DELETE FROM gruppi WHERE id='.$id_gruppo);
		}
		$db->connection = NULL;
	}

} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>