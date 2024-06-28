<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="author" content="Luca Ciancio, Alessio Giovanelli, Fabio Izzo">
  <meta http-equiv="Cache-control" content="no-cache">
  <title>Veicoli</title>
  <link rel="icon" href="images/fenicebianca" type="image/png">
  <link rel="stylesheet" href="css/style.css">
  <script src="js/code.js"></script>
  <script>
    function confermaEliminazione(veicolo) {
      const { numeroTelaio, marca, modello, dataProd, targa_attuale } = veicolo;
      const info = `Numero telaio: ${numeroTelaio}\nMarca: ${marca}\nModello: ${modello}\nData di produzione: ${dataProd}\nTarga Attuale: ${targa_attuale !== 'No' ? targa_attuale : 'N/A'}`;
      if (confirm(`Sei sicuro di voler eliminare il seguente veicolo?\n\n${info}`)) {
        document.getElementById('delete_id').value = numeroTelaio;
        document.getElementById('deleteForm').submit();
      }
    }

    function abilitaModifica(bottone, veicolo) {
    const riga = bottone.closest('tr');
    const { numeroTelaio, marca, modello, dataProd, targa_attuale, dataEmissioneTarga } = veicolo;
    riga.innerHTML = `
      <td><input type="text" id="modNumeroTelaio" value="${numeroTelaio}" /></td>
      <td><input type="text" id="modMarca" value="${marca}" /></td>
      <td><input type="text" id="modModello" value="${modello}" /></td>
      <td><input type="text" id="modDataProd" value="${dataProd}" /></td>
      <td>${targa_attuale}</td>
      <td class="imgcentr">
        <button id="trasparente" onclick='confermaModifica(this, ${JSON.stringify(veicolo)})'><img src="images/spunta" alt="icona spunta" width="20" height="20"></button>
        <button id="trasparente" onclick='annullaModifica()'><img src="images/croce" alt="icona croce" width="20" height="20"></button>
      </td>
      <td></td>
    `;
    // Passiamo la data di emissione della targa al JavaScript
    riga.dataset.dataEmissioneTarga = dataEmissioneTarga;
  }

  function confermaModifica(bottone, veicoloOriginale) {
    const numeroTelaio = document.getElementById('modNumeroTelaio').value;
    const marca = document.getElementById('modMarca').value;
    const modello = document.getElementById('modModello').value;
    const dataProd = document.getElementById('modDataProd').value;
    const targa_emissione = veicoloOriginale.dataEmissioneTarga;
    const dataOggi = new Date().toISOString().split('T')[0];

    if (numeroTelaio.length !== 17) {
        alert('Il numero del telaio deve essere composto da 17 caratteri.');
        return;
    }

    if (targa_emissione !== null && new Date(dataProd) > new Date(targa_emissione)) {
        alert(`La data di produzione non può essere successiva alla data di emissione della targa.\n\nDettagli:\nNumero telaio: ${numeroTelaio}\nMarca: ${marca}\nModello: ${modello}\nData di produzione: ${dataProd}\nData di emissione della targa: ${targa_emissione}`);
        return;
    }

    if (targa_emissione === null && new Date(dataProd) > new Date(dataOggi)) {
        alert(`La data di produzione non può essere successiva alla data odierna.\n\nDettagli:\nNumero telaio: ${numeroTelaio}\nMarca: ${marca}\nModello: ${modello}\nData di produzione: ${dataProd}\nData odierna: ${dataOggi}`);
        return;
    }

    if (confirm(`Sei sicuro di voler modificare il seguente veicolo?\n\nNumero telaio: ${numeroTelaio}\nMarca: ${marca}\nModello: ${modello}\nData di produzione: ${dataProd}`)) {
        document.getElementById('modifica_id').value = veicoloOriginale.numeroTelaio;
        document.getElementById('modNumeroTelaioForm').value = numeroTelaio;
        document.getElementById('modMarcaForm').value = marca;
        document.getElementById('modModelloForm').value = modello;
        document.getElementById('modDataProdForm').value = dataProd;
        document.getElementById('modificaForm').submit();
    } else {
        annullaModifica();
    }
}
    function annullaModifica() {
      window.location.reload();
    }
  </script>
</head>
<body>

<?php 
include 'html/veicolo.html';
include 'html/footer.html'; 
include 'config.php'; // Include il file di configurazione del database

