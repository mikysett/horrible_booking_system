<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore

	if(isset($_GET['pax'])) $pax = intval($_GET['pax']);
	else 							$pax = 200;
	if(isset($_GET['ogni'])) $ogni = intval($_GET['ogni']);
	else 							 $ogni = 25;
	
	// Valori standard
	$data_arrivo = $_SESSION['oggi'];
	$notti = 1;
	$pax = array(250);
	$pax_all = '250';
	$ogni = 20;
	$nome_gruppo = '';
	$show_data = TRUE;
	$tot_gratis = 0;
	
	$date[0] = $data_arrivo;
	
	// Recuperiamo i valori inseriti
	if(isset($_POST['data_arrivo'])) {
		if(!isset($_POST['show_data'])) $show_data = FALSE;
		$nome_gruppo = formatta_salvataggio($_POST['nome_gruppo']);
		$ogni = intval($_POST['ogni']);
		
		$data_arrivo = controllo_data($_POST['data_arrivo']);
		if($data_arrivo == NULL) $data_arrivo = $_SESSION['oggi'];
		
		$date[0] = $data_arrivo;
		
		$notti = intval($_POST['notti']);
		if($notti < 1) $notti = 1;
		
		$pax_all = str_replace(' ', '', $_POST['pax_all']);
		
		$pax = explode(',', str_replace(array('.', ',', ';', '-', '_'), ',', $pax_all));
		$num_pax = count($pax);
		
		for($i = 0 ; $i < $notti ; $i++) {
			// Si aggiungono le date
			if($i > 0) $date[$i] = strtotime('+1 day', $date[$i-1]);
			
			
			// Se il pax per la notte è stato inserito
			if($i + 1 <= $num_pax) {
				$pax[$i] = intval($pax[$i]);
			}
			// Se il pax per la notte non è stato inserito per la notte si assegna il pax di quella precedente
			else {
				$pax[$i] = $pax[$i-1];
			}
		}
		
	}
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head><?php
	echo '<title>CALCOLO GRATUITA</title>';
	header_gra_cal();
?></head>
<body><?php

		echo '<div class="no_print">';
		echo '<form name="dati" action="gratuita_cal.php" method="post" enctype="multipart/form-data">';
		echo '<a href="menu.php">MENU</a> | ';
		echo ' ARRIVO ';
		echo '<input type="text" name="data_arrivo" class="data_menu_print" value="'.date('d/m/y', $data_arrivo).'" />';
		echo ' NUMERO NOTTI ';
		echo '<input type="text" name="notti" class="data_menu_print" value="'.$notti.'" />';
		echo ' PAX ';
		echo '<input type="text" name="pax_all" class="data_menu_print" value="'.$pax_all.'" />';
		echo ' 1 GRATI OGNI ';
		echo '<input type="text" name="ogni" class="data_menu_print" value="'.$ogni.'" />';
		echo ' NOME GRUPPO ';
		echo '<input type="text" name="nome_gruppo" class="data_menu_print" value="'.formatta_visualizzazione($nome_gruppo).'" />';
		echo '<span><input type="checkbox" name="show_data" id="show_data" value="1"';
		if($show_data == TRUE) echo ' checked="checked"';
		echo ' /> <label for="show_data"> MOSTRA DATA</label></<span>';
		echo '<input class="bottone" name="aggiorna" type="submit" value="AGGIORNA" />';
		echo '</form>';
		echo '</div>';

		if($nome_gruppo != '') echo '<p class="nome_gruppo">'.formatta_visualizzazione($nome_gruppo).'</p>';

		for($j = 0 ; $j < $notti ; $j++) {
			$num_gratis = 0;
			$riga_pax = '';
			$riga_gratis = '';
			
			if($j > 0) echo '<div class="separatore"></div>';
			
			echo '<p class="info_gratis">';
			if($show_data == TRUE) echo '<b>'.date('d/m/Y', $date[$j]).'</b> ';
			echo 'PAX : <b>'.$pax[$j].'</b>, UNO GRATIS OGNI <b>'.$ogni.'</b></p>';
			
			echo '<table class="tab_gratis">';
			for($i = 1, $prog = 0 ; $i <= $pax[$j] ; $i++) {
				if($i != 1 && (($i-1) % 30 == 0)) {
					echo '<tr class="b_top"><th>PAX</th>'.$riga_pax.'</tr>';
					echo '<tr><th>GRATIS</th>'.$riga_gratis.'</tr>';
					$riga_gratis = '';
					$riga_pax = '';
					
				}
				if($prog == $ogni) {
					$num_gratis++;
					$riga_gratis .= '<td style="color:#ff2222"><b>'.$num_gratis.'</b></td>';
					$prog = 0;
				}
				else {
					$riga_gratis .= '<td>'.$num_gratis.'</td>';
					$prog++;
				}
				$riga_pax .= '<td>'.$i.'</td>';
			}
			if(($i - 1) % 30 != 0) {
				if($i > 30) echo '<tr class="b_top"><th>PAX</th>'.$riga_pax.'</tr>';
				else 			echo '<tr><th>PAX</th>'.$riga_pax.'</tr>';
				echo '<tr><th>GRATIS</th>'.$riga_gratis.'</tr>';
			}
			
			echo '</table>';
			
			echo '<p class="info_gratis">GRATIS: <b>'.$num_gratis.'</b>, DISAVANZO: '.$prog.' PAX</p>';
			$tot_gratis += $num_gratis;
		}
		
		// Se si sono stampate più notti si specifica il totale complessivo delle gratuità
		if($notti > 1) {
			echo '<div class="separatore"></div>';
			echo '<br />TOTALE COMPLESSIVO GRATUIT&Agrave;: <b>'.$tot_gratis.'</b><br /><br />';
		}

} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>