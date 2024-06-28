<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="author" content="Luca Ciancio, Alessio Giovanelli, Fabio Izzo">
    <meta http-equiv="Cache-control" content="no-cache">
    <title>Revisioni</title>
    <link rel="icon" href="images/fenicebianca" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/code.js"></script>
</head>
<body>

<?php
include 'html/revisione.html';
include 'html/footer.html';
include 'config.php';
include 'get_data_emissione.php';

function getRevisioniOrdinatePerData($targa)
{
    global $conn;

    $sql = "SELECT numero, dataRev FROM revisione WHERE targa = :targa ORDER BY dataRev DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':targa', $targa, PDO::PARAM_STR);
    $stmt->execute();
    
    $revisioni = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $revisioni[] = $row;
    }

    return $revisioni;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_numero']) && isset($_POST['delete_targa'])) {
    $deleteNumero = $_POST['delete_numero'];
    $deleteTarga = $_POST['delete_targa'];
    deleteRevisione($deleteNumero, $deleteTarga);
}

function deleteRevisione($numero, $targa)
{
    global $conn;

    $sql = "DELETE FROM revisione WHERE numero = :numero AND targa = :targa";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':numero', $numero, PDO::PARAM_INT);
    $stmt->bindValue(':targa', $targa, PDO::PARAM_STR);
    $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_numero']) && isset($_POST['update_targa'])) {
    $updateNumero = $_POST['update_numero'];
    $updateTarga = $_POST['update_targa'];
    $updateDataRev = $_POST['update_dataRev'];
    $updateEsito = $_POST['update_esito'];
    $updateMotivazione = $_POST['update_motivazione'];

    updateRevisione($updateNumero, $updateTarga, $updateDataRev, $updateEsito, $updateMotivazione);
}

function updateRevisione($numero, $targa, $dataRev, $esito, $motivazione) {
    global $conn;

    // Ottenere le revisioni ordinate per data
    $revisioni = getRevisioniOrdinatePerData($targa);

    // Trovare la revisione che si sta aggiornando
    $revisioneDaAggiornare = null;
    foreach ($revisioni as $revisione) {
        if ($revisione['numero'] == $numero) {
            $revisioneDaAggiornare = $revisione;
            break;
        }
    }

    // Trovare l'indice della revisione che si sta aggiornando
    $indiceRevisioneDaAggiornare = array_search($revisioneDaAggiornare, $revisioni);

    // Verificare se ci sono revisioni precedenti e confrontare le date
    if ($indiceRevisioneDaAggiornare < count($revisioni) - 1) {
        $revisioneSuccessiva = $revisioni[$indiceRevisioneDaAggiornare + 1];
        $dataRevisioneSuccessiva = strtotime($revisioneSuccessiva['dataRev']);
        $dataRevisioneAttuale = strtotime($dataRev);
        if ($dataRevisioneAttuale < $dataRevisioneSuccessiva) {
            echo "<script>alert('La data della revisione ($dataRev) non può essere precedente alla data della revisione successiva ($revisioneSuccessiva[dataRev]) per la stessa targa.');</script>";
            return;
        }
    }

    // Verificare se ci sono revisioni successive e confrontare le date
    if ($indiceRevisioneDaAggiornare > 0) {
        $revisionePrecedente = $revisioni[$indiceRevisioneDaAggiornare - 1];
        $dataRevisionePrecedente = strtotime($revisionePrecedente['dataRev']);
        $dataRevisioneAttuale = strtotime($dataRev);
        if ($dataRevisioneAttuale > $dataRevisionePrecedente) {
            echo "<script>alert('La data della revisione ($dataRev) non può essere successiva alla data della revisione precedente ($revisionePrecedente[dataRev]) per la stessa targa.');</script>";
            return;
        }
    }

    // Ottieni la data di emissione della targa
    $dataEmissione = getDataEmissione($targa);

    // Verifica se la data di revisione è minore della data di emissione della targa
    if (strtotime($dataRev) < strtotime($dataEmissione)) {
        echo "<script>alert('La data della revisione ($dataRev) non può essere precedente alla data di emissione della targa ($dataEmissione).');</script>";
        return;
    }

    // Esegui l'aggiornamento nel database
    $sql = "UPDATE revisione SET dataRev = :dataRev, esito = :esito, motivazione = :motivazione WHERE numero = :numero AND targa = :targa";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':numero', $numero, PDO::PARAM_INT);
    $stmt->bindValue(':targa', $targa, PDO::PARAM_STR);
    $stmt->bindValue(':dataRev', $dataRev, PDO::PARAM_STR);
    $stmt->bindValue(':esito', $esito, PDO::PARAM_STR);
    $stmt->bindValue(':motivazione', $motivazione, PDO::PARAM_STR);
    $stmt->execute();
}


