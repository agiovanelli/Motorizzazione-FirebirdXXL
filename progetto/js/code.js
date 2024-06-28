function creazioneQueryveicolo(){
    var numTelaio = document.getElementById("numTelaio").value;
    var marca = document.getElementById("marca").value;
    var modello = document.getElementById("modello").value;
    var dataProd = document.getElementById("dataProd").value;

    var query = document.getElementById("provaRicerca");
    provaRicerca.innerHTML = dbveicoli(numTelaio, marca, modello, dataProd);

    return false;
}

function creazioneQuerytarga(){
    var targa = document.getElementById("targa").value;
    var dataEmissione = document.getElementById("dataEm").value;

    var targaStato = document.getElementsByClassName("attivita");
    var targaEsito;
    if(targaStato[0].checked){
        targaEsito = 1;
    }else if(targaStato[1].checked){
        targaEsito = 0;
    }

    var dataRestituita = document.getElementById("dataRest").value;

    //Valore targa attiva = 1, restituita = 0
    //Valore revisione superata = 1, non superata = 0
    var query = document.getElementById("provaRicerca");
    provaRicerca.innerHTML = dbTarga(targa, dataEmissione, targaEsito, dataRestituita);

    return false;
}

function creazioneQueryrevisione(){
    var numRevisione = document.getElementById("numRev").value;
    var dataRevisione = document.getElementById("dataRev").value;
    var revStato = document.getElementsByClassName("attivita2");
    var revEsito;
    if(revStato[0].checked){
        revEsito = 1;
    }else if (targaStato[1].checked){
        revEsito = 0;
    }

    //Valore targa attiva = 1, restituita = 0
    //Valore revisione superata = 1, non superata = 0
    var query = document.getElementById("provaRicerca");
    provaRicerca.innerHTML = dbRevisione(numRevisione, dataRevisione, revEsito);

    return false;
}

function dbveicoli(num, mar, mod, dp){
    var q = "SELECT * FROM veicolo WHERE numeroTelaio = '" + num + "' OR marca = '" + mar
            + "' OR modello = " + mod + "' OR dataProd = '" + dp + "'";

    return q;
}

function dbTarga(tar, de, te){
    var q = "SELECT * FROM targa WHERE targa = '" + tar + "' OR dataEM = '" + de + "'";

    return q;
}

function dbRevisione(numr, dr, re){
    var q = "SELECT * FROM revisione WHERE numero = '" + numr + "' OR dataRev = '" + dr
            + "' OR esito = " + re + "'";

    return q;
}

function spento(radio){
    var bottone = document.getElementsByName(radio.name);
    var divOscurato = document.getElementById(opzioneVisuale);

    for (var i = 0; i < bottone.length; i++) {
        if (bottone[i] !== radio) {
            bottone[i].checked = false;
        }
    }

    if(radio.name == "attivita" && radio.value == 0 && radio.checked){
        opzioneVisuale.style.display = 'inline';
    }else{
        opzioneVisuale.style.display = 'none';
    }
}

function spento2(radio){
    var bottone = document.getElementsByName(radio.name);
    var divOscurato = document.getElementById(opzioneVisuale2);

    for (var i = 0; i < bottone.length; i++) {
        if (bottone[i] !== radio) {
            bottone[i].checked = false;
        }
    }

    if(radio.name == "Ass" && radio.value == "negativo" && radio.checked){
        opzioneVisuale2.style.display = 'inline';
    }else{
        opzioneVisuale2.style.display = 'none';
    }
}

function aggiuntaModifica(value){
    localStorage.setItem('x', value);
    window.location.href = "aggiungiModifica.php";
}

