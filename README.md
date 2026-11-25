# Tab Manager

Tab Manager è una piccola applicazione, personale e locale, per scrivere, archiviare ed esportare in Word (.docx) tabulature per basso e chitarra e parti per voce.

L'app non cerca e non genera le tabulature da sola. È l'utente che le scrive o le incolla su un foglio che è già il documento Word finale, e l'app le salva in un archivio e le esporta in un documento Word pronto da stampare o da modificare.

## Installazione (Windows 10/11)

1. In questa pagina di GitHub: **Code → Download ZIP**.
2. **Prima di estrarre**: tasto destro sullo ZIP → **Proprietà** → spunta **Sblocca** → **OK**.
   Windows segna i file scaricati da Internet e, con Smart App Control attivo, impedirebbe di avviare l'installazione.
3. Tasto destro sullo ZIP → **Estrai tutto**, nella cartella in cui vuoi tenere l'app.
4. Nella cartella estratta, doppio clic su **`Installa Tab Manager.cmd`**.
   Una finestra mostra i passi; ci vogliono alcuni minuti. L'installazione:
   - installa **PHP** e **Composer** (con [php.new](https://php.new)) e **Node.js** (con `winget`), se mancano: per Node.js Windows chiede una conferma;
   - scarica le librerie, compila l'interfaccia e prepara il database con tre brani di prova;
   - crea il collegamento **Tab Manager** sul desktop e nella cartella dell'app, e apre l'app.
5. Da qui in poi si usa solo il collegamento **Tab Manager**: si apre in una finestra dedicata.

## I tuoi brani: dove sono e come non perderli

I brani stanno in **`Documenti\Tab Manager\database.sqlite`**, fuori dalla cartella dell'app. Puoi cancellare, riscaricare, aggiornare o disinstallare l'app: i brani restano lì. Se la cartella Documenti è sincronizzata con OneDrive, finiscono anche nel cloud.

**Copie automatiche**, in `Documenti\Tab Manager\Backup`:
- una al giorno, quando apri Tab Manager;
- una prima di ogni aggiornamento.

Si tengono le ultime 30.

**Backup da tenere al sicuro**: nell'elenco dei brani, pulsante **Backup** (la scatola con la freccia, accanto alla ricerca). Scarica uno ZIP con:
- tutti i brani in Word (`.docx`), da aprire e stampare anche senza Tab Manager;
- il file `database.sqlite`, che serve per il ripristino;
- un `LEGGIMI.txt` con le istruzioni.

Conserva lo ZIP su una chiavetta, nel cloud o mandalo per email a te stesso.

**Ripristinare i brani** (da un backup, da una copia automatica o su un altro computer):
1. chiudi Tab Manager;
2. apri `Documenti\Tab Manager` (su un computer nuovo: prima installa Tab Manager e aprilo una volta);
3. copia lì il `database.sqlite` del backup, al posto di quello che c'è. Se usi una copia automatica della cartella `Backup`, rinominala in `database.sqlite`;
4. riapri Tab Manager.

**Passare a un altro computer**: installa Tab Manager sul computer nuovo, poi ripristina l'ultimo backup come sopra (oppure copia tutta la cartella `Documenti\Tab Manager`).

## Aggiornamento

Scarica il nuovo ZIP, sbloccalo ed estrailo (anche al posto della cartella vecchia), poi doppio clic su **`Installa Tab Manager.cmd`**. I brani restano: prima di aggiornare l'installazione ne fa una copia in `Documenti\Tab Manager\Backup`.

Chi aveva una versione precedente con i brani in `%LOCALAPPDATA%\TabManager`: l'installazione li sposta da sola in `Documenti\Tab Manager`. Il file vecchio resta, rinominato `database.sqlite.spostato`, e si può cancellare dopo aver controllato che i brani ci sono.

## Disinstallare

1. Nella cartella dell'app, doppio clic su **`Disinstalla Tab Manager.cmd`** e conferma con **S**.
   Chiude l'app e cancella i collegamenti **Tab Manager**.
2. **I brani non vengono mai cancellati**: restano in `Documenti\Tab Manager`, con le copie di sicurezza. Se reinstalli, li ritrovi com'erano. Se vuoi davvero eliminarli, cancella tu quella cartella.
3. Alla fine si apre Esplora file con la cartella dell'app selezionata: chiudi la finestra nera e **cancella la cartella**.

PHP, Composer e Node.js restano installati, perché possono servire ad altri programmi. Se vuoi toglierli:
- **PHP e Composer**: cancella la cartella `%USERPROFILE%\.config\herd-lite` (incolla il percorso nella barra di Esplora file), poi menu Start → **Modifica le variabili d'ambiente relative al tuo account** → **Path** → Modifica → togli la riga che finisce con `\.config\herd-lite\bin`;
- **Node.js**: Impostazioni → App → App installate → **Node.js** → Disinstalla.

## Se qualcosa non va

- **Non si apre niente cliccando `Installa Tab Manager.cmd`, oppure Windows dice che è stato bloccato**: lo ZIP non era stato sbloccato. Cancella la cartella estratta e ripeti dal punto 2.
- **L'installazione si ferma con un errore**: la finestra resta aperta con il messaggio. Fai una foto e mandala a chi ti ha dato l'app.
- **Il collegamento non apre la finestra**: rilancia `Installa Tab Manager.cmd`.
