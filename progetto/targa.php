<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="author" content="Luca Ciancio, Alessio Giovanelli, Fabio Izzo">
  <meta http-equiv="Cache-control" content="no-cache">
  <title>Targa</title>
  <link rel="icon" href="images/fenicebianca" type="image/png">
  <link rel="stylesheet" href="css/style.css">
  <script src="js/code.js"></script>
</head>
<body>
<?php
include 'html/targa.html';
include 'html/footer.html';
include 'config.php';

// Funzione per eliminare una targa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];
    deleteTarga($deleteId);
}

function deleteTarga($targa) {
    global $conn;

    // Elimina la targa dal database
    $sql = "DELETE FROM targa WHERE targa = :targa";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':targa', $targa, PDO::PARAM_STR);
    $stmt->execute();

    // Elimina le eventuali relazioni correlate
    $sql2 = "DELETE FROM targarestituita WHERE targa = :targa";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bindValue(':targa', $targa, PDO::PARAM_STR);
    $stmt2->execute();

    $sql3 = "DELETE FROM targaattiva WHERE targa = :targa";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->bindValue(':targa', $targa, PDO::PARAM_STR);
    $stmt3->execute();

    $sql4 ="DELETE FROM revisione WHERE targa = :targa";
    $stmt4 = $conn->prepare($sql4);
    $stmt4->bindValue(':targa', $targa, PDO::PARAM_STR);
    $stmt4->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $oldTarga = $_POST['edit_id'];
    $newTarga = $_POST['new_targa'];
    $newDataEm = $_POST['new_dataEm'];
    $newStato = $_POST['new_stato'];
    $newDataRest = $_POST['new_dataRest'];

    // Chiamata alla funzione updateTarga con la connessione al database
    updateTarga($conn, $oldTarga, $newTarga, $newDataEm, $newStato, $newDataRest);
}

function updateTarga($conn, $oldTarga, $newTarga, $newDataEm, $newStato, $newDataRest) {
    try {
        // Inizia una transazione per garantire l'integrità dei dati
        $conn->beginTransaction();

        // Aggiorna la targa nella tabella principale
        $sql = "UPDATE targa SET targa = :newTarga, dataEM = :newDataEm WHERE targa = :oldTarga";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':newTarga', $newTarga, PDO::PARAM_STR);
        $stmt->bindValue(':newDataEm', $newDataEm, PDO::PARAM_STR);
        $stmt->bindValue(':oldTarga', $oldTarga, PDO::PARAM_STR);
        $stmt->execute();

        // Gestione degli stati Restituita e Attiva
        if ($newStato == 'Restituita') {
            // Inserisce o aggiorna la targa nella tabella targarestituita
            $sql = "INSERT INTO targarestituita (targa, dataRest) VALUES (:newTarga, :newDataRest) 
                    ON DUPLICATE KEY UPDATE dataRest = :newDataRest";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':newTarga', $newTarga, PDO::PARAM_STR);
            $stmt->bindValue(':newDataRest', $newDataRest, PDO::PARAM_STR);
            $stmt->execute();

            // Cancella la targa dalla tabella targaattiva
            $sql = "DELETE FROM targaattiva WHERE targa = :newTarga";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':newTarga', $newTarga, PDO::PARAM_STR);
            $stmt->execute();
        } else {
            // Inserisce o aggiorna la targa nella tabella targaattiva
            $sql = "INSERT INTO targaattiva (targa) VALUES (:newTarga) ON DUPLICATE KEY UPDATE targa = :newTarga";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':newTarga', $newTarga, PDO::PARAM_STR);
            $stmt->execute();

            // Cancella la targa dalla tabella targarestituita
            $sql = "DELETE FROM targarestituita WHERE targa = :newTarga";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':newTarga', $newTarga, PDO::PARAM_STR);
            $stmt->execute();
        }

        // Aggiorna la targa nella tabella revisione
        $sql = "UPDATE revisione SET targa = :newTarga WHERE targa = :oldTarga";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':newTarga', $newTarga, PDO::PARAM_STR);
        $stmt->bindValue(':oldTarga', $oldTarga, PDO::PARAM_STR);
        $stmt->execute();

        // Conferma la transazione
        $conn->commit();

    } catch (PDOException $e) {
        // In caso di errore durante l'aggiornamento nel database, annulla la transazione e gestisce l'errore
        $conn->rollBack();
        error_log('Errore nell\'aggiornamento nel database: ' . $e->getMessage());
    }
}