// Funzione per eliminare un veicolo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];
    deleteVeicolo($deleteId);
}

// Funzione per modificare un veicolo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifica_id'])) {
    $modificaId = $_POST['modifica_id'];
    $numeroTelaio = $_POST['modNumeroTelaio'];
    $marca = $_POST['modMarca'];
    $modello = $_POST['modModello'];
    $dataProd = $_POST['modDataProd'];
    modificaVeicolo($modificaId, $numeroTelaio, $marca, $modello, $dataProd);
}

function deleteVeicolo($numeroTelaio) {
    global $conn;

    // Elimina il veicolo dal database
    $sql = "DELETE FROM veicolo WHERE numeroTelaio = :numeroTelaio";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':numeroTelaio', $numeroTelaio, PDO::PARAM_STR);
    $stmt->execute();

    // Elimina le eventuali relazioni correlate
    $sql2 = "DELETE FROM targaattiva WHERE veicolo = :numeroTelaio";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bindValue(':numeroTelaio', $numeroTelaio, PDO::PARAM_STR);
    $stmt2->execute();
}

function modificaVeicolo($modificaId, $numeroTelaio, $marca, $modello, $dataProd) {
    global $conn;

    // Modifica il veicolo nel database
    $sql = "UPDATE veicolo SET numeroTelaio = :numeroTelaio, marca = :marca, modello = :modello, dataProd = :dataProd WHERE numeroTelaio = :modificaId";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':numeroTelaio', $numeroTelaio, PDO::PARAM_STR);
    $stmt->bindValue(':marca', $marca, PDO::PARAM_STR);
    $stmt->bindValue(':modello', $modello, PDO::PARAM_STR);
    $stmt->bindValue(':dataProd', $dataProd, PDO::PARAM_STR);
    $stmt->bindValue(':modificaId', $modificaId, PDO::PARAM_STR);
    $stmt->execute();
}

function getVeicoliFromDatabase($searchNumeroTelaio = '', $searchMarca = '', $searchModello = '', $searchDataProd = '')
{
    global $conn;
    $sql = "SELECT v.*, IFNULL(ta.targa, 'No') AS targa_attuale, tr.dataEM AS dataEmissioneTarga
            FROM veicolo v
            LEFT JOIN targaattiva ta ON v.numeroTelaio = ta.veicolo
            LEFT JOIN targa tr ON ta.targa = tr.targa
            WHERE 1";

    if (!empty($searchNumeroTelaio)) {
        $sql .= " AND v.numeroTelaio LIKE :searchNumeroTelaio";
    }

    if (!empty($searchMarca)) {
        $sql .= " AND v.marca LIKE :searchMarca";
    }

    if (!empty($searchModello)) {
        $sql .= " AND v.modello LIKE :searchModello";
    }

    if (!empty($searchDataProd)) {
        $sql .= " AND v.dataProd LIKE :searchDataProd";
    }

    $sql .= " ORDER BY v.numeroTelaio";

    $stmt = $conn->prepare($sql);

    if (!empty($searchNumeroTelaio)) {
        $searchNumeroTelaio = '%' . $searchNumeroTelaio . '%';
        $stmt->bindParam(':searchNumeroTelaio', $searchNumeroTelaio, PDO::PARAM_STR);
    }

    if (!empty($searchMarca)) {
        $searchMarca = '%' . $searchMarca . '%';
        $stmt->bindParam(':searchMarca', $searchMarca, PDO::PARAM_STR);
    }

    if (!empty($searchModello)) {
        $searchModello = '%' . $searchModello . '%';
        $stmt->bindParam(':searchModello', $searchModello, PDO::PARAM_STR);
    }

    if (!empty($searchDataProd)) {
        $searchDataProd = '%' . $searchDataProd . '%';
        $stmt->bindParam(':searchDataProd', $searchDataProd, PDO::PARAM_STR);
    }

    $stmt->execute();

    $veicoli = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $veicoli[] = $row;
    }

    return $veicoli;
}

$searchNumeroTelaio = isset($_GET['searchNumeroTelaio']) ? $_GET['searchNumeroTelaio'] : '';
$searchMarca = isset($_GET['searchMarca']) ? $_GET['searchMarca'] : '';
$searchModello = isset($_GET['searchModello']) ? $_GET['searchModello'] : '';
$searchDataProd = isset($_GET['searchDataProd']) ? $_GET['searchDataProd'] : '';

