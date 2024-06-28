<?php
$servername = "localhost"; // In genere, il servername è "localhost" per Altervista, ma potrebbe essere diverso
$username = "programmazionewebalegio"; // Rimuovi il "@administrator"
$password=null;
$dbname = "my_programmazionewebalegio";
$error = false;

try {
	$conn = new PDO("mysql:host=".$servername.";dbname=".$dbname, 
										$username, $password);
	// set the PDO error mode to exception
	$conn->setAttribute(PDO::ATTR_ERRMODE, 
											PDO::ERRMODE_EXCEPTION);
} catch(PDOException$e) {
	echo "DB Error: " . $e->getMessage();
	$error = true;
}	

?>