function getTargheFromDatabase($searchTarga = '', $searchNumeroTelaio = '', $searchDataEm = '', $searchAttivita = '', $searchDataRest = '') {
    global $conn; 
    $sql = "SELECT t.targa, t.dataEM, tr.dataRest,
            CASE
                WHEN tr.targa IS NOT NULL THEN 'Restituita'
                ELSE 'Attiva'
            END AS Stato
        FROM targa t 
        LEFT JOIN targarestituita tr ON t.targa = tr.targa
        LEFT JOIN targaattiva ta ON t.targa = ta.targa
        LEFT JOIN veicolo v ON ta.veicolo = v.numeroTelaio";
    
    $conditions = [];
    if (!empty($searchTarga)) {
        $conditions[] = "t.targa LIKE :searchTarga";
    }
    if (!empty($searchNumeroTelaio)) {
        $conditions[] = "v.numeroTelaio LIKE :searchNumeroTelaio";
    }
    if (!empty($searchDataEm)) {
        $conditions[] = "t.dataEM = :searchDataEm";
    }
    if ($searchAttivita !== '') {
        if ($searchAttivita == '1') {
            $conditions[] = "tr.targa IS NULL";
        } else if ($searchAttivita == '0') {
            $conditions[] = "tr.targa IS NOT NULL";
            if (!empty($searchDataRest)) {
                $conditions[] = "tr.dataRest = :searchDataRest";
            }
        }
    }
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    $sql .= " GROUP BY t.targa, t.dataEM";

    $stmt = $conn->prepare($sql);

    if (!empty($searchTarga)) {
        $searchTarga = '%' . $searchTarga . '%';
        $stmt->bindParam(':searchTarga', $searchTarga, PDO::PARAM_STR);
    }
    if (!empty($searchNumeroTelaio)) {
        $searchNumeroTelaio = '%' . $searchNumeroTelaio . '%';
        $stmt->bindParam(':searchNumeroTelaio', $searchNumeroTelaio, PDO::PARAM_STR);
    }
    if (!empty($searchDataEm)) {
        $stmt->bindParam(':searchDataEm', $searchDataEm, PDO::PARAM_STR);
    }
    if (!empty($searchDataRest)) {
        $stmt->bindParam(':searchDataRest', $searchDataRest, PDO::PARAM_STR);
    }

    $stmt->execute();

    $targhe = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $targhe[] = $row;
    }

    return $targhe;
}

$searchTarga = isset($_GET['searchTarga']) ? $_GET['searchTarga'] : '';
$searchNumeroTelaio = isset($_GET['searchNumeroTelaio']) ? $_GET['searchNumeroTelaio'] : '';
$searchDataEm = isset($_GET['searchDataEm']) ? $_GET['searchDataEm'] : '';
$searchAttivita = isset($_GET['searchAttivita']) ? $_GET['searchAttivita'] : '';
$searchDataRest = isset($_GET['searchDataRest']) ? $_GET['searchDataRest'] : '';

$targhe = getTargheFromDatabase($searchTarga, $searchNumeroTelaio, $searchDataEm, $searchAttivita, $searchDataRest);
?>

<div id="contenuto" class="scorrevole">
<div class="centrato">
  <table class="tabella" id="TableTarga">
  <thead>
      <tr class="testata">
        <th>Targa <img onclick="sortTarga(0)" src="images/sort" alt="icona sort" class="sort"></th>
        <th>Data Emissione <img onclick="sortTarga(1)" src="images/sort" alt="icona sort" class="sort"></th>
        <th>Stato</th>
        <th>Data Restituzione <img onclick="sortTarga(2)" src="images/sort" alt="icona sort" class="sort"></th>
        <th>Modifica</th>
        <th>Elimina</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($targhe)): ?>
        <?php foreach ($targhe as $index => $targa): ?>
          <tr class="targa-row <?php echo $targa["Stato"] === 'Attiva' ? 'clickable-row' : ''; ?>" data-targa="<?php echo htmlspecialchars($targa["targa"]); ?>" data-row="<?php echo $index % 2 == 0 ? 'pari' : 'dispari'; ?>" id="<?php echo $index % 2 == 0 ? 'rigaPari' : 'rigaDispari'; ?>">
            <td><?php echo htmlspecialchars($targa["targa"]); ?></td>
            <td>
            <span class="targa-dataEM"><?php echo htmlspecialchars($targa["dataEM"]); ?></span>
            <input type="date" style="display:none;" class="edit-dataEM" value="<?php echo htmlspecialchars($targa["dataEM"]); ?>">
            </td>            
            <td><?php echo htmlspecialchars($targa["Stato"]); ?></td>
            <td>
  <span class="targa-dataRest"><?php echo htmlspecialchars($targa["dataRest"]); ?></span>
  <input type="date" style="display:none;" class="edit-dataRest" value="<?php echo htmlspecialchars($targa["dataRest"]); ?>">
