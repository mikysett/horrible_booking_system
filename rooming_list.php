<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore
	$data = time();
	$arrivi_diversi = FALSE;
	
	$ordine = 'camera';
	$show_cam = TRUE;
	$show_data = TRUE;
	$show_riepilogo = TRUE;
	$show_occ = TRUE;
	$show_nome = TRUE;
	$show_note = TRUE;
	$show_pax = TRUE;

	if(isset($_GET['id_gruppo']) || isset($_POST['id_gruppo'])) {
		if(isset($_GET['id_gruppo']))		 $id_gruppo = intval($_GET['id_gruppo']);
		
		elseif(isset($_POST['id_gruppo'])) {
			$id_gruppo = intval($_POST['id_gruppo']);
			$ordine = $_POST['ordine'];
			if(isset($_POST['show_cam']))			$show_cam = TRUE;			 else $show_cam = FALSE;
			if(isset($_POST['show_data']))		$show_data = TRUE;		 else $show_data = FALSE;
			if(isset($_POST['show_riepilogo'])) $show_riepilogo = TRUE;	 else $show_riepilogo = FALSE;
			if(isset($_POST['show_occ']))			$show_occ = TRUE;			 else $show_occ = FALSE;
			if(isset($_POST['show_nome']))		$show_nome = TRUE;		 else $show_nome = FALSE;
			if(isset($_POST['show_note']))		$show_note = TRUE;		 else $show_note = FALSE;
			if(isset($_POST['show_pax']))			$show_pax = TRUE;			 else $show_pax = FALSE;
		}

		$db = db_connect();
		// Prendiamo i dati delle principali
		$dati_pre = $db->query('SELECT * FROM prenotazioni WHERE id_rif=0 AND gruppo='.$id_gruppo.' ORDER BY '.$ordine);
		// Prendiamo i dati delle speciali e li ordiniamo per tipo_pre per avere prima multicam poi cambio cam e infine share
		$dati_spe = $db->query('SELECT * FROM prenotazioni WHERE id_rif>0 AND gruppo=' . $id_gruppo.' ORDER BY tipo_pre, data_arrivo');
		$dati_gru = $db->query('SELECT * FROM gruppi WHERE id='.$id_gruppo);
		$db->connection = NULL;
		
		$pre = $dati_pre->fetchAll(PDO::FETCH_ASSOC);
		$spe = $dati_spe->fetchAll(PDO::FETCH_ASSOC);
		$gru = $dati_gru->fetch(PDO::FETCH_ASSOC);
		
		$num_pre = count($pre);
		$num_spe = count($spe);
		
		// Controlliamo qual'è l'arrangiamento che va per la maggiore e se ci sono arrivi o partenze diversi
		for($i = 0, $arr_bb = 0, $arr_hb = 0, $arr_rs = 0, $arr_fb = 0 ; $i < $num_pre ; $i++) {
			if($pre[$i]['arrangiamento'] == 'BB')	   $arr_bb++;
			elseif($pre[$i]['arrangiamento'] == 'HB') $arr_hb++;
			elseif($pre[$i]['arrangiamento'] == 'FB') $arr_fb++;
			elseif($pre[$i]['arrangiamento'] == 'Rs') $arr_rs++;
			
			if($pre[$i]['data_arrivo'] != $gru['data_arrivo'] || $pre[$i]['data_partenza'] != $gru['data_partenza']) $arrivi_diversi = TRUE;
		}
		if($arr_bb >= $arr_hb && $arr_bb >= $arr_fb && $arr_bb >= $arr_rs)	  $arr_gru = 'BB';
		elseif($arr_hb >= $arr_bb && $arr_hb >= $arr_fb && $arr_hb >= $arr_rs) $arr_gru = 'HB';
		elseif($arr_rs >= $arr_hb && $arr_rs >= $arr_fb && $arr_rs >= $arr_bb) $arr_gru = 'RS';
		else 																						  $arr_gru = 'FB';
		
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head><?php
	echo '<title>'.formatta_visualizzazione($gru['nome']).' - ROOMING LIST</title>';
	 header_rl(); ?>
</head>
<body><?php
		// Stampiamo il menu
		echo '<div class="no_print">';
		echo '<form name="dati" action="rooming_list.php" method="post" enctype="multipart/form-data">';
		echo '<a href="menu.php">MENU</a> | <a href="ge_gru.php?mg='.$id_gruppo.'">GESTIONE GRUPPO</a> ';
		echo 'ORDINA PER: ';
		if($ordine == 'nome') {
			echo '<span><input type="radio" name="ordine" id="r1" value="nome" checked="checked" /> <label for="r1"> NOME</label></span> ';
  			echo '<span><input type="radio" name="ordine" id="r2" value="camera" /> <label for="r2"> CAMERA</label></span> ';
  			echo '<span><input type="radio" name="ordine" id="r3" value="pax" /> <label for="r3"> PAX</label></span>';
		}
		elseif($ordine == 'camera') {
			echo '<span><input type="radio" name="ordine" id="r1" value="nome" /> <label for="r1"> NOME</label></span> ';
  			echo '<span><input type="radio" name="ordine" id="r2" value="camera" checked="checked" /> <label for="r2"> CAMERA</label></span> ';
  			echo '<span><input type="radio" name="ordine" id="r3" value="pax" /> <label for="r3"> PAX</label></span>';
		}
		elseif($ordine == 'pax') {
			echo '<span><input type="radio" name="ordine" id="r1" value="nome" /> <label for="r1"> NOME</label></span> ';
  			echo '<span><input type="radio" name="ordine" id="r2" value="camera" /> <label for="r2"> CAMERA</label></span> ';
  			echo '<span><input type="radio" name="ordine" id="r3" value="pax" checked="checked" /> <label for="r3"> PAX</label></span>';
		}
		echo ' | VISUALIZZA: ';
		echo '<span><input type="checkbox" name="show_cam" id="show_cam" value="1"';
		if($show_cam == TRUE) echo ' checked="checked"';
		echo ' /> <label for="show_cam"> CAMERE</label></<span> ';
		echo '<span><input type="checkbox" name="show_data" id="show_data" value="1"';
		if($show_data == TRUE) echo ' checked="checked"';
		echo ' /> <label for="show_data"> DATA DI STAMPA</label></<span> ';
		echo '<span><input type="checkbox" name="show_riepilogo" id="show_riepilogo" value="1"';
		if($show_riepilogo == TRUE) echo ' checked="checked"';
		echo ' /> <label for="show_riepilogo"> RIEPILOGO</label></<span> ';
		echo '<span><input type="checkbox" name="show_occ" id="show_occ" value="1"';
		if($show_occ == TRUE) echo ' checked="checked"';
		echo ' /> <label for="show_occ"> OCCUPAZIONE</label></<span> ';
		echo '<span><input type="checkbox" name="show_nome" id="show_nome" value="1"';
		if($show_nome == TRUE) echo ' checked="checked"';
		echo ' /> <label for="show_nome"> NOME</label></<span> ';
		echo '<span><input type="checkbox" name="show_note" id="show_note" value="1"';
		if($show_note == TRUE) echo ' checked="checked"';
		echo ' /> <label for="show_note"> NOTE</label></<span> ';
		echo '<span><input type="checkbox" name="show_pax" id="show_pax" value="1"';
		if($show_pax == TRUE) echo ' checked="checked"';
		echo ' /> <label for="show_pax"> PAX</label></<span> ';
		
		echo '<input class="bottone" name="aggiorna" type="submit" value="AGGIORNA" />';
		echo '<input type="hidden" name="id_gruppo" value="'.$id_gruppo.'" />';
		echo '</form>';
		echo '</div>'; // fine . no_print
		
		
		// Stampiamo la testata
		echo '<div class="menu">';
		echo '<span class="nome_struttura">'.$_SESSION['nome_struttura'].'</span>';
		if($show_data == TRUE) echo '<span class="data_stampa">GENERATO IL '.date('d/m/y \A\L\L\E H:i', $data).'</span>';
		echo '</div>'; // fine .menu
		
		// Stampiamo i dati generici del gruppo
		echo '<div class="nome_gruppo">'.formatta_visualizzazione($gru['nome']).'</div>'//
		.' <div class="dati_gruppo">DAL '.date('d/m', $gru['data_arrivo']).' al '.date('d/m', $gru['data_partenza']);
		if($show_pax == TRUE) echo ', '.$gru['totale_camere'].' CAMERE, '.$gru['totale_pax'].' PAX';
		if($show_occ == TRUE) echo ', '.round($gru['totale_pax']/$gru['totale_camere'], 2).' PPC';
		echo '<span class="arrangiamento_gruppo">ARRANGIAMENTO: '.$arr_gru.'</span></div>';
		
		if($show_riepilogo == TRUE) echo '<div class="riepilogo_gruppo">RIEPILOGO: '.formatta_visualizzazione($gru['riepilogo']).'</div>';
		
		echo '<div class="testa_rl">ROOMING LIST</div>';
		
		// Stampiamo i dati delle prenotazioni
		echo '<table class="rooming_list">'//
		 	  .'<thead><tr></tr>'//
		 	  .'<tr><th class="small_td prog">#</th>'//
			  .'<th class="medium_td">ARR</th><th class="medium_td">PAR</th>';
			  if($show_cam == TRUE) echo '<th class="medium_td">CAM</th>';
			  if($show_nome == TRUE) echo '<th>NOME</th>';
		 	  echo '<th class="small_td">PAX</th><th class="medium_td">VEST</th><th class="small_td">A</th>';
		 	  if($show_note == TRUE) echo '<th>NOTE</th>';
		 	  echo '</tr></thead><tbody>';
		
		for($i = 0 ; $i < $num_pre ; $i++) {
			$num_spe_found = 0; // Reinizializziamo il numero di speciali correlate
			$num_multicam = 0;
			$num_cambiocam = 0;
			$num_share = 0;
			$rowspan_multicam = '';
			$rowspan_cambiocam = '';
			$rowspan_share = '';
			$rowspan_arr = '';
			$rowspan_note = '';
			$class_bottom = ' tr_bottom_bold';
			
			// Se la prenotazione ha delle speciali collegate
			if($pre[$i]['stile_spe'] > 0) {
				echo '<tr class="tr_left_bold tr_top_bold">'; // Creamo una differenza grafica
				$spe_found = array();
				for($j = 0 ; $j < $num_spe ; $j++) {
					if($spe[$j]['id_rif'] == $pre[$i]['id']) {
						$spe_found[] = &$spe[$j];
						$num_spe_found++;
						
						if($spe[$j]['tipo_pre'] >= 10 && $spe[$j]['tipo_pre'] <= 19) $num_multicam++;
						elseif($spe[$j]['tipo_pre'] >= 20 && $spe[$j]['tipo_pre'] <= 29) $num_cambiocam++;
						elseif($spe[$j]['tipo_pre'] >= 30 && $spe[$j]['tipo_pre'] <= 39) $num_share++;
					}
				}
				if($num_multicam > 0) 														 $rowspan_multicam =' rowspan="'.($num_multicam+1).'" class="tr_bottom_bold"';
				if($num_cambiocam > 0 && $num_multicam == 0) 						 $rowspan_cambiocam =' rowspan="'.($num_cambiocam+1).'" class="tr_bottom_bold"';
				if($num_share > 0 && $num_multicam == 0 && $num_cambiocam == 0) $rowspan_share =' rowspan="'.($num_share+1).'" class="tr_bottom_bold"';
				
				// Per l'arrangiamento
				if($num_multicam > 0 || $num_cambiocam > 0) $rowspan_arr =' rowspan="'.($num_multicam+$num_cambiocam+1).'" class="tr_bottom_bold"';
				// Per le note
				if($num_spe_found > 0)							  $rowspan_note =' rowspan="'.($num_spe_found+1).'"';
			}
			else
				echo '<tr>';

			if($num_spe_found > 0) echo '<td class="prog'.$class_bottom.'"'.$rowspan_note.'><a href="ge_pre.php?mp='.$pre[$i]['id'].'">'.($i+1).'</a></td>';
			else 						  echo '<td class="prog"><a href="ge_pre.php?mp='.$pre[$i]['id'].'">'.($i+1).'</a></td>';
			
			echo '<td'.$rowspan_multicam.'>';
			if($pre[$i]['data_arrivo'] != $gru['data_arrivo'] || $pre[$i]['data_partenza'] != $gru['data_partenza'])
				echo '<b>'.date('d/m', $pre[$i]['data_arrivo']).'</b>';
			else
				echo date('d/m', $pre[$i]['data_arrivo']);
			echo '</td>';
			
			echo '<td'.$rowspan_multicam.'>';
			if($pre[$i]['data_arrivo'] != $gru['data_arrivo'] || $pre[$i]['data_partenza'] != $gru['data_partenza'])
				echo '<b>'.date('d/m', $pre[$i]['data_partenza']).'</b>';
			else
				echo date('d/m', $pre[$i]['data_partenza']);
			echo '</td>';
			
			if($show_cam == TRUE) {
				if($pre[$i]['camera'] == 0) {
					if($rowspan_share != '') echo '<td class="center'.$class_bottom.'" '.$rowspan_share.'><b>?</b></td>';
					else 							 echo '<td class="center"><b>?</b></td>';
					
				}
				else {
					if($rowspan_share != '') echo '<td class="center'.$class_bottom.'" '.$rowspan_share.'>'.$pre[$i]['camera'].'</td>';
					else 							 echo '<td class="center">'.$pre[$i]['camera'].'</td>';
				}
			}
			
			if($show_nome == TRUE) echo '<td'.$rowspan_multicam.'>'.formatta_visualizzazione($pre[$i]['nome']).'</td>';
			
			echo '<td'.$rowspan_cambiocam.'>';
			if($pre[$i]['pax'] != 0) echo $pre[$i]['pax'];
			echo '</td>';
			
			echo '<td'.$rowspan_share.'>'.formatta_visualizzazione($pre[$i]['vestizione']).'</td>';
			
			echo '<td'.$rowspan_arr.'>';
			if($pre[$i]['arrangiamento'] != $arr_gru) echo '<b>'.$pre[$i]['arrangiamento'].'</b>';
			else 													echo $pre[$i]['arrangiamento'];
			echo '</td>';
			
			if($show_note == TRUE) {
				if($rowspan_note != '') echo '<td class="'.$class_bottom.'" '.$rowspan_note.'>'.formatta_visualizzazione($pre[$i]['note']).'</td>';
				else 							echo '<td>'.formatta_visualizzazione($pre[$i]['note']).'</td>';
			}
			
			echo '</tr>';
			
			// Stampiamo le spe
			if($num_spe_found > 0) {
				for($j = 0 ; $j < $num_spe_found ; $j++) {
					if($j + 1 == $num_spe_found) echo '<tr class="tr_left_bold tr_bottom_bold">';
					else 								  echo '<tr class="tr_left_bold">';
					
					// Se si tratta di una multicam
					if($spe_found[$j]['tipo_pre'] >= 10 && $spe_found[$j]['tipo_pre'] <= 19) {
						if($show_cam == TRUE) echo '<td class="center">'.$spe_found[$j]['camera'].'</td>';
						
						echo '<td>'.$spe_found[$j]['pax'].'</td>';
						echo '<td>'.formatta_visualizzazione($spe_found[$j]['vestizione']).'</td>';
					}
					// Se si tratta di un cambio cam
					elseif($spe_found[$j]['tipo_pre'] >= 20 && $spe_found[$j]['tipo_pre'] <= 29) {
						echo '<td><b>'.date('d/m', $spe_found[$j]['data_arrivo']).'</b></td>';
						echo '<td><b>'.date('d/m', $spe_found[$j]['data_partenza']).'</b></td>';
						
						if($show_cam == TRUE) echo '<td class="center">'.$spe_found[$j]['camera'].'</td>';
						
						echo '<td><b>CAMBIO CAMERA</b></td>';
						if($rowspan_cambiocam == '') echo '<td>'.$spe_found[$j]['pax'].'</td>';
						echo '<td>'.formatta_visualizzazione($spe_found[$j]['vestizione']).'</td>';
					}
					// Se si tratta di uno share
					elseif($spe_found[$j]['tipo_pre'] >= 30 && $spe_found[$j]['tipo_pre'] <= 39) {
						echo '<td><b>'.date('d/m', $spe_found[$j]['data_arrivo']).'</b></td>';
						echo '<td><b>'.date('d/m', $spe_found[$j]['data_partenza']).'</b></td>';
						
						if($show_cam == TRUE) if($rowspan_share == '') echo '<td class="center">'.$spe_found[$j]['camera'].'</td>';
						
						if($show_nome == TRUE) echo '<td><b>[SHARE]</b> '.formatta_visualizzazione($spe_found[$j]['nome']).'</td>';
						echo '<td>'.$spe_found[$j]['pax'].'</td>';
						if($rowspan_share == '') echo '<td>'.formatta_visualizzazione($spe_found[$j]['vestizione']).'</td>';
						
						echo '<td>';
						if($spe_found[$j]['arrangiamento'] != $arr_gru) echo '<b>'.$spe_found[$j]['arrangiamento'].'</b>';
						else 															echo $spe_found[$j]['arrangiamento'];
						echo '</td>';
					}
					
					echo '</tr>';
				}
			}
		}
		
		echo '</tbody></table>';
		
	?></body>
</html>
<?php

	}
} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>