function visualeAggiungi(){
    let tipo='';
    const x = localStorage.getItem('x');
    var veicolo = document.getElementById(visualVeicolo);
    var targa = document.getElementById(visualTarga);
    var revisione = document.getElementById(visualRevisione);

    if(x == 1){
        visualVeicolo.style.display = 'block';
        tipo = 'veicolo';
    }else if(x == 2){
        visualTarga.style.display = 'block';
        tipo='targa';
    }else if(x == 3){
        visualRevisione.style.display = 'block';
        tipo='revisione';
    }else{
        alert("Per qualche motivo sei finito in questa pagina senza motivo, torna alla home");
        window.location.href = "home.php";
    }

    console.log('Valore di tipo:', tipo);

    document.getElementById('tipoInput').value = tipo;
}

function inviaForm() {
    // Aggiungi il valore di tipo al form
    document.getElementById('tipoInput').value = tipo;

    // Invia il form
    document.getElementById('formRicerca').submit();
}

let sortDirections = [];

function sortVeicolo(columnIndex) {
    const table = document.getElementById("TableVeicolo");
    const tbody = table.tBodies[0];
    const rowsArray = Array.from(tbody.rows);

    // Initialize sort direction if not already set
    if (!sortDirections[columnIndex]) {
        sortDirections[columnIndex] = 'asc';
    }

    const currentSortDirection = sortDirections[columnIndex];

    rowsArray.sort((rowA, rowB) => {
        const cellA = rowA.cells[columnIndex].innerText.toLowerCase();
        const cellB = rowB.cells[columnIndex].innerText.toLowerCase();

        if (cellA < cellB) {
            return currentSortDirection === 'asc' ? -1 : 1;
        }
        if (cellA > cellB) {
            return currentSortDirection === 'asc' ? 1 : -1;
        }
        return 0;
    });

    // Toggle sort direction for next click
    sortDirections[columnIndex] = currentSortDirection === 'asc' ? 'desc' : 'asc';

    // Remove all existing rows
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }

    // Append sorted rows
    tbody.append(...rowsArray);
}

function sortTarga(columnIndex) {
    const table = document.getElementById("TableTarga");
    const tbody = table.tBodies[0];
    const rowsArray = Array.from(tbody.rows);

    // Initialize sort direction if not already set
    if (!sortDirections[columnIndex]) {
        sortDirections[columnIndex] = 'asc';
    }

    const currentSortDirection = sortDirections[columnIndex];

    rowsArray.sort((rowA, rowB) => {
        const cellA = rowA.cells[columnIndex].innerText.toLowerCase();
        const cellB = rowB.cells[columnIndex].innerText.toLowerCase();

        if (cellA < cellB) {
            return currentSortDirection === 'asc' ? -1 : 1;
        }
        if (cellA > cellB) {
            return currentSortDirection === 'asc' ? 1 : -1;
        }
        return 0;
    });

    // Toggle sort direction for next click
    sortDirections[columnIndex] = currentSortDirection === 'asc' ? 'desc' : 'asc';

    // Remove all existing rows
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }

    // Append sorted rows
    tbody.append(...rowsArray);
}

function sortRevisione(columnIndex) {
    const table = document.getElementById("TableRevisione");
    const tbody = table.tBodies[0];
    const rowsArray = Array.from(tbody.rows);

    // Initialize sort direction if not already set
    if (!sortDirections[columnIndex]) {
        sortDirections[columnIndex] = 'asc';
    }

    const currentSortDirection = sortDirections[columnIndex];

    rowsArray.sort((rowA, rowB) => {
        const cellA = rowA.cells[columnIndex].innerText.toLowerCase();
        const cellB = rowB.cells[columnIndex].innerText.toLowerCase();

        if (cellA < cellB) {
            return currentSortDirection === 'asc' ? -1 : 1;
        }
        if (cellA > cellB) {
            return currentSortDirection === 'asc' ? 1 : -1;
        }
        return 0;
    });

    // Toggle sort direction for next click
    sortDirections[columnIndex] = currentSortDirection === 'asc' ? 'desc' : 'asc';

    // Remove all existing rows
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }

    // Append sorted rows
    tbody.append(...rowsArray);
}