</td>
<td class="imgcentr">
    <button id="trasparente" class="edit-button" onclick="modificaTarga('<?php echo $targa["targa"]; ?>','<?php echo $targa["dataEM"]; ?> ',' <?php echo $targa["Stato"]; ?>')"><img src="images/modificabianca" alt="icona modifica" width="20" height="20"></button>
    <form id="editForm" method="post" style="display:inline;">
        <input type="hidden" id="edit_id" name="edit_id" >
        <input type="hidden" id="new_targa" name="new_targa"value="<?php echo htmlspecialchars($targa["targa"]); ?>">
        <input type="hidden" id="new_dataEm" name="new_dataEm" value="<?php echo htmlspecialchars($targa["dataEM"]); ?>">
        <input type="hidden" id="new_stato" name="new_stato" value="<?php echo htmlspecialchars($targa["Stato"]); ?>">
        <input type="hidden" id="new_dataRest" name="new_dataRest" value="<?php echo htmlspecialchars($targa["dataRest"]); ?>">
        <button type="submit" id="trasparente" class="confirm-edit-button" style="display:none;" onclick="salvaModifiche('<?php echo $targa["targa"]; ?>','<?php echo $targa["dataEM"]; ?> ',' <?php echo $targa["Stato"]; ?>')"><img src="images/spunta" alt="icona salva" width="20" height="20"></button>
    </form>
    <button id="trasparente" class="cancel-edit-button" style="display:none;" onclick="annullaModifica('<?php echo $targa["targa"]; ?>','<?php echo $targa["dataEM"]; ?> ',' <?php echo $targa["Stato"]; ?>')"><img src="images/croce" alt="icona annulla" width="20" height="20"></button>
</td>
<td class="imgcentr">
    <button class="delete-button" onclick="confermaEliminazione('<?php echo $targa["targa"]; ?>','<?php echo $targa["dataEM"]; ?> ',' <?php echo $targa["Stato"]; ?>')"><img src="images/eliminabianco" alt="icona elimina" width="20" height="20"></button></td>            
</tr>
          <?php if ($targa["Stato"] === 'Attiva'): ?>
            <tr class="details-row hidden" id="details-<?php echo htmlspecialchars($targa["targa"]); ?>">
              <td colspan="4">Caricamento informazioni...</td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="6">Nessuna targa trovata</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</div>