function getRevisioniFromDatabase($searchTarga = '', $searchDataRev = '', $searchEsito = '', $searchMotivazione = '')
{
    global $conn;

    $sql = "SELECT targa, numero, dataRev, esito, motivazione
            FROM revisione
            WHERE 1";

    if (!empty($searchTarga)) {
        $sql .= " AND targa LIKE :searchTarga";
    }

    if (!empty($searchDataRev)) {
        $sql .= " AND dataRev = :searchDataRev";
    }

    if (!empty($searchEsito)) {
        $sql .= " AND esito = :searchEsito";
    }

    if (!empty($searchMotivazione) && $searchMotivazione != 'NULL') {
        $sql .= " AND motivazione = :searchMotivazione";
    }

    $sql .= " ORDER BY targa";

    $stmt = $conn->prepare($sql);

    if (!empty($searchTarga)) {
        $searchTarga = "%" . $searchTarga . "%";
        $stmt->bindValue(':searchTarga', $searchTarga, PDO::PARAM_STR);
    }

    if (!empty($searchDataRev)) {
        $stmt->bindValue(':searchDataRev', $searchDataRev, PDO::PARAM_STR);
    }

    if (!empty($searchEsito)) {
        $stmt->bindValue(':searchEsito', $searchEsito, PDO::PARAM_STR);
    }

    if (!empty($searchMotivazione) && $searchMotivazione != 'NULL') {
        $stmt->bindValue(':searchMotivazione', $searchMotivazione, PDO::PARAM_STR);
    }

    $stmt->execute();
    
    $revisioni = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $revisioni[] = $row;
    }

    return $revisioni;
}

$searchTarga = isset($_GET['searchTarga']) ? $_GET['searchTarga'] : '';
$searchDataRev = isset($_GET['searchDataRev']) ? $_GET['searchDataRev'] : '';
$searchEsito = isset($_GET['searchEsito']) ? $_GET['searchEsito'] : '';
$searchMotivazione = isset($_GET['searchMotivazione']) ? $_GET['searchMotivazione'] : '';

$revisioni = getRevisioniFromDatabase($searchTarga, $searchDataRev, $searchEsito, $searchMotivazione);

?>