$veicoli = getVeicoliFromDatabase($searchNumeroTelaio, $searchMarca, $searchModello, $searchDataProd);
?>

<div id="contenuto" class="scorrevole">
  <div class="centrato">
    <table class="tabella" id="TableVeicolo">
      <thead>
        <tr class="testata">
          <th>Numero telaio <img onclick="sortVeicolo(0)" src="images/sort" alt="icona sort" class="sort" id="cambioImmagine"></th>
          <th>Marca <img onclick="sortVeicolo(1)" src="images/sort" alt="icona sort" class="sort" id="cambioImmagine"></th>
          <th>Modello <img onclick="sortVeicolo(2)" src="images/sort" alt="icona sort" class="sort" id="cambioImmagine"></th>
          <th>Data di produzione <img onclick="sortVeicolo(3)" src="images/sort" alt="icona sort" class="sort" id="cambioImmagine"></th>
          <th>Targa Attuale</th>
          <th>Modifica</th>
          <th>Elimina</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($veicoli)): ?>
          <?php foreach ($veicoli as $index => $veicolo): ?>
            <tr data-row="<?php echo $index % 2 == 0 ? 'rigaPari' : 'rigaDispari'; ?>" id="<?php echo $index % 2 == 0 ? 'rigaPari' : 'rigaDispari'; ?>">
              <td><?php echo htmlspecialchars($veicolo["numeroTelaio"]); ?></td>
              <td><?php echo htmlspecialchars($veicolo["marca"]); ?></td>
              <td><?php echo htmlspecialchars($veicolo["modello"]); ?></td>
              <td><?php echo htmlspecialchars($veicolo["dataProd"]); ?></td>
              <td><?php echo htmlspecialchars($veicolo["targa_attuale"]); ?></td>
              <td class="imgcentr">
                <button id="trasparente" onclick='abilitaModifica(this, <?php echo json_encode($veicolo); ?>)'><img src="images/modificabianca" alt="icona modifica" width="20" height="20"></button>
              </td>
              <td class="imgcentr">
                <button class="delete-button" onclick='confermaEliminazione(<?php echo json_encode($veicolo); ?>)'><img src="images/eliminabianco" alt="icona elimina" width="20" height="20"></button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7">Nessun veicolo trovato</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<form id="deleteForm" method="post" style="display: none;">
  <input type="hidden" name="delete_id" id="delete_id">
</form>

<form id="modificaForm" method="post" style="display: none;">
  <input type="hidden" name="modifica_id" id="modifica_id">
  <input type="hidden" name="modNumeroTelaio" id="modNumeroTelaioForm">
  <input type="hidden" name="modMarca" id="modMarcaForm">
  <input type="hidden" name="modModello" id="modModelloForm">
  <input type="hidden" name="modDataProd" id="modDataProdForm">
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const searchInputTelaio = document.getElementById('numTelaio');
  const searchInputMarca = document.getElementById('marca');
  const searchInputModello = document.getElementById('modello');
  const searchInputDataProd = document.getElementById('dataProd');

  function performSearch() {
    const searchValueTelaio = searchInputTelaio.value;
    const searchValueMarca = searchInputMarca.value;
    const searchValueModello = searchInputModello.value;
    const searchValueDataProd = searchInputDataProd.value;

    fetch(`veicolo.php?searchNumeroTelaio=${encodeURIComponent(searchValueTelaio)}&searchMarca=${encodeURIComponent(searchValueMarca)}&searchModello=${encodeURIComponent(searchValueModello)}&searchDataProd=${encodeURIComponent(searchValueDataProd)}`)
    .then(response => response.text())
    .then(data => {
      const parser = new DOMParser();
      const newDocument = parser.parseFromString(data, 'text/html');
      const newTableBody = newDocument.querySelector('tbody');
      const tableBody = document.querySelector('tbody');
      tableBody.innerHTML = newTableBody.innerHTML;
    })
    .catch(error => {
      console.error('Error fetching search results:', error);
    });
  }

  searchInputTelaio.addEventListener('input', performSearch);
  searchInputMarca.addEventListener('input', performSearch);
  searchInputModello.addEventListener('input', performSearch);
  searchInputDataProd.addEventListener('input', performSearch);
});
</script>

</body>
</html>
