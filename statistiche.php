<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore
	$data = time();
	
	$info_mese = array();
	$info_day = array();
	
	$giorni_nel_mese = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	
	// Definiamo la prima e l'ultima data
	if(isset($_POST['estate'])) {
		$data_scelta = $_SESSION['inizio_estate'];
		$data_scelta_ultima = $_SESSION['fine_estate'];
	}
	elseif(isset($_POST['inverno'])) {
		$data_scelta = $_SESSION['inizio_inverno'];
		$data_scelta_ultima = $_SESSION['fine_inverno'];
	}
	else {
		$data_scelta = controllo_data($_POST['data_arrivo']);
		$data_scelta_ultima = controllo_data($_POST['data_partenza']);
	}
	
	// Se si è già inviato il form se controllato i dati inseriti
	if(isset($_POST['agenzia'])) {
		// Nel caso si voglia prendere i dati di un'unica agenzia
		$agenzia = formatta_salvataggio($_POST['agenzia']);
		
		// Per eliminare alcune camere dai conteggi
		if($_POST['cam_escluse'] != '')	$cam_escluse = explode(',', str_replace(' ', '', $_POST['cam_escluse']));
		else 										$cam_escluse = NULL;
		
		// Per tenere conto solo di alcune camere nei conteggi
		if($_POST['cam_incluse'] != '')	$cam_incluse = explode(',', str_replace(' ', '', $_POST['cam_incluse']));
		else 										$cam_incluse = NULL;
		
		// Prendiamo la scelta per il tipo di arrangiamento da controllare
		$arrangiamento = formatta_salvataggio($_POST['arrangiamento']);
		
	}
	else {
		$agenzia = '';
		$arrangiamento = 'TUTTI';
		$cam_escluse = NULL;
		$cam_incluse = NULL;
	}
	
	// Prendiamo i parametri generici
	if(isset($_POST['show_data'])) $show_data = TRUE; else $show_data = FALSE;
	
	// Prendiamo i parametri generici
	if(isset($_POST['show_pax'])) $show_pax = TRUE; else $show_pax = FALSE;
	
	// Controlliamo la validità delle date
	if($data_scelta == NULL) {
		$data_scelta = $_SESSION['oggi'];
		$data_scelta_ultima = strtotime('+30 day', $data_scelta);
	}
	elseif($data_scelta_ultima == NULL || $data_scelta_ultima <= $data_scelta) $data_scelta_ultima = strtotime('+30 day', $data_scelta);
	
	// Recuperiamo le prenotazioni ordinandole per data
	$prenotazioni_da_prendere = 'WHERE ((prenotazioni.data_arrivo>='.$data_scelta.' AND prenotazioni.data_arrivo<='.$data_scelta_ultima.') || '// Se l'arrivo è nel periodo
											.'(prenotazioni.data_partenza>=' . $data_scelta . ' AND prenotazioni.data_partenza<=' . $data_scelta_ultima.') || '// Se la partenza è nel periodo
											.'(prenotazioni.data_partenza>='.$data_scelta_ultima.' AND prenotazioni.data_arrivo<='.$data_scelta.'))'; // Se la prenotazione ingloba il periodo
	
	// Se dobbiamo recuperare o escludere solo alcune camere
	if($cam_escluse != NULL) {
		$num_cam_escluse = count($cam_escluse);
		
		$prenotazioni_da_prendere .= ' AND (';
		
		for($i = 0 ; $i < $num_cam_escluse ; $i++) {
			if($i != 0) $prenotazioni_da_prendere .= ' AND ';
			$prenotazioni_da_prendere .= 'camera != ' . $cam_escluse[$i];
		}
		
		$prenotazioni_da_prendere .= ')';
	}
	
	
	elseif($cam_incluse != NULL) {
		$num_cam_incluse = count($cam_incluse);
		
		$prenotazioni_da_prendere .= ' AND (';
		
		for($i = 0 ; $i < $num_cam_incluse ; $i++) {
			if($i != 0) $prenotazioni_da_prendere .= ' OR ';
			$prenotazioni_da_prendere .= 'camera = ' . $cam_incluse[$i];
		}
		
		$prenotazioni_da_prendere .= ')';
	}
	

	// Se le statistiche sono per una specifica agenzia
	if($agenzia != '') $prenotazioni_da_prendere .= ' AND prenotazioni.agenzia=\''.$agenzia.'\'';
	
	// Se le statistiche sono per uno specifico arrangiamento
	if($arrangiamento != 'TUTTI') $prenotazioni_da_prendere .= ' AND prenotazioni.arrangiamento=\''.$arrangiamento.'\'';

	$db = db_connect();
	$prenotazioni_grezze = $db->query('SELECT prenotazioni.id_rif,prenotazioni.tipo_pre,prenotazioni.stile_spe,prenotazioni.data_arrivo,prenotazioni.data_partenza,prenotazioni.pax, '//
								.'prenotazioni.gruppo,prenotazioni.agenzia,prenotazioni.arrangiamento,prenotazioni.primo_pasto,prenotazioni.ultimo_pasto,'//
								.'gruppi.totale_camere AS camere_gruppo FROM prenotazioni LEFT JOIN gruppi ON prenotazioni.gruppo=gruppi.id '//
								.$prenotazioni_da_prendere . ' ORDER BY prenotazioni.data_arrivo, gruppo, agenzia');
	$db->connection = NULL;
	
	$pre = $prenotazioni_grezze->fetchAll(PDO::FETCH_ASSOC);
	$num_pre = count($pre);
	$pre_rimaste = $num_pre;
	
	
	// Se esistono effettivamente delle prenotazioni per il periodo indicato
	if($num_pre > 0) {
		// Recuperiamo le scelte dei dati statistici
		if(isset($_POST['presenze']))		 $presenze = TRUE; else $presenze = FALSE;
		if(isset($_POST['occupazione']))  $occupazione = TRUE; else $occupazione = FALSE;
		if(isset($_POST['cucina']))		 $cucina = TRUE; else $cucina = FALSE;
		
		// Prendiamo la prima data utile e la parametriamo
		$info_day[0]['timestamp'] = $pre[0]['data_arrivo'];
		$prima_data = &$info_day[0]['timestamp'];
		// Se la prima data è inferiore alla data scelta riveniamo alla data scelta
		if($prima_data < $data_scelta) $prima_data = $data_scelta;
		
		
		$info_day[0]['mese']		 = date('n', $info_day[0]['timestamp']);
		// Se il mese è febbraio si controlla la bisestilità
		if($info_day[0]['mese'] == 2) {
			if(date("L", $info_day[0]['timestamp'])) 	$giorni_nel_mese[1] = 29;
			else 													$giorni_nel_mese[1] = 28;
		}
		$info_day[0]['day_mese'] = date('j', $info_day[0]['timestamp']);
		$info_day[0]['day_week'] = date('w', $info_day[0]['timestamp']);
		
		$info_mese[0]['mese'] = &$info_day[0]['mese'];
		$info_mese[0]['giorni_mese'] = 1;
		if($presenze == TRUE) $info_mese[0]['presenze'] = 0;
		$num_mesi = 1;
		
		if($presenze == TRUE) {
			$info_day[0]['arrivi_cam'] = 0;		  $info_day[0]['arrivi_pax'] = 0;
			$info_day[0]['partenze_cam'] = 0;	  $info_day[0]['partenze_pax'] = 0;
			$info_day[0]['in_casa_cam'] = 0;		  $info_day[0]['in_casa_pax'] = 0;
			$info_day[0]['presenze_tot_cam'] = 0; $info_day[0]['presenze_tot_pax'] = 0;
		}
		if($occupazione == TRUE) {
			// Da fare
		}
		if($cucina == TRUE) {
			$info_day[0]['b_cam'] = 0; $info_day[0]['b_pax'] = 0;
			$info_day[0]['l_cam'] = 0; $info_day[0]['l_pax'] = 0;
			$info_day[0]['d_cam'] = 0; $info_day[0]['d_pax'] = 0;
		}
		
		// Prendiamo l'ultima data utile
		for($i = 0, $ultima_data = 0 ; $i < $num_pre ; $i++) {
			if($ultima_data < $pre[$i]['data_partenza']) $ultima_data = $pre[$i]['data_partenza'];
		}
		// Se l'ultima data è superiore alla data scelta come ultima ritorniamo a quella scelta
		if($ultima_data > $data_scelta_ultima) $ultima_data = $data_scelta_ultima;
		
		// Creamo l'array con le date e le informazioni
		$num_col = 0;
		while(1) {
			
			// Si inseriscono i dati statistici nel giorno
			for($i = 0, $pre_el = FALSE, $data_corrente = &$info_day[$num_col]['timestamp'] ; $i < $pre_rimaste ; $i++) {
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
				
				// Se si è superata la data interessata ci si ferma
				if($pre[$i]['data_arrivo'] > $data_corrente) break;
				
				// Se la data di arrivo è prima della data corrente
				if($pre[$i]['data_arrivo'] < $data_corrente) {
					// Se la prenotazione è in casa
					if($pre[$i]['data_partenza'] > $data_corrente) {
						if($presenze == TRUE) {
							// Se si tratta di uno share non si aggiorna il numero di camere
							if(!$is_share)
								$info_day[$num_col]['in_casa_cam']++;
							
							$info_day[$num_col]['in_casa_pax'] += $pre[$i]['pax'];
							$info_mese[$num_mesi-1]['presenze'] += $pre[$i]['pax'];
						}
						if($cucina == TRUE) {
							if($pre[$i]['arrangiamento'] == 'BB') {
								if(!$is_share) $info_day[$num_col]['b_cam']++;	$info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
							}
							elseif($pre[$i]['arrangiamento'] == 'HB') {
								if(!$is_share) $info_day[$num_col]['b_cam']++;	$info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
								if(!$is_share) $info_day[$num_col]['d_cam']++;	$info_day[$num_col]['d_pax'] += $pre[$i]['pax'];
							}
							elseif($pre[$i]['arrangiamento'] == 'FB') {
								if(!$is_share) $info_day[$num_col]['b_cam']++;	$info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
								if(!$is_share) $info_day[$num_col]['l_cam']++;	$info_day[$num_col]['l_pax'] += $pre[$i]['pax'];
								if(!$is_share) $info_day[$num_col]['d_cam']++;	$info_day[$num_col]['d_pax'] += $pre[$i]['pax'];
							}
						}
					}
					// Se la prenotazione è in partenza
					elseif($pre[$i]['data_partenza'] == $data_corrente) {
						if($presenze == TRUE) {
							// Se è uno share si aggiornano solo i pax in partenza
							if($is_share) {
								$info_day[$num_col]['partenze_pax'] += $pre[$i]['pax'];
							}
							// Se è una principale o una camera aggiuntiva o un cambio camera non in partenza (per evitare conteggio doppio sul cambio camera)
							elseif($frecce_cam == 0) {
								$info_day[$num_col]['partenze_cam']++;
								$info_day[$num_col]['partenze_pax'] += $pre[$i]['pax'];
							}
							elseif($frecce_cam == 1) {
								$info_day[$num_col]['partenze_cam']++;
								$info_day[$num_col]['partenze_pax'] += $pre[$i]['pax'];
							}
							
						}
						if($cucina == TRUE) {
							// Se si tratta di una partenza vera e propria
							if($frecce_cam != 2 && $frecce_cam != 3) {
								if($pre[$i]['arrangiamento'] == 'BB') {
									if(!$is_share) $info_day[$num_col]['b_cam']++; $info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
								}
								elseif($pre[$i]['arrangiamento'] == 'HB') {
									if(!$is_share) $info_day[$num_col]['b_cam']++; $info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
									
									if($pre[$i]['ultimo_pasto'] == 3) {
										if(!$is_share) $info_day[$num_col]['d_cam']++; $info_day[$num_col]['d_pax'] += $pre[$i]['pax'];
									}
								}
								elseif($pre[$i]['arrangiamento'] == 'FB') {
									if($pre[$i]['ultimo_pasto'] == 1) {
										if(!$is_share) $info_day[$num_col]['b_cam']++; $info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
									}
									elseif($pre[$i]['ultimo_pasto'] == 2) {
										if(!$is_share) $info_day[$num_col]['b_cam']++; $info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
										if(!$is_share) $info_day[$num_col]['l_cam']++; $info_day[$num_col]['l_pax'] += $pre[$i]['pax'];
									}
									else {
										if(!$is_share) $info_day[$num_col]['b_cam']++; $info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
										if(!$is_share) $info_day[$num_col]['l_cam']++; $info_day[$num_col]['l_pax'] += $pre[$i]['pax'];
										if(!$is_share) $info_day[$num_col]['d_cam']++; $info_day[$num_col]['d_pax'] += $pre[$i]['pax'];
									}
								}
							}
							// Altrimenti al posto della partenza lo segnamo in casa
							else {
								if($pre[$i]['arrangiamento'] == 'BB') {
									if(!$is_share) $info_day[$num_col]['b_cam']++;	$info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
								}
								elseif($pre[$i]['arrangiamento'] == 'HB') {
									if(!$is_share) $info_day[$num_col]['b_cam']++;	$info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
									if(!$is_share) $info_day[$num_col]['d_cam']++;	$info_day[$num_col]['d_pax'] += $pre[$i]['pax'];
								}
								elseif($pre[$i]['arrangiamento'] == 'FB') {
									if(!$is_share) $info_day[$num_col]['b_cam']++;	$info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
									if(!$is_share) $info_day[$num_col]['l_cam']++;	$info_day[$num_col]['l_pax'] += $pre[$i]['pax'];
									if(!$is_share) $info_day[$num_col]['d_cam']++;	$info_day[$num_col]['d_pax'] += $pre[$i]['pax'];
								}
							}
						}
					}
					// Se la prenotazione va eliminata perché passata
					else {
						unset($pre[$i]); // Eliminiamo la camera na
						$pre_el = TRUE;
					}
				}
				// Se la prenotazione è in arrivo
				else {
					if($presenze == TRUE) {
						// Se è un cambio camera con freccia a sinistra e non un arrivo
						if($is_cambio_cam && ($frecce_cam == 1 || $frecce_cam == 3)) {
							$info_day[$num_col]['in_casa_cam']++;
							$info_day[$num_col]['in_casa_pax'] += $pre[$i]['pax'];
						}
						// Se è uno share si aggiorna solo il pax
						elseif($is_share) {
							$info_day[$num_col]['arrivi_pax'] += $pre[$i]['pax'];
						}
						// Se è un arrivo vero e proprio
						else {
							$info_day[$num_col]['arrivi_cam']++;
							$info_day[$num_col]['arrivi_pax'] += $pre[$i]['pax'];
						}
						
						$info_mese[$num_mesi-1]['presenze'] += $pre[$i]['pax'];
					}
					if($cucina == TRUE) {
						// Se si tratta di un arrivo vero e proprio
						if($frecce_cam != 1 && $frecce_cam != 3) {
							if($pre[$i]['arrangiamento'] == 'BB' && $pre[$i]['primo_pasto'] == 1) {
								if(!$is_share) $info_day[$num_col]['b_cam']++; $info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
							}
							elseif($pre[$i]['arrangiamento'] == 'HB') {
								if($pre[$i]['primo_pasto'] == 1) { if(!$is_share) $info_day[$num_col]['b_cam']++; $info_day[$num_col]['b_pax'] += $pre[$i]['pax']; }
								else 										{ if(!$is_share) $info_day[$num_col]['d_cam']++; $info_day[$num_col]['d_pax'] += $pre[$i]['pax']; }
							}
							elseif($pre[$i]['arrangiamento'] == 'FB') {
								if($pre[$i]['primo_pasto'] == 1) {
									if(!$is_share) $info_day[$num_col]['b_cam']++; $info_day[$num_col]['b_pax'] += $pre[$i]['pax'];
									if(!$is_share) $info_day[$num_col]['l_cam']++; $info_day[$num_col]['l_pax'] += $pre[$i]['pax'];
									if(!$is_share) $info_day[$num_col]['d_cam']++; $info_day[$num_col]['d_pax'] += $pre[$i]['pax'];
								}
								elseif($pre[$i]['primo_pasto'] == 2) {
									if(!$is_share) $info_day[$num_col]['l_cam']++; $info_day[$num_col]['l_pax'] += $pre[$i]['pax'];
									if(!$is_share) $info_day[$num_col]['d_cam']++; $info_day[$num_col]['d_pax'] += $pre[$i]['pax'];
								}
								else {
									if(!$is_share) $info_day[$num_col]['d_cam']++; $info_day[$num_col]['d_pax'] += $pre[$i]['pax'];
								}
							}
						}
					}
				}
			}
		
			// Se le prenotazioni vanno riordinate
			if($pre_el == TRUE) {
				$pre = array_values($pre);
				$pre_rimaste = count($pre);
			}
			
			// Calcoliamo le presenze totali del giorno
			if($presenze == TRUE) {
				$info_day[$num_col]['presenze_tot_cam'] = $info_day[$num_col]['arrivi_cam'] + $info_day[$num_col]['in_casa_cam'];
				$info_day[$num_col]['presenze_tot_pax'] = $info_day[$num_col]['arrivi_pax'] + $info_day[$num_col]['in_casa_pax'];
			}
			
			// Si settano i parametri per il giorno successivo
			$num_col++;
			$info_day[$num_col]['timestamp'] = strtotime('+1 day', $info_day[$num_col - 1]['timestamp']);
			
			// Se non siamo arrivati alla fine
			if($info_day[$num_col]['timestamp'] <= $ultima_data) {

				// Se si Sta cambiando Settimana
				if($info_day[$num_col-1]['day_week'] == 6) $info_day[$num_col]['day_week'] = 0;
				else 													 $info_day[$num_col]['day_week'] = $info_day[$num_col-1]['day_week'] + 1;
				
				// Se si sta cambiando mese
				if($info_day[$num_col - 1]['day_mese'] == $giorni_nel_mese[$info_day[$num_col - 1]['mese'] - 1]) {
					
					$info_day[$num_col]['day_mese'] = 1; // Giorno mese
					
					// Se bisogna cambiare anno
					if($info_day[$num_col - 1]['mese'] == 12) {
						$info_day[$num_col]['mese'] = 1; // Si riparte da gennaio
					}
					else {
						$info_day[$num_col]['mese'] = $info_day[$num_col - 1]['mese'] + 1; // Si aggiunge un mese
						
						// Se il mese è febbraio si controlla la bisestilità
						if($info_day[$num_col]['mese'] == 2) {
							if(date("L", $info_day[$num_col]['timestamp'])) $giorni_nel_mese[1] = 29;
							else 															$giorni_nel_mese[1] = 28;
						}
					}
						
					// Inizializziamo i valori statistici per il mese
					$info_mese[$num_mesi]['mese'] = &$info_day[$num_col]['mese'];
					$info_mese[$num_mesi]['giorni_mese'] = 1;
					if($presenze == TRUE) $info_mese[$num_mesi]['presenze'] = 0;
					$num_mesi++;
				}
				else {
					$info_day[$num_col]['day_mese'] = $info_day[$num_col-1]['day_mese'] + 1; // Si aggiunge un giorno al giorno mese
					$info_day[$num_col]['mese'] = &$info_day[$num_col-1]['mese']; // Si rimane sul mese corrente
					
					$info_mese[$num_mesi-1]['giorni_mese']++;
				}
				
				// Inizializiamo i parametri statistici
				if($presenze == TRUE) {
					$info_day[$num_col]['arrivi_cam'] = 0;		 	$info_day[$num_col]['arrivi_pax'] = 0;
					$info_day[$num_col]['partenze_cam'] = 0;	 	$info_day[$num_col]['partenze_pax'] = 0;
					$info_day[$num_col]['in_casa_cam'] = 0;		$info_day[$num_col]['in_casa_pax'] = 0;
					$info_day[$num_col]['presenze_tot_cam'] = 0; $info_day[$num_col]['presenze_tot_pax'] = 0;
				}
				if($occupazione == TRUE) {
					// Da fare
				}
				if($cucina == TRUE) {
					$info_day[$num_col]['b_cam'] = 0; $info_day[$num_col]['b_pax'] = 0;
					$info_day[$num_col]['l_cam'] = 0; $info_day[$num_col]['l_pax'] = 0;
					$info_day[$num_col]['d_cam'] = 0; $info_day[$num_col]['d_pax'] = 0;
				}
			}
			else {
				break;
			}
		
		}
	}

	else {
		$prima_data = $data_scelta;
		$ultima_data = $data_scelta_ultima;
	}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head>
	<title>STATISTICHE</title><?php
	header_rl(); 
