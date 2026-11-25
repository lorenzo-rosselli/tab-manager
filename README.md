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

I brani vengono salvati in `%LOCALAPPDATA%\TabManager\database.sqlite`, fuori dalla cartella dell'app.

## Aggiornamento

Scarica il nuovo ZIP, sbloccalo ed estrailo (anche al posto della cartella vecchia), poi doppio clic su **`Installa Tab Manager.cmd`**. I brani restano.

## Disinstallare

1. Nella cartella dell'app, doppio clic su **`Disinstalla Tab Manager.cmd`** e conferma con **S**.
   Chiude l'app e cancella i collegamenti **Tab Manager**.
2. Ti chiede se cancellare anche l'**archivio delle tabulature**: scrivi **SI** per cancellarlo (le tabulature vanno perse), oppure premi **Invio** per tenerlo. Se lo tieni e reinstalli, ritrovi i brani com'erano.
3. Alla fine si apre Esplora file con la cartella dell'app selezionata: chiudi la finestra nera e **cancella la cartella**.

PHP, Composer e Node.js restano installati, perché possono servire ad altri programmi. Se vuoi toglierli:
- **PHP e Composer**: cancella la cartella `%USERPROFILE%\.config\herd-lite` (incolla il percorso nella barra di Esplora file), poi menu Start → **Modifica le variabili d'ambiente relative al tuo account** → **Path** → Modifica → togli la riga che finisce con `\.config\herd-lite\bin`;
- **Node.js**: Impostazioni → App → App installate → **Node.js** → Disinstalla.

## Se qualcosa non va

- **Non si apre niente cliccando `Installa Tab Manager.cmd`, oppure Windows dice che è stato bloccato**: lo ZIP non era stato sbloccato. Cancella la cartella estratta e ripeti dal punto 2.
- **L'installazione si ferma con un errore**: la finestra resta aperta con il messaggio. Fai una foto e mandala a chi ti ha dato l'app.
- **Il collegamento non apre la finestra**: rilancia `Installa Tab Manager.cmd`.