<form id="deleteForm" method="post" style="display: none;">
    <input type="hidden" name="delete_id" id="delete_id">
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const rows = document.querySelectorAll(".clickable-row");

    function handleClick() {
        const targa = this.getAttribute("data-targa");
        const detailsRow = document.getElementById('details-' + targa);
        if(detailsRow.classList.contains("hidden")) {
            fetch('details.php?targa=' + targa)
            .then(response => response.json())
            .then(data => {
                if(data.error) {
                    detailsRow.innerHTML = '<td colspan="4">' + data.error + '</td>';
                } else {
                    detailsRow.innerHTML = '<td colspan="4">' +
                    '<table>' +
                        '<tr><td>Numero Telaio: ' + data.numeroTelaio + '</td></tr>' +
                        '<tr><td>Marca: ' + data.marca + '</td></tr>' +
                        '<tr><td>Modello: ' + data.modello + '</td></tr>' +
                        '<tr><td>Data Produzione: ' + data.dataProd + '</td></tr>' +
                        '</table>' +
                    '</td>';
                }
                detailsRow.classList.remove("hidden");
            })
            .catch(error => {
                console.error('Error fetching vehicle details:', error);
                detailsRow.innerHTML = '<td colspan="4">Errore nel caricamento dei dettagli.</td>';
                detailsRow.classList.remove("hidden");
            });
        } else {
            detailsRow.classList.add("hidden");
        }
    }

    rows.forEach(row => {
        row.addEventListener("click", handleClick);
    });

    const searchInputTarga = document.getElementById('targa');
    const searchInputDataEm = document.getElementById('dataEm');
    const searchInputAttivita = document.querySelectorAll('input[name="attivita"]');
    const searchInputDataRest = document.getElementById('dataRest');

    function performSearch() {
        const searchValueTarga = searchInputTarga.value;
        const searchValueDataEm = searchInputDataEm.value;
        const searchValueAttivita = [...searchInputAttivita].find(input => input.checked)?.value;
        const searchValueDataRest = searchInputDataRest.value;

        fetch(`targa.php?searchTarga=${encodeURIComponent(searchValueTarga)}&searchDataEm=${encodeURIComponent(searchValueDataEm)}&searchAttivita=${encodeURIComponent(searchValueAttivita)}&searchDataRest=${encodeURIComponent(searchValueDataRest)}`)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const newDocument = parser.parseFromString(data, 'text/html');
            const newTableBody = newDocument.querySelector('tbody');
            const tableBody = document.querySelector('tbody');
            tableBody.innerHTML = newTableBody.innerHTML;

            const newRows = document.querySelectorAll(".clickable-row");
            newRows.forEach(row => {
                row.addEventListener("click", handleClick);
            });
        })
        .catch(error => {
            console.error('Error fetching search results:', error);
        });
    }

    searchInputTarga.addEventListener('input', performSearch);
    searchInputDataEm.addEventListener('input', performSearch);
    searchInputAttivita.forEach(input => input.addEventListener('change', performSearch));
    searchInputDataRest.addEventListener('input', performSearch);

});

function confermaEliminazione(targa, dataEM, Stato) {
    const conferma = confirm(`Sei sicuro di voler eliminare la seguente revisione?\n\nTarga: ${targa}\nData Emissione: ${dataEM}\nStato: ${Stato}`);
    if (conferma) {
        document.getElementById('delete_id').value = targa;
        document.getElementById('deleteForm').submit();
    }
}

function modificaTarga(targa, dataEM, stato) {
    const row = document.querySelector(`tr[data-targa="${targa.trim()}"]`);
    const cells = row.querySelectorAll('td');

    row.dataset.originalTarga = cells[0].textContent.trim();
    row.dataset.originalDataEm = cells[1].textContent.trim();
    row.dataset.originalStato = cells[2].textContent.trim();
    row.dataset.originalDataRest = cells[3]?.textContent.trim() || '';

    cells[0].innerHTML = `
        <input type="text" class="edit-targa" value="${row.dataset.originalTarga}">
    `;

    cells[1].innerHTML = `
        <span class="targa-dataEM" style="display:none;">${row.dataset.originalDataEm}</span>
        <input type="date" class="edit-dataEM" value="${row.dataset.originalDataEm}">
    `;

    cells[3].innerHTML = `
        <span class="targa-dataRest" style="display:none;">${row.dataset.originalDataRest}</span>
        <input type="date" class="edit-dataRest" value="${row.dataset.originalDataRest}" style="display: none;">
    `;

    cells[2].innerHTML = `
        <select class="stato-select">
            <option value="Attiva" ${stato.trim() === 'Attiva' ? 'selected' : ''}>Attiva</option>
            <option value="Restituita" ${stato.trim() === 'Restituita' ? 'selected' : ''}>Restituita</option>
        </select>
    `;

    cells[4].innerHTML = `
        <button id="trasparente" class="conferma-button" onclick="salvaModifiche('${targa.trim()}')"><img src="images/spunta" alt="icona salva" width="20" height="20"></button>
        <button id="trasparente" class="annulla-button" onclick="annullaModifiche('${targa.trim()}')"><img src="images/croce" alt="icona annulla" width="20" height="20"></button>
    `;

    const statoSelect = row.querySelector('.stato-select');
    statoSelect.addEventListener('change', function() {
        const dataRestInput = row.querySelector('.edit-dataRest');
        if (this.value === 'Restituita') {
            dataRestInput.style.display = 'inline-block';
        } else {
            dataRestInput.style.display = 'none';
        }
    });

    if (stato.trim() === 'Restituita') {
        row.querySelector('.edit-dataRest').style.display = 'inline-block';
    }
}