?><style media="print" type="text/css">
@page {size: A4 landscape;}
</style>
</head>
<body><?php
		// Stampiamo il menu
		echo '<div class="no_print">';
		echo '<form name="dati" action="statistiche.php" method="post" enctype="multipart/form-data">';
		echo '<a href="menu.php">MENU</a> | ';
		echo ' DAL ';
		echo '<input type="text" name="data_arrivo" class="data_menu_print" value="'.date('d/m/y', $data_scelta).'" />';
		echo ' AL ';
		echo '<input type="text" name="data_partenza" class="data_menu_print" value="'.date('d/m/y', $data_scelta_ultima).'" />';
		echo ' AGENZIA ';
		echo '<input type="text" name="agenzia" class="data_menu_print" value="'.formatta_visualizzazione($agenzia).'" />';
		echo ' | ';
		
		echo '<select name="arrangiamento">';
		echo '<option '; if($arrangiamento == 'TUTTI') echo 'selected="selected" '; echo 'value="TUTTI">TUTTI</option>';
		echo '<option '; if($arrangiamento == 'RS') echo 'selected="RS" '; echo 'value="RS">RS</option>';
		echo '<option '; if($arrangiamento == 'BB') echo 'selected="selected" '; echo 'value="BB">BB</option>';
		echo '<option '; if($arrangiamento == 'HB') echo 'selected="selected" '; echo 'value="HB">HB</option>';
		echo '<option '; if($arrangiamento == 'FB') echo 'selected="selected" '; echo 'value="FB">FB</option>';
		echo '</select>';
		echo ' | ';
		
		
		echo ' VISUALIZZA: ';
		echo '<span><input type="checkbox" name="show_pax" id="show_pax" value="1"';
		if($show_pax == TRUE) echo ' checked="checked"';
		echo ' /> <label for="show_pax"> PAX</label></<span> ';
		echo '<span><input type="checkbox" name="presenze" id="presenze" value="1"';
		if($presenze == TRUE) echo ' checked="checked"';
		echo ' /> <label for="presenze"> PRESENZE</label></<span> ';
		echo '<span><input type="checkbox" name="occupazione" id="occupazione" value="1"';
		if($occupazione == TRUE) echo ' checked="checked"';
		echo ' /> <label for="occupazione"> OCCUPAZIONE</label></<span> ';
		echo '<span><input type="checkbox" name="cucina" id="cucina" value="1"';
		if($cucina == TRUE) echo ' checked="checked"';
		echo ' /> <label for="cucina"> CUCINA</label></<span> ';
		echo '<span><input type="checkbox" name="show_data" id="show_data" value="1"';
		if($show_data == TRUE) echo ' checked="checked"';
		echo ' /> <label for="show_data"> DATA DI STAMPA</label></<span> ';
		
		
		echo '<div class="escludi_cam">';
		echo 'ESCLUDI CAMERE  ';
		echo '<input type="text" name="cam_escluse" class="data_menu_print" value="';
		
		if($cam_escluse != NULL) {
			for($i = 0 ; $i < $num_cam_escluse ; $i++) {
				if($i != 0) echo ',';
				echo $cam_escluse[$i];
			}
		}
		
		echo '" />';
		echo ' | OPPURE INCLUDI UNICAMENTE CAMERE  ';
		echo '<input type="text" name="cam_incluse" class="data_menu_print" value="';
		
		if($cam_incluse != NULL) {
			for($i = 0 ; $i < $num_cam_incluse ; $i++) {
				if($i != 0) echo ',';
				echo $cam_incluse[$i];
			}
		}
		
		echo '" />';
		echo '</div>';
		
		echo '<input class="bottone" name="aggiorna" type="submit" value="AGGIORNA" />';
		echo '</form>';
		echo '</div>';
		
		// Stampiamo la testata
		echo '<div class="menu">';
		echo '<span class="nome_struttura">'.$_SESSION['nome_struttura'].'</span>';
		if($show_data) echo '<span class="data_stampa">GENERATO IL '.date('d/m/y \A\L\L\E H:i', $data).'</span>';
		
		echo '</div>';
		
		// Stampiamo la testata delle statistiche
		echo '<div class="testa_stat">STATISTICHE';
		if($agenzia != '') echo ' PER '.formatta_visualizzazione($agenzia);
		
		echo ' DAL '.date('d/m/Y', $prima_data).' AL '.date('d/m/Y', $ultima_data);
		
		if($cam_escluse != NULL) {
			echo ' (CAMERE ESCLUSE: '; 
			for($i = 0 ; $i < $num_cam_escluse ; $i++) {
				if($i != 0) echo ',';
				echo $cam_escluse[$i];
			}
			echo ')';
		}
		
		if($cam_incluse != NULL) {
			echo ' (CAMERE INCLUSE: '; 
			for($i = 0 ; $i < $num_cam_incluse ; $i++) {
				if($i != 0) echo ',';
				echo $cam_incluse[$i];
			}
			echo ')';
		}
		
		echo '<span class="testa_stat_dx">'.$num_col.' NOTTI';
		if($num_pre > 0 && $presenze == TRUE) {
			// Calcoliamo le presenze totali
			for($i = 0, $presenze_tot = 0 ; $i < $num_mesi ; $i++) $presenze_tot += $info_mese[$i]['presenze'];
			echo ' - '.$presenze_tot.' PRESENZE';
			
			if($occupazione == TRUE) {
				echo ', '.(round($presenze_tot/$num_col,2)).' PPN';
			}
		}
		echo '</span></div>';
		
		if($num_pre == 0) {
			echo '<div class="testa_stat">NESSUN DATO TROVATO</div>';
		}
		
		else {
			// Per ogni mese una tabella diversa
			for($i = 0, $giorno = 0 ; $i < $num_mesi ; $i++) {
				// Stampiamo la testata del mese
				echo '<div class="no_breaking">'; // Per evitare di separare il titolo dalla tabella
				echo '<div class="mese_stat">'.$_SESSION['mesi'][$info_mese[$i]['mese']-1] //
					  .' | '.$info_mese[$i]['giorni_mese'].' NOTTI';
				if($presenze == TRUE) {
					echo ' - '.$info_mese[$i]['presenze'].' PRESENZE';
					
					if($occupazione == TRUE) {
						echo ', '.(round($info_mese[$i]['presenze']/$info_mese[$i]['giorni_mese'],2)).' PPN';
					}
				}
				echo '</div>';
				
				echo '<table class="stat">';
				
				// Stampiamo le date
				echo '<tr class="date"><th>DATE</th>';
				for($giorno_mese = 0 ; $giorno_mese < $info_mese[$i]['giorni_mese'] ; $giorno_mese++) {
					// Se siamo di sabato o di domenica
					if($info_day[$giorno]['day_week'] == 0 || $info_day[$giorno]['day_week'] == 6) echo '<th class="we">';
					else 																									 echo '<th>';
					
					echo $_SESSION['giorni'][$info_day[$giorno]['day_week']] . '<br />'//
						  .$info_day[$giorno]['day_mese'].'</th>';
					
					$giorno++; // Facciamo avanzare il puntatore
				}
				echo '</tr>';
				
				// Stampiamo le righe delle presenze
				if($presenze == TRUE) {
					$righe_values = array('arrivi_cam', 'arrivi_pax', 'partenze_cam', 'partenze_pax', 'in_casa_cam', 'in_casa_pax', 'presenze_tot_cam', 'presenze_tot_pax');
					$righe_names = array('ARRIVI CAM', 'ARRIVI PAX', 'PARTENZE CAM', 'PARTENZE PAX', 'IN CASA CAM', 'IN CASA PAX', 'PRESENZE CAM', 'PRESENZE PAX');
					$num_righe = count($righe_values);
					
					for($j = 0 ; $j < $num_righe ; $j++) {
						if($show_pax == FALSE && $j % 2 != 0) continue;
						
						$giorno -= $info_mese[$i]['giorni_mese']; // Riportiamo il puntatore a inizio mese
						
						echo '<tr';
						// Se ci vuole la riga scura o il bordo doppio per delimitare la fine di un blocco statistico
						if($j % 2 != 0 || $j + 1 == $num_righe) {
							echo ' class="';
							if($j % 2 != 0) {
								if($j + 1 == $num_righe) echo 'riga_scura fine_blocco';
								else 							 echo 'riga_scura';
							}
							elseif($j + 1 == $num_righe) echo 'fine_blocco';
							echo '"';
						}
						echo '>';
						
						echo '<td class="nome_riga_stat">'.$righe_names[$j].'</td>';
						
						for($giorno_mese = 0 ; $giorno_mese < $info_mese[$i]['giorni_mese'] ; $giorno_mese++) {
							if($info_day[$giorno]['day_week'] == 5 || $info_day[$giorno]['day_week'] == 0)
									echo '<td class="bordo_we">';
							else  echo '<td>';
							
							echo $info_day[$giorno][$righe_values[$j]].'</td>';
							
							$giorno++; // Facciamo avanzare il puntatore
						}
						echo '</tr>';
					}
				}
				
				if($cucina == TRUE) {
					$righe_values = array('b_cam', 'b_pax', 'l_cam', 'l_pax', 'd_cam', 'd_pax');
					$righe_names = array('COLAZIONE CAM', 'COLAZIONE PAX', 'PRANZO CAM', 'PRANZO PAX', 'CENA CAM', 'CENA PAX');
					$num_righe = count($righe_values);
					
					for($j = 0 ; $j < $num_righe ; $j++) {
						$giorno -= $info_mese[$i]['giorni_mese']; // Riportiamo il puntatore a inizio mese
						
						echo '<tr';
						// Se ci vuole la riga scura o il bordo doppio per delimitare la fine di un blocco statistico
						if($j % 2 != 0 || $j + 1 == $num_righe) {
							echo ' class="';
							if($j % 2 != 0) {
								if($j + 1 == $num_righe) echo 'riga_scura fine_blocco';
								else 							 echo 'riga_scura';
							}
							elseif($j + 1 == $num_righe) echo 'fine_blocco';
							echo '"';
						}
						echo '>';
						
						echo '<td class="nome_riga_stat">'.$righe_names[$j].'</td>';
						
						for($giorno_mese = 0 ; $giorno_mese < $info_mese[$i]['giorni_mese'] ; $giorno_mese++) {
							if($info_day[$giorno]['day_week'] == 5 || $info_day[$giorno]['day_week'] == 0)
									echo '<td class="bordo_we">';
							else  echo '<td>';
							
							echo $info_day[$giorno][$righe_values[$j]].'</td>';
							
							$giorno++; // Facciamo avanzare il puntatore
						}
						echo '</tr>';
					}
				}
				
				
				// Chiudiamo la tabella del mese
				echo '</table>';
				echo '</div>'; // chiudiamo no_breaking
			}
		}
		
	?></body>
</html><?php
} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>