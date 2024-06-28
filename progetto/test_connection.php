<?php
include 'config.php'; // Includi il file di configurazione del database

// Query di test per verificare la connessione al database
// Include il file di configurazione
include 'config.php';

// Verifica se la connessione è avvenuta correttamente
if ($conn) {
    echo "OK";
} else {
    echo "Error";
}

?>
