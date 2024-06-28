<?php
include 'config.php';

header('Content-Type: application/json');
try{
    if(isset($_GET['targa']))
    {
        $targa= $_GET['targa'];

        $sql= "SELECT v.numeroTelaio, v.marca, v.modello, v.dataProd
        FROM veicolo v
        JOIN targaattiva ta ON v.numeroTelaio = ta.veicolo
        JOIN targa t ON ta.targa = t.targa
        WHERE t.targa = :targa";
        
        $stmt = $conn -> prepare($sql);
        $stmt->bindParam(':targa', $targa, PDO::PARAM_STR);
        $stmt->execute();
        $details = $stmt->fetch(PDO::FETCH_ASSOC);

        if($details)
        {
            echo json_encode($details);
        } else{
            echo json_encode(['error' => 'Nessun dettaglio trovato per questa targa']);
        }
    }
    else{
        echo json_encode(['error' => 'Nessuna targa specificata']);
    }
}catch(Exception $e)
{
    echo json_encode(['error' =>'Errore nel server: '. $e->getMessage()]);
}
?>