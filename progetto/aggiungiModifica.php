<html>
<head>
  <meta charset="utf-8">
  <meta name="author" content="Luca Ciancio, Alessio Giovanelli, Fabio Izzo">
  <meta http-equiv="Cache-control" content="no-cache">
  <title>Aggiungi</title>
  <link rel="icon" href="images/fenicebianca" type="image/png">
  <link rel="stylesheet" href="css/style.css">
  <script src="js/code.js"></script>
  <script>
    function showAlert(message) {
        alert(message);
    }

    function formatDate(date) {
        const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
        return new Date(date).toLocaleDateString('it-IT', options);
    }

    function reloadParent() {
        setTimeout(() => {
            window.opener.location.reload();
            window.close();
        }, 1000);
    }
  </script>
</head>
<body>
<?php
include 'html/aggiungiModifica.html';
include 'html/footer.html';
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($error) {
        exit;
    }

    $tipo = $_POST['tipo'];
    $today = date('Y-m-d');

    if ($tipo === 'veicolo') {
        $numTelaio = $_POST['numTelaio'] ?? null;
        $marca = $_POST['marca'] ?? null;
        $modello = $_POST['modello'] ?? null;
        $dataProd = $_POST['dataProd'] ?? null;

        if (strlen($numTelaio) !== 17) {
            echo "<script>showAlert('Il numero del telaio deve essere composto da 17 caratteri.');</script>";
        } elseif (strtotime($dataProd) > strtotime($today)) {
            echo "<script>showAlert('Errore: La data di produzione non può essere superiore alla data odierna.');</script>";
        } else {
            $sql = "INSERT INTO veicolo (numeroTelaio, marca, modello, dataProd) VALUES (:numTelaio, :marca, :modello, :dataProd)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bindParam(':numTelaio', $numTelaio);
                $stmt->bindParam(':marca', $marca);
                $stmt->bindParam(':modello', $modello);
                $stmt->bindParam(':dataProd', $dataProd);

                if ($stmt->execute()) {
                    echo "<script>showAlert('Nuovo veicolo creato con successo'); reloadParent();</script>";
                } else {
                    echo "<script>showAlert('Errore durante l\\'inserimento dei dati.');</script>";
                    print_r($stmt->errorInfo());
                }
            } else {
                echo "<script>showAlert('Errore nella preparazione della query: " . $conn->errorInfo()[2] . "');</script>";
            }
        }
    } elseif ($tipo === 'targa') {
        $targa = $_POST['targa'] ?? null;
        $dataEm = $_POST['dataEm'] ?? null;
        $numTelaio = $_POST['telaio'] ?? null;

        if (strtotime($dataEm) > strtotime($today)) {
            echo "<script>showAlert('Errore: La data di emissione non può essere superiore alla data odierna.');</script>";
        } else {
            // Controlla il formato della targa
            if (!preg_match('/^[A-Z]{2}[0-9]{3}[A-Z]{2}$/', $targa)) {
                echo "<script>showAlert('Errore: La targa deve essere composta da 2 lettere, 3 numeri e 2 lettere (es. AB123CD).');</script>";
            } else {
                // Controlla la data di emissione in base alla prima lettera della targa
                $primaLettera = $targa[0];
                $dataInizio = null;
                $dataFine = null;

                switch ($primaLettera) {
                    case 'A':
                        $dataInizio = '1994-01-01';
                        $dataFine = '1997-12-31';
                        break;
                    case 'B':
                        $dataInizio = '1998-01-01';
                        $dataFine = '2001-12-31';
                        break;
                    case 'C':
                        $dataInizio = '2002-01-01';
                        $dataFine = '2005-12-31';
                        break;
                    case 'D':
                        $dataInizio = '2006-01-01';
                        $dataFine = '2009-12-31';
                        break;
                    case 'E':
                        $dataInizio = '2010-01-01';
                        $dataFine = '2015-12-31';
                        break;
                    case 'F':
                        $dataInizio = '2016-01-01';
                        $dataFine = '2018-12-31';
                        break;
                    case 'G':
                        $dataInizio = '2019-01-01';
                        $dataFine = date('Y-m-d'); // Data odierna
                        break;
                    default:
                        echo "<script>showAlert('Errore: La prima lettera della targa deve essere compresa tra A e G.');</script>";
                        exit;
                }

                if (strtotime($dataEm) < strtotime($dataInizio) || strtotime($dataEm) > strtotime($dataFine)) {
                    echo "<script>showAlert('Errore: La data di emissione non è valida per la targa con la prima lettera " . htmlspecialchars($primaLettera) . ". Deve essere compresa tra " . formatDate($dataInizio) . " e " . formatDate($dataFine) . ".');</script>";
                    exit;
                }

                if ($targa && $numTelaio) {
                    // Controlla se il veicolo esiste
                    $sqlCheckVeicolo = "SELECT dataProd FROM veicolo WHERE numeroTelaio = :numTelaio";
                    $stmtCheckVeicolo = $conn->prepare($sqlCheckVeicolo);
                    $stmtCheckVeicolo->bindParam(':numTelaio', $numTelaio);
                    $stmtCheckVeicolo->execute();
                    $dataProd = $stmtCheckVeicolo->fetchColumn();

                    if (!$dataProd) {
                        echo "<script>showAlert('Errore: Il veicolo con numero di telaio " . htmlspecialchars($numTelaio) . " non esiste.');</script>";
                    } elseif (strtotime($dataEm) < strtotime($dataProd)) {
                        echo "<script>showAlert('Errore: La data di emissione della targa non può essere precedente alla data di produzione del veicolo.');</script>";
                    } else {
                        // Controlla se il veicolo ha già una targa assegnata
                        $sqlCheckTarga = "SELECT COUNT(*) FROM targaattiva WHERE veicolo = :numTelaio";
                        $stmtCheckTarga = $conn->prepare($sqlCheckTarga);
                        $stmtCheckTarga->bindParam(':numTelaio', $numTelaio);
                        $stmtCheckTarga->execute();
                        $countTarga = $stmtCheckTarga->fetchColumn();

                        if ($countTarga > 0) {
                            echo "<script>showAlert('Errore: Il veicolo ha già una targa assegnata.');</script>";
                        } else {
                            // Inserisci la nuova targa
                            $sqlTarga = "INSERT INTO targa (targa, dataEm) VALUES (:targa, :dataEm)";
                            $stmtTarga = $conn->prepare($sqlTarga);

                            if ($stmtTarga) {
                                $stmtTarga->bindParam(':targa', $targa);
                                $stmtTarga->bindParam(':dataEm', $dataEm);

                                if ($stmtTarga->execute()) {
                                    echo "<script>showAlert('Nuova targa aggiunta con successo'); reloadParent();</script>";

                                    // Inserisci la relazione nella tabella targaattiva
                                    $sqlRelazione = "INSERT INTO targaattiva (targa, veicolo) VALUES (:targa, :numTelaio)";
                                    $stmtRelazione = $conn->prepare($sqlRelazione);
                                    $stmtRelazione->bindParam(':targa', $targa);
                                    $stmtRelazione->bindParam(':numTelaio', $numTelaio);

                                    if ($stmtRelazione->execute()) {

                                    } else {
                                        echo "<script>showAlert('Errore durante l\\'inserimento della relazione targa-veicolo.');</script>";
                                        print_r($stmtRelazione->errorInfo());
                                    }
                                } else {
                                    echo "<script>showAlert('Errore durante l\\'inserimento della targa.');</script>";
                                    print_r($stmtTarga->errorInfo());
                                }
                            } else {
                                echo "<script>showAlert('Errore nella preparazione della query per la targa: " . $conn->errorInfo()[2] . "');</script>";
                            }
                        }
                    }
                } else {
                    echo "<script>showAlert('Errore: Dati mancanti per l\\'inserimento della targa.');</script>";
                }
            }
        }
    } elseif ($tipo === 'revisione') {
        $targa1 = $_POST['targa1'] ?? null;
        $dataRev = $_POST['dataRev'] ?? null;
        $esito = $_POST['Ass'] ?? null;
        $motivazione = $_POST['menu'] ?? null;

        if (strtotime($dataRev) > strtotime($today)) {
            echo "<script>showAlert('Errore: La data della revisione non può essere superiore alla data odierna.');</script>";
        } else {
            $sqlCheckTarga = "SELECT COUNT(*) FROM revisione WHERE targa = :targa1";
            $stmtCheckTarga = $conn->prepare($sqlCheckTarga);
            $stmtCheckTarga->bindParam(':targa1', $targa1);
            $stmtCheckTarga->execute();
            $countTarga = $stmtCheckTarga->fetchColumn();

            if ($countTarga == 0) {
                echo "<script>showAlert('Errore: La targa " . htmlspecialchars($targa1) . " non esiste.');</script>";
            } else {
                // Recupera l'ultima revisione per la targa
                $sqlLastRev = "SELECT MAX(dataRev) as ultima_data, MAX(numero) as ultimo_numero FROM revisione WHERE targa = :targa1";
                $stmtLastRev = $conn->prepare($sqlLastRev);
                $stmtLastRev->bindParam(':targa1', $targa1);
                $stmtLastRev->execute();
                $lastRev = $stmtLastRev->fetch(PDO::FETCH_ASSOC);
                $ultimaData = $lastRev['ultima_data'];
                $ultimoNumero = $lastRev['ultimo_numero'];

                // Controlla che la data della nuova revisione non sia precedente all'ultima revisione
                if ($ultimaData && strtotime($dataRev) <= strtotime($ultimaData)) {
                    echo "<script>showAlert('Errore: La data della revisione non può essere precedente o uguale all\\'ultima revisione effettuata.');</script>";
                } else {
                    // Genera il numero progressivo per la nuova revisione
                    $nuovoNumero = $ultimoNumero ? $ultimoNumero + 1 : 1;

                    // Imposta motivazione come NULL se lo stato è positivo
                    if ($esito === 'positivo') {
                        $motivazione = null;
                    }

                    // Inserisci la nuova revisione
                    $sqlRev = "INSERT INTO revisione (targa, numero, dataRev, esito, motivazione) VALUES (:targa1, :numero, :dataRev, :esito, :motivazione)";
                    $stmtRev = $conn->prepare($sqlRev);

                    if ($stmtRev) {
                        $stmtRev->bindParam(':targa1', $targa1);
                        $stmtRev->bindParam(':numero', $nuovoNumero);
                        $stmtRev->bindParam(':dataRev', $dataRev);
                        $stmtRev->bindParam(':esito', $esito);
                        $stmtRev->bindParam(':motivazione', $motivazione);

                        if ($stmtRev->execute()) {
                            echo "<script>showAlert('Nuova revisione aggiunta con successo'); reloadParent();</script>";
                        } else {
                            echo "<script>showAlert('Errore durante l\\'inserimento della revisione.');</script>";
                            print_r($stmtRev->errorInfo());
                        }
                    } else {
                        echo "<script>showAlert('Errore nella preparazione della query per la revisione: " . $conn->errorInfo()[2] . "');</script>";
                    }
                }
            }
        }
    }
}
?>
</body>
</html>
