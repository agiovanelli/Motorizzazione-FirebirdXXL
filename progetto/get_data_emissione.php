<?php
include 'config.php';

if (isset($_GET['targa'])) {
    $targa = $_GET['targa'];
    $dataEM = getDataEmissione($targa);
    echo json_encode(['dataEM' => $dataEM]);
}

function getDataEmissione($targa)
{
    global $conn;

    $sql = "SELECT dataEM FROM targa WHERE targa = :targa";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':targa', $targa, PDO::PARAM_STR);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['dataEM'] : null;
}
?>
