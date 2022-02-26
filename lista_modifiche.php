<?php
session_start(); // Si lancia la sezione
require('funzioni_admin.php');

if(isset($_SESSION['pseudo'])) { // Si verifica che sia l'amministratore

	$azione = ""; $i = 0;

	if(isset($_POST['data_modifiche'])) {
		$data = controllo_data_ora($_POST['data_modifiche'], $_POST['ora_modifiche']);
		
		if($data == NULL) {
			$azione = "DATA E ORA NON VALIDE";
		}
		
		else {		
			$db = db_connect();
			
			// IMPLEMENTARE POSSIBILITÀ DI SCEGLIERE IL CRITERIO DI ORDINAMENTO
			
			$reponse = $db->query("SELECT * FROM prenotazioni WHERE data_ultima_modifica>=" . $data . " ORDER BY camera ASC");
			// $reponse = $db->query("SELECT * FROM prenotazioni WHERE data_ultima_modifica>=" . $data . " ORDER BY data_ultima_modifica DESC");
	
			$db->connection = NULL;
		}
	}
	
	//se si tratta di eliminare e si é confermato si elimina
	else {
	}
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it">
<head>
	<title>LISTA MODIFICHE</title>
	<?php header_standard(); ?>
</head>

<body style="background: #fff">
<?php $testo_menu_top = "";
		menu_top($testo_menu_top); ?>

		<div id="corpo_a"><?php

		switch($azione) {

			case "DATA E ORA NON VALIDE":
			echo '<p class="messageBad">DATA E ORA NON VALIDE</p>';
			break;
		}
		
		if($azione == "") {
		?>
			<p class="titolo"><?php echo "PRENOTAZIONI MODIFICATE O CREATE DOPO IL " . date("d/m/Y", $data) . " ALLE " . date("H:i", $data); ?></p>


			<table class="lista_camere" id="lista_modifiche">
			 	<thead>
					<tr>
						<th>MOD. IL</th>
						<th>CAM.</th>
						<th>DAL</th>
						<th>AL</th>
						<th>NOME</th>
						<th>GRUPPO</th>
						<th>PAX</th>
						<th>OP.</th>
					</tr>
				</thead>
				 
				<tbody>
<?php
			while($donnees = $reponse->fetch()) { 
				$id						 = $donnees['id'];
				$nome						 = formatta_visualizzazione($donnees['nome']);	
				$gruppo					 = formatta_visualizzazione($donnees['gruppo']);
				$pax						 = $donnees['pax'];
				$data_arrivo			 = date("d/m/Y", $donnees['data_arrivo']);
				$data_partenza			 = date("d/m/Y", $donnees['data_partenza']);
				$camera 					 = $donnees['camera'];
				$note			 			 = formatta_visualizzazione($donnees['note']);
				$data_ultima_modifica = date("d/m/Y", $donnees['data_ultima_modifica']);
			
				// Per creare linee dai colori alterni
				if($i == 1) { $linea = 'style="background: #eee"'; $i = 0; }
				else 			{ $linea = ''; $i = 1; }
			?>
					<tr <?php echo $linea; ?>>
						<td><?php echo $data_ultima_modifica; ?></td>
						<td><?php echo $camera; ?></td>
						<td><?php echo $data_arrivo; ?></td>
						<td><?php echo $data_partenza; ?></td>
						<td><?php echo $nome; ?></td>
						<td><?php echo $gruppo; ?></td>
						<td><?php echo $pax; ?></td>
						<td><?php echo '<a target="blank" href="gestione_prenotazioni.php?modifica=' . $id . '">MODIFICA</a>'; ?></td>
					</tr>
					
					<?php if($note != "") { ?>
					<tr>
						<td class="note" style="text-align:right" colspan="8"><?php echo $note; ?></td>
					</tr><?php } ?>
			
	<?php }
		}	?>
				</tbody>
			</table>
		</div>
		
	</body>
</html>
<?php
} // Fin de "Si l'admin s'est bien identifié"

else { // Si pass ou pseudo n'existent pas on renvoie à la page de login administrateur
	header('Location: index.php');
}
?>