function salvaModifiche(targa) {
    const row = document.querySelector(`tr[data-targa="${targa.trim()}"]`);
    const newTarga = row.querySelector('.edit-targa').value;
    const newDataEm = row.querySelector('.edit-dataEM').value;
    const newStato = row.querySelector('.stato-select').value;
    const newDataRest = newStato === 'Restituita' ? row.querySelector('.edit-dataRest').value : '';

    const originalStato = row.dataset.originalStato;
    const originalTarga = row.dataset.originalTarga;

    // Controllo del formato della nuova targa
    const targaRegex = /^[A-G][A-Z]\d{3}[A-Z]{2}$/i;
    if (!targaRegex.test(newTarga)) {
        alert("La targa deve avere il formato: lettera (A-G) + lettera + numero + numero + numero + lettera + lettera.");
        return;
    }

    if (originalStato === 'Restituita' && newStato === 'Attiva') {
        alert("Non è possibile cambiare lo stato da 'Restituita' a 'Attiva'.");
        return;
    }

    if (newStato === 'Restituita' && newDataRest < newDataEm) {
        alert("La data di restituzione non può essere precedente alla data di emissione.");
        return;
    }

    // Controllo della data di emissione in base alla prima lettera della targa
    const firstLetter = newTarga.charAt(0).toUpperCase();
    const dateEm = new Date(newDataEm);
    let dataInizio, dataFine;

    switch (firstLetter) {
        case 'A':
            dataInizio = new Date('1994-01-01');
            dataFine = new Date('1997-12-31');
            break;
        case 'B':
            dataInizio = new Date('1998-01-01');
            dataFine = new Date('2001-12-31');
            break;
        case 'C':
            dataInizio = new Date('2002-01-01');
            dataFine = new Date('2005-12-31');
            break;
        case 'D':
            dataInizio = new Date('2006-01-01');
            dataFine = new Date('2009-12-31');
            break;
        case 'E':
            dataInizio = new Date('2010-01-01');
            dataFine = new Date('2013-12-31');
            break;
        case 'F':
            dataInizio = new Date('2014-01-01');
            dataFine = new Date('2017-12-31');
            break;
        case 'G':
            dataInizio = new Date('2018-01-01');
            dataFine = new Date('2021-12-31');
            break;
        default:
            alert("La prima lettera della targa deve essere compresa tra A e G.");
            return;
    }

    if (dateEm < dataInizio || dateEm > dataFine) {
        alert(`La data di emissione deve essere compresa tra ${dataInizio.toISOString().split('T')[0]} e ${dataFine.toISOString().split('T')[0]} per una targa che inizia con ${firstLetter}.`);
        return;
    }

    const confirmationMessage = `
        Sei sicuro di voler salvare le seguenti modifiche?
        Targa: ${newTarga}
        Data Emissione: ${newDataEm}
        Stato: ${newStato}
        ${newStato === 'Restituita' ? `Data Restituzione: ${newDataRest}` : ''}
    `;

    if (!confirm(confirmationMessage)) {
        return;
    }

    document.getElementById('edit_id').value = targa;
    document.getElementById('new_targa').value = newTarga;
    document.getElementById('new_dataEm').value = newDataEm;
    document.getElementById('new_stato').value = newStato;
    document.getElementById('new_dataRest').value = newDataRest;

    document.getElementById('editForm').submit();
}





function annullaModifiche(targa) {
    const row = document.querySelector(`tr[data-targa="${targa.trim()}"]`);
    const cells = row.querySelectorAll('td');

    cells[0].textContent = row.dataset.originalTarga;
    cells[1].innerHTML = `
        <span class="targa-dataEM">${row.dataset.originalDataEm}</span>
        <input type="date" style="display:none;" class="edit-dataEM" value="${row.dataset.originalDataEm}">
    `;
    cells[2].textContent = row.dataset.originalStato;
    cells[3].innerHTML = `
        <span class="targa-dataRest">${row.dataset.originalDataRest}</span>
        <input type="date" style="display:none;" class="edit-dataRest" value="${row.dataset.originalDataRest}">
    `;

    cells[4].innerHTML = `
        <button id="trasparente" class="edit-button" onclick="modificaTarga('${targa.trim()}', '${row.dataset.originalDataEm}', '${row.dataset.originalStato}')">
            <img src="images/modificabianca" alt="icona modifica" width="20" height="20">
        </button>
    `;
}



window.modificaTarga = modificaTarga;
window.salvaModifiche = salvaModifiche;
window.annullaModifiche = annullaModifiche;

</script>
</body>
</html>