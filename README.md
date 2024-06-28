# Cos'è FirebirdXXL
Firebird è un sistema della Motorizzazione Civile per gestire i veicoli e le targhe.
XXL perché ci piace fare le cose in grande.

## Come si effettua la ricerca
Sulla destra vi sono tre bottoni: Veicoli, Targhe e Revisioni
Cliccando su ognuno dei pulsanti verrete portati in una pagina dove potrete cercare, ed eventualmente aggiungere, l'oggetto della vostra ricerca.
Sulla sinistra di ogni pagina ci sono dei filtri che vi faciliteranno la ricerca.
È inoltre possibile ordinare in modo crescente cliccando una volta sul simbolo accanto alla nome della colonna indicata e ordinarlo in modo decrescente cliccando due volte sulla stessa icona.

## I dati
Ogni pagina possiede un database con più di mille elementi contenenti le più disparate combinazioni.

## CRUD
Per ogni tabella sono possibili tutte le pratiche CRUD:

C - Creazione di un nuovo dato (veicolo, targa e revisione);

R - Tutte le tabella sono consultabili nelle apposite pagine;

U - Tutti i dati nelle tabelle possono essere modificati (tranne alcune eccezioni che abbiamo volutamente scelto di non poter modificare);

D - Tutti i dati nelle tabelle sono eliminabili.

## Decisioni intraprese

- Nelle pagine di veicolo e revisione non è possibile modificare la voce "Targa Attuale" / "Targa", data la presenza di una tabella apposita;

- Nella pagina di revisione non è possibile modificare la voce "Numero revisione" in quanto è un valore che si aggiorna automaticamente;

- Per ogni pagina nel caso si volesse modificare qualche voce sono stati creati dei controlli per il buon inserimento (logico) delle modifiche.

## Progetto a cura di
Luca Ciancio, 1079291

Alessio Giovanelli, 1081610

Fabio Izzo, 1080579