<div id="contenuto" class="scorrevole">
    <div class="centrato">
        <table class="tabella" id="TableRevisione">
            <thead>
                <tr class="testata">
                    <th>Targa <img onclick="sortRevisione(0)" src="images/sort" alt="icona sort" class="sort"></th>
                    <th>Numero Revisione <img onclick="sortRevisione(1)" src="images/sort" alt="icona sort" class="sort"></th>
                    <th>Data ultima revisione <img onclick="sortRevisione(2)" src="images/sort" alt="icona sort" class="sort"></th>
                    <th>Stato</th>
                    <th>Motivazione</th>
                    <th>Modifica</th>
                    <th>Elimina</th>
                </tr>
            </thead>
            <tbody>
    <?php if (!empty($revisioni)): ?>
        <?php foreach ($revisioni as $index => $revisione): ?>
            <tr class="revisione-row" data-row="<?php echo $index % 2 == 0 ? 'rigaPari' : 'rigaDispari'; ?>" id="<?php echo $index % 2 == 0 ? 'rigaPari' : 'rigaDispari'; ?>">
                <td>
                   <?php echo htmlspecialchars($revisione["targa"]); ?>
                </td>
                <td><?php echo htmlspecialchars($revisione["numero"]); ?></td>
                <td><span class="revisione-dataRev"><?php echo htmlspecialchars($revisione["dataRev"]); ?></span><input type="date" class="edit-dataRev" value="<?php echo htmlspecialchars($revisione["dataRev"]); ?>" style="display:none;"></td>
                <td>
                    <span class="revisione-esito"><?php echo htmlspecialchars($revisione["esito"]); ?></span>
                    <select class="edit-esito" style="display:none;">
                        <option value="positivo" <?php echo $revisione["esito"] == 'positivo' ? 'selected' : ''; ?>>Positivo</option>
                        <option value="negativo" <?php echo $revisione["esito"] == 'negativo' ? 'selected' : ''; ?>>Negativo</option>
                    </select>
                </td>
                <td>
                    <span class="revisione-motivazione"><?php echo htmlspecialchars($revisione["motivazione"]); ?></span>
                    <select class="edit-motivazione" style="display:none;">
                        <option value="Pastiglie dei freni usurate" <?php echo $revisione["motivazione"] == 'Pastiglie dei freni usurate' ? 'selected' : ''; ?>>Pastiglie dei freni usurate</option>
                        <option value="Luci non funzionanti" <?php echo $revisione["motivazione"] == 'Luci non funzionanti' ? 'selected' : ''; ?>>Luci non funzionanti</option>
                        <option value="Pneumatici rovinati" <?php echo $revisione["motivazione"] == 'Pneumatici rovinati' ? 'selected' : ''; ?>>Pneumatici rovinati</option>
                        <option value="Emissioni nocive superano i limiti" <?php echo $revisione["motivazione"] == 'Emissioni nocive superano i limiti' ? 'selected' : ''; ?>>Emissioni nocive superano i limiti</option>
                        <option value="Sterzo non funzionante" <?php echo $revisione["motivazione"] == 'Sterzo non funzionante' ? 'selected' : ''; ?>>Sterzo non funzionante</option>
                        <option value="Freno a mano usurato" <?php echo $revisione["motivazione"] == 'Freno a mano usurato' ? 'selected' : ''; ?>>Freno a mano usurato</option>
                        <option value="Parabrezza scheggiato" <?php echo $revisione["motivazione"] == 'Parabrezza scheggiato' ? 'selected' : ''; ?>>Parabrezza scheggiato</option>
                    </select>
                </td>
                <td class="imgcentr">
                    <button id="trasparente" class="edit-button" onclick="abilitaModifica(this)"><img src="images/modificabianca" alt="icona modifica" width="20" height="20"></button>
                    <form class="update-form" method="post" style="display:inline;">
                        <input type="hidden" name="update_targa" value="<?php echo htmlspecialchars($revisione["targa"]); ?>">
                        <input type="hidden" name="update_numero" value="<?php echo htmlspecialchars($revisione["numero"]); ?>">
                        <input type="hidden" name="update_targa" class="update-targa" value="<?php echo htmlspecialchars($revisione["targa"]); ?>">
                        <input type="hidden" name="update_dataRev" class="update-dataRev" value="<?php echo htmlspecialchars($revisione["dataRev"]); ?>">
                        <input type="hidden" name="update_esito" class="update-esito" value="<?php echo htmlspecialchars($revisione["esito"]); ?>">
                        <input type="hidden" name="update_motivazione" class="update-motivazione" value="<?php echo htmlspecialchars($revisione["motivazione"]); ?>">
                        <button type="submit" id="trasparente" class="save-button" style="display:none;"><img src="images/spunta" alt="icona salva" width="20" height="20"></button>
                    </form>
                    <button id="trasparente" class="cancel-button" onclick="annullaModifica(this)" style="display:none;"><img src="images/croce" alt="icona annulla" width="20" height="20"></button>
                </td>
                <td class="imgcentr">
                    <form class="delete-form" method="post" onsubmit="return confermaEliminazione('<?php echo htmlspecialchars($revisione["numero"]); ?>', '<?php echo htmlspecialchars($revisione["targa"]); ?>', '<?php echo htmlspecialchars($revisione["dataRev"]); ?>', '<?php echo htmlspecialchars($revisione["esito"]); ?>', '<?php echo htmlspecialchars($revisione["motivazione"]); ?>')">
                        <input type="hidden" name="delete_numero" value="<?php echo htmlspecialchars($revisione["numero"]); ?>">
                        <input type="hidden" name="delete_targa" value="<?php echo htmlspecialchars($revisione["targa"]); ?>">
                        <button type="submit" class="delete-button"><img src="images/eliminabianco" alt="icona elimina" width="20" height="20"></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7">Nessun risultato trovato.</td>
        </tr>
    <?php endif; ?>
</tbody>

