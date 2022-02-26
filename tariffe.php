<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore

	$erreur = 0;
	$azione = 0;
	
	$nome_precedente = NULL;

	$db = db_connect();
	
	//se si tratta di eliminare si chiede conferma
	if(isset($_POST['elimina'])) {
		$tariffa_sel = $_POST['elimina'];
		
		$azione = 1;
	}
	
	//se si tratta di eliminare e si é confermato si elimina
	elseif(isset($_POST['conferma'])) {
		$tariffa_sel = $_POST['conferma'];
		
		// Si elimina la camera
		$db->query("DELETE FROM tariffe WHERE nome=" . $tariffa_sel);

		$azione = 2;
	}
	
	// Si prendono i dati per la lista
	$reponse = $db->query("SELECT * FROM tariffe ORDER BY nome, data_inizio ASC");
	
	$db->connection = NULL;
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head>
	<title>TARIFFE</title>
	<?php header_standard(); ?>
</head>

<body>
<?php $testo_menu_top = '';
		menu_top($testo_menu_top); ?>

		<div id="corpo_a"><?php

		switch($azione) {
			case 1:
	?>
	
		<div class="form1_650 form_green">
			<p class="titolo">CONFERMA ELIMINAZIONE</p>
			<div class="cont_op"><p class="form_txt">sei sicuro di voler eliminare la tariffa <span>'<?php echo $tariffa_sel; ?>'</span> definitivamente ?</p></div>
			
			<div class="pulsanti_bottom">
				<a class="bottone" href="tariffe">NO</a>
					<input class="bottone bottone_r" type="submit" value="SI" />
					<input type="hidden" name="conferma" value="<?php echo $tariffa_sel; ?>" />
			</div>
		</div>
		<?php
			break;

			case 2:
			echo '<div class="form1_650 form_green"><p class="titolo">TARIFFA ELIMINATA CON SUCCESSO</p></div>';
			break;
		} ?>
			<div class="form1_650">
				<p class="titolo">GESTIONE TARIFFE</p>
				<table>
				 	<thead>
						<tr>
							<th style="width: 125px">TARIFFA</th>
							<th style="min-width: 115px">PERIODO</th>
							<th style="width: 75px">PREZZO</th>
							<th style="width: 185px">NOTE</th>
						</tr>
					</thead>
					 
					<tbody>
<?php while($donnees = $reponse->fetch()) {
		$nome = $donnees['nome'];
		$data_inizio = $donnees['data_inizio'];
		$data_fine = $donnees['data_fine'];
		$prezzo = $donnees['prezzo'];
		$note = formatta_visualizzazione($donnees['note']);
		
		// Se stiamo cambiando tariffa
		if($nome != $nome_precedente) {
			echo '<tr class="border_tariffa">';
			echo '<td class="nome_tariffa">' . formatta_visualizzazione($nome) . '</td>';
		}
		// Altrimenti lasciamo la prima colonna vuota e applichiamo il bordo fino
		else 	echo '<tr class="border_bottom"><td></td>';
		echo '<td class="monospace_style">'.formatta_periodo($data_inizio, $data_fine).'</td>';
		echo '<td class="monospace_style center">' . $prezzo . '</td>';
		if($nome != $nome_precedente) {
			$nome_precedente = $nome;
			echo '<td class="nomi">';
			echo '<div class="hover_hide">'.$note.'</div>';
			
			echo '<div class="hover_show">';
			echo '<form name="dati" style="display:inline" action="gestione_tariffe.php" method="post" enctype="multipart/form-data">';
			echo '<input class="bottone bottone_small" type="submit" value="MODIFICA" />';
			echo '<input type="hidden" name="modifica" value="' . $nome . '" />';
			echo '</form>';
			
			echo '<form name="dati" style="display:inline" action="tariffe.php" method="post" enctype="multipart/form-data">';
			echo '<input class="bottone bottone_small bottone_r" type="submit" value="ELIMINA" />';
			echo '<input type="hidden" name="elimina" value="' . $nome . '" />';
			echo '</form>';
			echo '</div>';
			echo '</td>';
		}
		else echo '<td class="nomi">' . $note . '</td>';
		echo '</tr>';

	}
?>
				</table>
			</div>
		</div>
		
	</body>
</html>
<?php
} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>