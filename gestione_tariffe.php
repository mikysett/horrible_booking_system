<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore

	$erreur = 0;
	$azione = '';
	$i = 0;
	$nome = '';
	$tariffe_da_inserire = 10;
	
	
	$db = db_connect();

	// Se il form è stato compilato e inviato
	if(isset($_POST['nome'])) {
	
		// Se la tariffa va modificata si elimina per essere reinserita
		if(isset($_POST['modifica'])) {
			$nome_eliminare = $_POST['modifica'];
			
			$db->query('DELETE FROM tariffe WHERE nome=\'' . $nome_eliminare . '\'');
	
			$azione = 'TARIFFA MODIFIED';
		}
		
		$nome = formatta_salvataggio($_POST['nome']);
		if($nome == NULL)		$nome = 'NO NAME';
	
		for($i = 0 ; isset($_POST["data_inizio" . $i]) && $_POST["data_inizio" . $i] != "" ; $i++) {
			$prezzo = intval($_POST["prezzo" . $i]);
			$note = formatta_salvataggio($_POST["note" . $i]);
			$data_inizio = controllo_data($_POST["data_inizio" . $i]);
			$data_fine = controllo_data($_POST["data_fine" . $i]);
			
			if($data_inizio == NULL || $data_fine == NULL || $data_inizio >= $data_fine) {
				$azione = "INCORRECT FIELD";
				continue;
			}
		
			// Si inserisce la prenotazione nel database
			$sql = $db->prepare('INSERT INTO tariffe
				(
					nome,
					data_inizio,
					data_fine,
					prezzo,
					note
				)
		
				VALUES
				(
					:nome,
					:data_inizio,
					:data_fine,
					:prezzo,
					:note
				)
			');
	
			$request = $sql->execute(array(
				':nome' => $nome,
				':data_inizio' => $data_inizio,
				':data_fine' => $data_fine,
				':prezzo' => $prezzo,
				':note' => $note
				)
			);

			if($azione == "") $azione = "TARIFFA ADDED";
		}
	}
	
	
	// Se la tariffa va modificata o è stata modificata si prendono i dati
	elseif(isset($_POST['modifica'])) {
		$nome = $_POST['modifica'];
		
		$reponse = $db->query("SELECT * FROM tariffe WHERE nome='" . $nome . "' ORDER BY data_inizio ASC");
	}
	if($azione == "TARIFFA ADDED" || $azione == "TARIFFA MODIFIED" || $azione == "INCORRECT FIELD") {
		$reponse = $db->query("SELECT * FROM tariffe WHERE nome='" . $nome . "' ORDER BY data_inizio ASC");
	}

	
	$db->connection = NULL;
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head><?php
	if(isset($_POST['modifica']) || $azione == "TARIFFA ADDED")  echo "<title>" . $nome . " - MODIFICA TARIFFA</title>";
	else 								   										 echo "<title>NUOVA TARIFFA</title>";
	header_standard(); ?>
</head>

<body>
	<?php $testo_menu_top = ' | <a href="tariffe.php">TARIFFE</a>'; menu_top($testo_menu_top); ?>
	<div id="corpo_a"><?php

		if($azione != "") {
			echo 	'<div class="form1_650';
			
			if($azione == "TARIFFA ADDED" || $azione == "TARIFFA MODIFIED") echo  ' form_green';
			else	 echo  ' form_red';
			echo '">';

			switch($azione) {
	
				case "TARIFFA ADDED":
				$titolo_operazione = '<p class="titolo">TARIFFA AGGIUNTA</p>';
				$contenuto_operazione = '';
				$bottoni_operazione = '';
				break;
				
				case "TARIFFA MODIFIED":
				$titolo_operazione = '<p class="titolo">TARIFFA MODIFICATA</p>';
				$contenuto_operazione = '';
				$bottoni_operazione = '';
				break;
	
				case "INCORRECT FIELD":
				$titolo_operazione = '<p class="titolo">ERRORE</p>';
				$contenuto_operazione = 'ALCUNI ERRORI SONO STATI RISCONTRATI NEI CAMPI INSERITI PER LA TARIFFA';
				$bottoni_operazione = '';
				break;
			}
			
			echo $titolo_operazione;
			if($contenuto_operazione != '') echo '<div class="cont_op">' . $contenuto_operazione . '</div>';
			if($bottoni_operazione != '') echo '<div class="pulsanti_bottom">' . $bottoni_operazione . '</div>';
	
			echo '</div>';
		}	
		?>
		
	   <form name="dati" action="gestione_tariffe.php" method="post" enctype="multipart/form-data">
			<div class="form1_650">
			<p class="titolo"><?php
				if(isset($_POST['modifica']) || $azione == "TARIFFA ADDED")  echo "MODIFICA TARIFFA";
				else 								   										 echo "NUOVA TARIFFA";
			?></p>

				<table>
					<tr>
						<th><label for="nome">NOME</label></th>
						<td colspan="2"><input type="text" name="nome" class="field" value="<?php echo formatta_visualizzazione($nome) ?>" tabindex="1" autofocus /></td>
					</tr>
					<tr>
						<th>DAL</th>
						<th>AL</th>
						<th>PREZZO</th>
					</tr><?php
					
					if(isset($_POST['modifica']) || $azione == "TARIFFA ADDED") {
						for($i = 0 ; $dati_tariffa = $reponse->fetch() ; $i++) {
							$data_inizio = $dati_tariffa["data_inizio"];
							$data_fine = $dati_tariffa["data_fine"];
							$prezzo = $dati_tariffa["prezzo"];
							$note = $dati_tariffa["note"];
							
							echo '<tr>';
							echo '<td><input type="text" name="data_inizio' . $i . '" class="field" value="' . date("d/m/Y", $data_inizio) . '" /></td>';
							echo '<td><input type="text" name="data_fine' . $i . '" class="field" value="' . date("d/m/Y", $data_fine) . '" /></td>';
							echo '<td><input type="text" name="prezzo' . $i . '" class="field" value="' . $prezzo . '" /></td>';
							echo '</tr>';

							echo '<tr>';
							echo '<th>NOTE</th>';
							echo '<td colspan=2><textarea class="nota_corta" name="note' . $i . '">' . formatta_visualizzazione($note) . '</textarea></td>';
							echo '</tr>';
						}
					}
					
					
					// Si aggiungono dei campi vuoti per l'inserimento
					for($j = $i ; $j < $i + $tariffe_da_inserire ; $j++) {
						echo '<tr>';
						echo '<td><input type="text" name="data_inizio' . $j . '" class="field" /></td>';
						echo '<td><input type="text" name="data_fine' . $j . '" class="field" /></td>';
						echo '<td><input type="text" name="prezzo' . $j . '" class="field" /></td>';
						echo '</tr>';

						echo '<tr>';
						echo '<th>NOTE</th>';
						echo '<td colspan=2><textarea class="nota_corta" name="note' . $j . '"></textarea></td>';
						echo '</tr>';
					}
						
					echo '</table>';

					echo '<div class="pulsanti_bottom">';

					echo '<a class="bottone" href="tariffe.php">ANNULLA</a> ';
					
					if(isset($_POST['modifica'])) {
						echo '<input class="bottone" type="submit" value="MODIFICA" />';
						echo '<input type="hidden" name="modifica" value="' . $nome . '" />';
					}
					else 	echo '<input class="bottone" type="submit" value="AGGIUNGI" />';
					
					echo '</div>';
						
					?>
				</div>
			</form>
		</div>
		
	</body>
</html>
<?php
} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>