</table>
</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInputTarga = document.getElementById('targa');
        const searchInputDataRev = document.getElementById('dataRev');
        const searchInputEsitoSi = document.getElementById('AssSi');
        const searchInputEsitoNo = document.getElementById('AssNo');
        const searchInputMotivazione = document.getElementById('menu');

        function performSearch() {
            const searchValueTarga = searchInputTarga.value;
            const searchValueDataRev = searchInputDataRev.value;
            const searchValueEsito = searchInputEsitoSi.checked ? 'positivo' : (searchInputEsitoNo.checked ? 'negativo' : '');
            const searchValueMotivazione = searchInputMotivazione.value;

            fetch(`revisione.php?searchTarga=${encodeURIComponent(searchValueTarga)}&searchDataRev=${encodeURIComponent(searchValueDataRev)}&searchEsito=${encodeURIComponent(searchValueEsito)}&searchMotivazione=${encodeURIComponent(searchValueMotivazione)}`)
                .then(response => response.text())
                .then(data => {
                    const parser = new DOMParser();
                    const newDocument = parser.parseFromString(data, 'text/html');
                    const newTableBody = newDocument.querySelector('tbody');
                    const tableBody = document.querySelector('tbody');
                    tableBody.innerHTML = newTableBody.innerHTML;

                    // Aggiungi event listener per la modifica
                    addEditEventListeners();
                })
                .catch(error => {
                    console.error('Error fetching search results:', error);
                });
        }

        searchInputTarga.addEventListener('input', performSearch);
        searchInputDataRev.addEventListener('input', performSearch);
        searchInputEsitoSi.addEventListener('change', performSearch);
        searchInputEsitoNo.addEventListener('change', performSearch);
        searchInputMotivazione.addEventListener('change', performSearch);

        function addEditEventListeners() {
            const editButtons = document.querySelectorAll('.edit-button');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    abilitaModifica(this);
                });
            });

            const saveButtons = document.querySelectorAll('.save-button');
            saveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    confermaModifica(this);
                });
            });

            const cancelButtons = document.querySelectorAll('.cancel-button');
            cancelButtons.forEach(button => {
                button.addEventListener('click', function() {
                    annullaModifica(this);
                });
            });
        }

        // Chiamare addEditEventListeners() all'avvio per aggiungere gli eventi di modifica
        addEditEventListeners();
    });

    function getDataEmissione(targa) {
        return fetch(`get_data_emissione.php?targa=${encodeURIComponent(targa)}`)
            .then(response => response.json())
            .then(data => data.dataEM);
    }

    function abilitaModifica(button) {
    const row = button.closest('tr');
    row.querySelectorAll('span').forEach(span => span.style.display = 'none');
    row.querySelectorAll('input, select').forEach(input => input.style.display = 'block');
    row.querySelector('.edit-button').style.display = 'none';
    row.querySelector('.save-button').style.display = 'inline-block';
    row.querySelector('.cancel-button').style.display = 'inline-block';

    const esitoSelect = row.querySelector('.edit-esito');
    const motivazioneSelect = row.querySelector('.edit-motivazione');

    esitoSelect.addEventListener('change', function() {
        if (esitoSelect.value === 'negativo') {
            motivazioneSelect.style.display = 'block';
            motivazioneSelect.required = true;
        } else {
            motivazioneSelect.style.display = 'none';
            motivazioneSelect.required = false;
            motivazioneSelect.value = '';
        }
    });

    esitoSelect.dispatchEvent(new Event('change'));
}

function annullaModifica(button) {
    const row = button.closest('tr');
    row.querySelectorAll('input, select').forEach(input => input.style.display = 'none');
    row.querySelectorAll('span').forEach(span => span.style.display = 'block');
    row.querySelector('.edit-button').style.display = 'inline-block';
    row.querySelector('.save-button').style.display = 'none';
    row.querySelector('.cancel-button').style.display = 'none';
}

function confermaModifica(button) {
    const row = button.closest('tr');
    const targaInput = row.querySelector('input[name="update_targa"]');
    const dataRevInput = row.querySelector('.edit-dataRev');
    const esitoSelect = row.querySelector('.edit-esito');
    const motivazioneSelect = row.querySelector('.edit-motivazione');

    // Verifica se la data di revisione è valida
    const dataRev = new Date(dataRevInput.value);
    if (isNaN(dataRev.getTime())) {
        alert('La data di revisione non è valida.');
        return;
    }

    // Ottieni la data di emissione della targa
    getDataEmissione(targaInput.value).then(dataEmissione => {
        // Confronta la data di revisione con la data di emissione
        if (dataRev < new Date(dataEmissione)) {
            alert(`La data della revisione (${dataRevInput.value}) non può essere precedente alla data di emissione della targa (${dataEmissione}).`);
            return;
        }

        // Verifica se l'esito è negativo e la motivazione è vuota
        if (esitoSelect.value === 'negativo' && !motivazioneSelect.value) {
            alert('Devi specificare una motivazione per un esito negativo.');
            return;
        }

        // Se il controllo delle date è superato, mostra il prompt di conferma
        const confirmationMessage = `Sei sicuro di voler aggiornare la revisione con i seguenti dati?\n\n` +
                                    `Data Revisione: ${dataRevInput.value}\n` +
                                    `Esito: ${esitoSelect.value}\n` +
                                    `Motivazione: ${motivazioneSelect.value}\n\n` +
                                    `Conferma o Annulla`;

        const confirmed = confirm(confirmationMessage);

        if (confirmed) {
            const updateForm = row.querySelector('.update-form');
            updateForm.querySelector('.update-dataRev').value = dataRevInput.value;
            updateForm.querySelector('.update-esito').value = esitoSelect.value;
            updateForm.querySelector('.update-motivazione').value = motivazioneSelect.value;
            updateForm.submit();
        }
    }).catch(error => {
        console.error('Error fetching data emissione:', error);
    });
}




function confermaEliminazione(numero, targa, dataRev, esito, motivazione) {
    const confirmMessage = `Sei sicuro di voler eliminare la revisione con numero ${numero}, targa ${targa}, data di revisione ${dataRev}, esito ${esito}, motivazione ${motivazione}?`;
    return confirm(confirmMessage);
}
</script>

</body>
</html>


