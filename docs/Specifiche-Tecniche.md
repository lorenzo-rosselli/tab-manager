# Tab Manager – Specifiche tecniche

> Descrizione completa dell'applicazione com'è: basta per ricrearla da zero.
> Da passare all'assistente all'inizio di una nuova sessione di lavoro.

---

## 1. Descrizione del programma

**Tab Manager** è una piccola applicazione web, personale e locale, per **scrivere, archiviare ed esportare in Word (.docx) tabulature per basso e chitarra** e parti per voce.

L'app **non cerca e non genera** le tabulature da sola. È l'utente che le scrive o le incolla su un **foglio** che è già il documento Word finale (stessa pagina, stessa formattazione), e l'app lo archivia e lo esporta in .docx, pronto da stampare o da modificare.

### Obiettivi
- Essere **snella e leggerissima**: poche pagine, nessun framework JavaScript (solo un editor di testo per il foglio, l'impaginazione del foglio in pagine A4 e poche righe per il tema e il tasto Esc), nessun servizio esterno.
- Usare un solo utente, sul proprio computer, senza login.
- Aprirsi come un programma: collegamento sul desktop con la sua icona e finestra dedicata (vedi sezione 2, "Avvio come app desktop").
- **Non perdere mai i brani**: archivio fuori dalla cartella dell'app (in Documenti), copie automatiche e backup ZIP scaricabile (vedi sezione 2, "Archivio dei brani e copie di sicurezza").
- Servire anche come **progetto di studio** per imparare le basi di Laravel.

### Cosa NON fa (fuori dallo scopo)
- Non scarica le tabulature da siti esterni (niente scraping: ci sono problemi legali e di diritto d'autore).
- Non usa l'intelligenza artificiale per generare le note musicali.
- Non trascrive dall'audio.
- Non gestisce più utenti: niente registrazione né login.

---

## 2. Ambiente di sviluppo

| Elemento | Valore |
|---|---|
| Sistema operativo | Windows 11 (con Smart App Control attivo) |
| PHP | 8.5.x in **WSL** (Ubuntu), usato dall'app e per lo sviluppo; il PHP di **php.new** (Herd Lite, `C:\Users\lorer\.config\herd-lite\bin`) è installato ma Smart App Control lo blocca |
| Composer | 2.8.x (in WSL) |
| Laravel Installer | 5.33.x |
| Node.js / npm | Node v26 (installazione Windows) |
| Editor | Visual Studio Code |
| Terminale | Windows PowerShell 5.1 (consigliato PowerShell 7) |
| Cartella progetto | `D:\Workspaces\Rosse Production\Tab Manager` (repository GitHub `lorenzo-rosselli/tab-manager`, ramo `main`) |

### Struttura della cartella del progetto
Oltre alle cartelle standard di Laravel (`app`, `bootstrap`, `config`, `database`, `lang`, `public`, `resources`, `routes`, `storage`), che restano dove Laravel se le aspetta:
- `docs/`: questo documento, `Specifiche-Tecniche.md`;
- `windows/`: gli script per Windows, cioè avvio in finestra dedicata, collegamenti, installazione, aggiornamento automatico e disinstallazione (vedi "Avvio come app desktop");
- nella cartella principale: `README.md` (istruzioni per chi installa l'app, mostrate da GitHub), `Installa Tab Manager.cmd` (il file da cliccare per installare o aggiornare), `Disinstalla Tab Manager.cmd` (il file da cliccare per disinstallare) e il collegamento `Tab Manager.lnk` (creato dall'installazione, non su git).

### Note sull'ambiente
- **Smart App Control** blocca i `php.exe` non firmati, sia quelli di windows.php.net sia quello di php.new (errore "bloccato dai criteri di Device Guard"): decide in base alla "reputazione" del file e può cambiare idea in qualsiasi momento. Non si possono aggiungere eccezioni per singole app.
- Per questo su questo computer **PHP gira in WSL** (Ubuntu, con PHP 8.5, Composer e Laravel Installer), che Smart App Control non controlla: lo usa l'app (vedi "Avvio come app desktop") e si usa per i comandi `php` e `composer`, lanciati dal terminale di Ubuntu nella cartella del progetto (`cd "/mnt/d/Workspaces/Rosse Production/Tab Manager"`). Il PHP di WSL non ha l'estensione `gd`, richiesta da PHPWord solo per le immagini: i comandi di Composer che risolvono le dipendenze vogliono `--ignore-platform-req=ext-gd`.
- **PowerShell 5.1 non supporta `&&`**: i comandi vanno eseguiti uno alla volta, oppure si usa PowerShell 7.
- La **Execution Policy** è stata impostata a `RemoteSigned` per l'utente corrente, così `npm` funziona.
- Il percorso `Rosse Production\Tab Manager` contiene spazi: nei comandi va scritto **tra virgolette**.
- Per lavorare servono due terminali: `php artisan serve` dal terminale di Ubuntu (l'app su `http://127.0.0.1:8000`, raggiungibile da Windows; con `DB_DATABASE="/mnt/c/Users/lorer/Documents/Tab Manager/database.sqlite"` davanti al comando per usare l'archivio vero) e `npm run dev` da Windows (Vite: CSS e JavaScript).

### Avvio come app desktop (finestra dedicata)
Per l'uso di tutti i giorni l'app si apre dal collegamento **"Tab Manager"** (sul desktop e nella cartella del progetto), senza terminali: una finestra tutta sua, senza barra degli indirizzi, schede o menu, con titolo e icona di Tab Manager (anche nella barra delle applicazioni).
- **Perché non un programma compilato** (per esempio con NativePHP/Electron): un eseguibile costruito da noi non sarebbe firmato e Smart App Control lo bloccherebbe. Si usa invece Microsoft Edge, già installato e firmato, in "modalità app" (`--app=`), che mostra solo la pagina. Nessuna libreria in più.
- File nella cartella `windows/` del progetto (oltre a `setup.ps1`, `update.ps1` e `uninstall.ps1`, vedi "Installazione su un altro computer", "Aggiornamento automatico" e "Disinstallazione"):
  - `tab-manager.ps1`: script di avvio. Per prima cosa controlla gli aggiornamenti (vedi "Aggiornamento automatico"); il parametro `-SkipUpdate` salta il controllo. **Quale PHP**: se c'è WSL con PHP 8.4.1 o più recente (controllo con `wsl.exe --exec php -r ...`), usa sempre quello, così l'app si apre anche quando Smart App Control blocca i `php.exe`; altrimenti il PHP di php.new (`%USERPROFILE%\.config\herd-lite\bin\php.exe`) anche se sul computer c'è un altro PHP, e solo se manca quello nel PATH. Con WSL i comandi partono con `wsl.exe --cd <cartella> --exec php ...`; il percorso dell'archivio nel `.env` (`C:/Users/...`) viene tradotto per Linux (`/mnt/c/Users/...`) e passato a PHP con `env DB_DATABASE=...` (una variabile d'ambiente vince sul `.env`); WSL inoltra a Windows la porta aperta su 127.0.0.1. **Velocità** (con il PHP di WSL leggere e scrivere i file di Windows è lento): la sessione di Laravel va in un file (`SESSION_DRIVER=file`, in `storage\framework\sessions`) invece che nel database, perché Laravel la salva a ogni pagina e una scrittura su SQLite è molto più lenta; la variabile la passa lo script (con WSL nell'`env`, senza WSL in `$env:`), il `.env` non cambia. Non nel cookie: dopo un errore di validazione la sessione contiene tutto il foglio, troppo grande per un cookie. Il server parte con OPcache senza ricontrollo dei file (`-d opcache.enable_cli=1 -d opcache.validate_timestamps=0`: ogni file PHP si legge una volta sola finché l'app resta aperta, dopo una modifica al codice basta riaprirla) e con una cache dei percorsi più grande e più lunga (`-d realpath_cache_size=4096K -d realpath_cache_ttl=3600`). Poi trova Edge; avvia di nascosto il server web integrato di PHP sulla **porta 8765** (`php -S 127.0.0.1:8765` dalla cartella `public` con il router di Laravel, come fa `php artisan serve`; porta diversa dalla 8000 dello sviluppo), aspetta che risponda (fino a 30 secondi: la prima volta WSL impiega qualche secondo ad accendersi) e apre Edge con `--app=http://127.0.0.1:8765` e un profilo tutto suo (`--user-data-dir=%LOCALAPPDATA%\TabManager\Edge`, finestra 1280 × 900). Quando non resta nessun processo di Edge con quel profilo (finestra chiusa), spegne il server (con WSL anche con `pkill` dentro Linux, perché chiudere `wsl.exe` non basta sempre). Se il server è già acceso (seconda finestra), apre solo la finestra. Subito dopo aver aperto la finestra lancia in background, senza aspettarlo, `php artisan tab:backup --daily` (copia di sicurezza del giorno, vedi "Archivio dei brani e copie di sicurezza"; un suo errore non blocca l'app), così l'app compare senza attendere la copia. Gli errori (PHP o Edge non trovati, interfaccia non compilata, server che non parte) compaiono in una finestra di messaggio;
  - `install-shortcut.ps1`: crea il collegamento `Tab Manager.lnk` sul desktop e nella cartella del progetto (da eseguire una volta, e di nuovo se si sposta la cartella del progetto, con `powershell -NoProfile -File windows\install-shortcut.ps1`: funziona sia da PowerShell sia dal Prompt dei comandi, dove scrivere solo il nome del file `.ps1` lo aprirebbe nel Blocco note). Il collegamento lancia `conhost.exe --headless powershell.exe -NoProfile -ExecutionPolicy Bypass -File windows\tab-manager.ps1`, cioè lo script senza nessuna finestra del terminale (`-ExecutionPolicy Bypass` vale solo per quell'avvio: l'app parte anche dove PowerShell non esegue script, impostazione predefinita `Restricted`), con l'icona `windows\tab-manager.ico`. Il collegamento contiene percorsi assoluti di questo computer, quindi quello nella cartella del progetto è nel `.gitignore` (`/Tab Manager.lnk`);
  - `tab-manager.ico`: l'icona (16–256 px).
- `setup.ps1`, `uninstall.ps1` e `update.ps1`, che girano in una finestra del terminale visibile, all'inizio disattivano la **Modifica rapida** di quella finestra (`SetConsoleMode` di `kernel32.dll` tramite `Add-Type`: toglie `ENABLE_QUICK_EDIT_MODE`), perché con la modifica rapida un clic nella finestra mette in pausa lo script finché non si preme un tasto. Se non riesce, proseguono lo stesso. Inoltre i programmi esterni (`wsl.exe`, `composer`, `npm`) partono con l'input vuoto (`$null | & ...`), così non restano mai in attesa della tastiera.
- Gli script sono salvati in UTF-8 **con BOM**: Windows PowerShell 5.1 senza BOM leggerebbe male le lettere accentate. Girano con la Execution Policy `RemoteSigned` già impostata (sono file locali).
- L'interfaccia usa i file compilati da **`npm run build`** (cartella `public/build`), da rifare dopo ogni modifica al codice. Se `npm run dev` è acceso, la finestra usa quello. Il file `public/hot` rimasto da un `npm run dev` chiuso male (server di Vite spento) viene tolto dallo script, altrimenti le pagine resterebbero senza stile.

### Installazione su un altro computer
Su un computer senza l'ambiente di sviluppo l'app si installa con un doppio clic, senza comandi (istruzioni nel `README.md`):
1. su GitHub **Code → Download ZIP**;
2. prima di estrarre: tasto destro sullo ZIP → Proprietà → **Sblocca**. Windows segna i file scaricati da Internet ("Mark of the Web") e i file estratti ereditano il segno; con Smart App Control attivo i file eseguibili segnati (`.cmd`, `.ps1`, `.lnk`, …) vengono bloccati senza possibilità di aggirare il blocco. Un `.exe` costruito da noi sarebbe bloccato comunque, perché non firmato: per questo l'"eseguibile" è un file di comandi;
3. "Estrai tutto" e doppio clic su **`Installa Tab Manager.cmd`** (nella cartella principale del progetto). Il file avvia `windows\setup.ps1` con `-ExecutionPolicy Bypass` (solo per quell'avvio) e alla fine resta aperto con `pause`, così si leggono eventuali errori.

`windows\setup.ps1` (installazione **e** aggiornamento: si può rilanciare quando si vuole):
- toglie il segno di Internet ai file dell'app (`Unblock-File`), nel caso lo ZIP non fosse stato sbloccato;
- **PHP e Composer**: se c'è WSL con PHP 8.4.1 o più recente, usa PHP e Composer di WSL, come lo script di avvio (se in WSL manca Composer si ferma e dice di installarlo). I comandi partono con `wsl.exe --cd <cartella dell'app> --exec ...`: `php` riceve il percorso dell'archivio tradotto per Linux (`env DB_DATABASE=/mnt/c/...`), `composer` l'opzione `--ignore-platform-req=ext-gd` (il PHP di WSL di solito non ha `gd`, che PHPWord chiede solo per le immagini). Senza WSL l'app usa **sempre il PHP di php.new**, anche se sul computer c'è già un altro PHP (per esempio quello di XAMPP), così le versioni non vanno in conflitto. Se `%USERPROFILE%\.config\herd-lite\bin\php.exe` o Composer mancano, li installa con il comando ufficiale di php.new (`https://php.new/install/windows/8.5`), in un PowerShell separato: php.new usa una cartella sua e non tocca l'altro PHP. Per i comandi dell'installazione quella cartella è messa **per prima nel PATH** (solo in quel processo, le impostazioni del PC non cambiano). Controlla che PHP sia almeno **8.4**, la versione minima richiesta dalle librerie in `composer.lock`;
- **Node.js**: se manca lo installa con `winget install OpenJS.NodeJS.LTS` (Windows chiede una conferma); serve solo a compilare l'interfaccia;
- **`.env`**: solo se non esiste, lo crea copiando `.env.example`;
- **archivio dei brani** in **`Documenti\Tab Manager\database.sqlite`** (cartella Documenti da `[Environment]::GetFolderPath('MyDocuments')`, quindi anche se è su OneDrive), vedi "Archivio dei brani e copie di sicurezza":
  - crea `Documenti\Tab Manager` e `Documenti\Tab Manager\Backup`;
  - scrive nel `.env` `DB_DATABASE` con il percorso di Documenti (barre `/`, tra apici perché contiene spazi), subito dopo `DB_CONNECTION`;
- `composer install`, `php artisan key:generate` (solo se manca la chiave), `npm install`, `npm run build`;
- **database**: se il file non esiste lo crea, poi `migrate --force` e `db:seed --force` (i tre brani di prova); se esiste, prima `php artisan tab:backup --label="prima dell'aggiornamento"` (copia in `Backup`), poi solo `migrate --force` (i brani restano);
- esegue `install-shortcut.ps1` e apre l'app dal collegamento (con il parametro `-NoLaunch`, usato da `update.ps1`, non la apre).

### Aggiornamento automatico
Le installazioni da ZIP si aggiornano da sole con le **Release** di GitHub (repository pubblico `lorenzo-rosselli/tab-manager`): un push su `main` non arriva agli altri computer finché non si pubblica una Release (pagina del repository → Releases → "Draft a new release", con un tag come `v1.1`).
- **Controllo all'avvio** (`tab-manager.ps1`): `Invoke-RestMethod` su `https://api.github.com/repos/lorenzo-rosselli/tab-manager/releases/latest` (TLS 1.2, intestazione `User-Agent`, massimo 5 secondi) legge `tag_name` e lo confronta con `windows\version.txt`, la Release installata (nel `.gitignore`). Se sono diversi (anche quando `version.txt` manca, per esempio la prima volta dopo l'installazione) apre in una finestra visibile `update.ps1 -Tag <tag>` e si chiude. Il controllo non si fa: sul computer di sviluppo (cartella con `.git`: lì gli aggiornamenti si fanno con git), se l'app è già aperta (porta 8765 occupata), senza Internet, se GitHub non risponde o se non c'è ancora nessuna Release. In tutti questi casi l'app si apre normalmente.
- **`update.ps1`** (finestra "Aggiornamento di Tab Manager"):
  1. scarica `https://github.com/lorenzo-rosselli/tab-manager/archive/refs/tags/<tag>.zip` in `%TEMP%\TabManager-update` con `Invoke-WebRequest` (senza barra di avanzamento, che in PowerShell 5.1 rallenta molto il download) e lo estrae con `Expand-Archive`; lo ZIP contiene una sola cartella (es. `tab-manager-1.1`), che deve contenere `artisan`. Un file scaricato da PowerShell non ha il segno di Internet: Smart App Control non lo blocca;
  2. copia la versione attuale in `%LOCALAPPDATA%\TabManager\versione-precedente` (senza `node_modules`);
  3. mette i file nuovi al posto dei vecchi con `robocopy /MIR` (toglie anche i file che la nuova versione non ha più). Restano quelli di questo computer: cartelle `.git`, `vendor`, `node_modules`, `storage`, `public\build`, `bootstrap\cache` (passate a `/XD` con il percorso di origine e di arrivo) e file `.env`, `Tab Manager.lnk`, `hot`, `version.txt`, `database.sqlite*` (`/XF`). Codici di uscita di robocopy da 0 a 7 = riuscito;
  4. esegue il `setup.ps1` della nuova versione con `-NoLaunch` (librerie, interfaccia, copia di sicurezza del database, `migrate`);
  5. scrive il tag in `version.txt`;
  6. legge le note della Release (`https://api.github.com/repos/lorenzo-rosselli/tab-manager/releases/tags/<tag>`, campo `body`: il testo in Markdown della sezione "Release notes") e le scrive con il tag in `storage\app\private\update-notes.json` (`{"version": "v1.1.0", "notes": "..."}`, UTF-8 senza BOM). Se le note non si leggono il file si scrive con `notes` vuoto; se il file non si scrive l'aggiornamento resta valido. La cartella `storage` non viene toccata dagli aggiornamenti, quindi il file resta lì;
  7. cancella la cartella temporanea e apre l'app (`conhost.exe --headless powershell.exe ... tab-manager.ps1 -SkipUpdate`, come il collegamento).
- **Finestra "Tab-Manager aggiornato alla versione …!"**: alla prima pagina aperta dopo un aggiornamento compare una finestra con il titolo "Tab-Manager aggiornato alla versione 1.1.0!" (il tag senza la "v") e, sotto, le note della Release. Compare una volta sola (vedi "Viste Blade", `layouts/_update-notes.blade.php`).

  Se un passo fallisce, rimette la versione precedente (stessa copia a specchio, al contrario), mostra l'errore e, premendo Invio, apre l'app: `version.txt` non cambia, quindi al prossimo avvio riprova. Durante l'aggiornamento il file `update.ps1` stesso viene sostituito: non è un problema, perché PowerShell legge tutto lo script prima di eseguirlo.

### Disinstallazione
Doppio clic su **`Disinstalla Tab Manager.cmd`** (nella cartella principale): avvia `windows\uninstall.ps1` come l'installatore avvia `setup.ps1` (`-ExecutionPolicy Bypass` solo per quell'avvio, `pause` alla fine). Lo script:
- chiede conferma (S/N) prima di fare qualsiasi cosa;
- chiude l'app se è aperta: i processi di Edge con il profilo `%LOCALAPPDATA%\TabManager\Edge` e il server PHP sulla porta 8765 (`php.exe` o `wsl.exe`, e con WSL anche `pkill` dentro Linux);
- cancella i collegamenti `Tab Manager.lnk` sul desktop e nella cartella dell'app (quello sul desktop solo se è davvero di Tab Manager, cioè se lancia `tab-manager.ps1`);
- cancella il profilo di Edge dell'app (riprova per qualche secondo se Edge tiene ancora i file aperti) e la copia `%LOCALAPPDATA%\TabManager\versione-precedente` lasciata dall'aggiornamento automatico;
- **non tocca mai l'archivio dei brani né le sue copie** (`Documenti\Tab Manager`): alla fine dice dov'è (da `DB_DATABASE` nel `.env`). Se l'archivio è dentro la cartella dell'app (succede solo se `setup.ps1` non è stato eseguito), avvisa di rilanciare l'installazione o di copiarlo prima di cancellare la cartella. Chi vuole davvero cancellare i brani lo fa a mano. La cartella `%LOCALAPPDATA%\TabManager` viene tolta se resta vuota;
- **non** cancella la cartella dell'app (uno script non può cancellare la cartella da cui è in esecuzione, e sul computer di sviluppo è il progetto con git): alla fine ne scrive il percorso, da cancellare a mano (non apre Esplora file);
- **non** disinstalla PHP, Composer (php.new) e Node.js, che possono servire ad altri programmi. Il `README.md` spiega come toglierli a mano: cartella `%USERPROFILE%\.config\herd-lite` e la sua voce nel PATH dell'utente; Node.js da Impostazioni → App oppure `winget uninstall OpenJS.NodeJS.LTS`.

Nient'altro resta sul computer: l'app non crea servizi, voci nel registro o avvii automatici.

### Archivio dei brani e copie di sicurezza
L'archivio è indipendente dall'app: si può cancellare, riscaricare, aggiornare o disinstallare l'app, anche su un altro computer, senza perdere i brani.
- **Dove**: `Documenti\Tab Manager\database.sqlite` (indicato da `DB_DATABASE` nel `.env`, che `setup.ps1` scrive da solo). Cartella visibile in Esplora file; se Documenti è sincronizzata con OneDrive, l'archivio finisce anche nel cloud.
- **Copie automatiche** in `Documenti\Tab Manager\Backup`, con il comando **`php artisan tab:backup`** (in `routes/console.php`, usa `App\Services\TabBackup`):
  - `--daily`: una volta al giorno, all'avvio dell'app (`tab-manager.ps1`), solo se oggi non c'è già una copia;
  - `--label="prima dell'aggiornamento"`: prima di ogni `migrate` di `setup.ps1` (quindi anche a ogni aggiornamento automatico);
  - senza opzioni, a mano: etichetta "manuale".
  
  Nome della copia: data, ora ed etichetta, es. `2026-11-25 17-05-12 automatico.sqlite`. Si tengono le ultime **30** (`TabBackup::KEEP`), le più vecchie vengono cancellate. Se il database non esiste ancora, il comando non fa niente.
- **Backup da scaricare**: pulsante **Backup** nell'elenco (vedi sezione 5). Scarica `Tab Manager backup AAAA-MM-GG HH-MM.zip` con:
  - `Brani/Artista - Titolo.docx`: tutti i brani, come il download del singolo brano; con artista e titolo uguali si aggiunge " (2)", " (3)"…;
  - `database.sqlite`: l'archivio completo;
  - `LEGGIMI.txt`: contenuto e istruzioni di ripristino (a capo di Windows).
  
  I `.docx` servono per leggere e stampare (l'app non li sa rileggere); per ripristinare serve `database.sqlite`.
- **Copie coerenti**: le copie del database si fanno con `VACUUM INTO` di SQLite, corrette anche se l'app sta scrivendo (una semplice copia del file potrebbe prenderne una versione a metà).
- **Ripristino** (spiegato nel `README.md` e nel `LEGGIMI.txt`): chiudere l'app e mettere il `database.sqlite` del backup (o una copia automatica rinominata) in `Documenti\Tab Manager`, al posto di quello esistente. Su un altro computer: prima installare l'app e aprirla una volta. Nell'app non c'è un pulsante "Ripristina".
- Classe **`App\Services\TabBackup`**: `databasePath()`, `folder()` (la cartella `Backup` accanto al database), `hasToday()`, `snapshot($label)` (copia datata + pulizia delle più vecchie), `zip()` (crea lo ZIP in un file temporaneo e ne restituisce il percorso), `zipName()`. Lo ZIP si crea con `ZipArchive` di PHP, già presente perché serve anche a PHPWord (anche il .docx è uno ZIP): nessuna libreria in più.

In `.gitattributes`, `*.cmd` e `*.ps1` hanno terminazioni di riga **CRLF** (anche nello ZIP di GitHub): `cmd.exe` e PowerShell 5.1 le vogliono così.

---

## 3. Stack tecnologico

| Parte | Tecnologia | Note |
|---|---|---|
| Backend | **Laravel 13** su PHP 8.5 | Rotte, controller, validazione, Eloquent |
| Frontend | **Blade** + **Tailwind CSS 4** (via Vite) | HTML generato dal server, già incluso nel progetto Laravel. Tema chiaro e scuro, con interruttore |
| Editor del foglio | **Tiptap 3** (`@tiptap/core`, `@tiptap/pm`, `@tiptap/starter-kit`, `@tiptap/extension-text-style`, `@tiptap/extension-text-align`, `@tiptap/extension-highlight`; licenza MIT) | Editor di testo ricco, installato con npm e caricato da Vite solo nelle pagine del form (`resources/js/tab-editor.js`) |
| JavaScript | Nessun'altra libreria | Oltre all'editor: `resources/js/tab-pages.js` per mostrare il foglio su pagine A4 (form e anteprima) e `resources/js/app.js` (poche righe) per l'interruttore del tema, il tasto Esc nell'anteprima e l'apertura della finestra dopo un aggiornamento. Tutto il resto (ordinamento, riga cliccabile, modale di conferma) funziona senza JavaScript |
| Database | **SQLite** | File unico `Documenti\Tab Manager\database.sqlite`, fuori dalla cartella dell'app (sezione 2, "Archivio dei brani e copie di sicurezza") |
| Export Word | **PHPWord** (`phpoffice/phpword`) | Unica dipendenza PHP aggiuntiva |
| App desktop | **Microsoft Edge** in modalità app + script **PowerShell** | Finestra dedicata e collegamento sul desktop (sezione 2); nessuna dipendenza in più |
| Supporto sviluppo | **Laravel Boost** (`laravel/boost`, solo `require-dev`) | Installato da `laravel new`; strumenti per gli assistenti AI. I suoi file di linee guida (`AGENTS.md`, `CLAUDE.md`) non sono usati e non sono nel progetto. Non fa parte dell'app |

### Escluso volutamente
React, Vue, Livewire, Inertia, Alpine.js, starter kit con autenticazione, MySQL/PostgreSQL, pacchetti di icone o di traduzioni.

---

## 4. Modello dati

### Tabella `songs`

| Campo | Tipo (migration) | Obbligatorio | Significato |
|---|---|---|---|
| `id` | `id()` | sì | — |
| `artist` | `string(255)` | sì | Artista del brano (es. Queen) |
| `title` | `string(255)` | sì | Titolo del brano (es. Another One Bites the Dust) |
| `band` | `string(255)`, nullable | no | Il gruppo con cui si suona il brano |
| `instrument` | `string(10)`: valori `bass` / `guitar` / `voice` | sì | Strumento |
| `strings` | `unsignedTinyInteger`, nullable | no | Numero di corde: 4 o 5 per il basso, 6 per la chitarra; vuoto per la voce |
| `show_instrument` | `boolean`, default `false` | sì | Scrivere lo strumento nella seconda riga del foglio |
| `show_band` | `boolean`, default `false` | sì | Scrivere il gruppo nella seconda riga del foglio |
| `tab` | `longText` | sì | Il **foglio**: documento formattato in formato JSON di Tiptap (vedi sotto) |
| `created_at` / `updated_at` | `timestamps()` | auto | Data di creazione (non mostrata) e dell'ultimo salvataggio che ha cambiato qualcosa (colonna "Data ultimo aggiornamento" dell'elenco) |

La tabella è creata da `2026_11_24_182440_create_songs_table.php`; la colonna `strings` la aggiunge una seconda migration, `2026_11_25_150000_add_strings_to_songs_table.php`. Le nuove colonne si aggiungono sempre così, con una migration nuova: `php artisan migrate` (lanciato anche da `Installa Tab Manager.cmd`) aggiorna i database che hanno già dei brani senza cancellarli. Anche le modifiche ai dati dei brani si fanno così: `2026_11_26_150000_keep_existing_songs_at_10pt.php` scrive `"10pt"` sul testo dei brani salvati quando la dimensione predefinita era 10 pt (testo senza dimensione propria e righe vuote senza formato), così restano identici ora che il predefinito è 12 pt. Aggiorna solo la colonna `tab` con `DB::table()`: `updated_at` non cambia.

### Il foglio (`tab`)
Un documento JSON di Tiptap: una lista di paragrafi, ognuno con il suo allineamento e i suoi pezzi di testo, ognuno con la sua formattazione.

```json
{"type": "doc", "content": [
  {"type": "paragraph", "attrs": {"textAlign": "center", "auto": "header", "autoText": "Queen - Another One Bites the Dust"},
   "content": [{"type": "text", "text": "Queen - Another One Bites the Dust",
                "marks": [{"type": "bold"}, {"type": "textStyle", "attrs": {"fontSize": "16pt"}}]}]},
  {"type": "paragraph"},
  {"type": "paragraph", "content": [{"type": "text", "text": "G|-----------------|"}]}
]}
```

- Elementi usati: paragrafi (`paragraph`, attributo `textAlign`: `left` / `center` / `right`; attributi `fontSize` es. `"12pt"` e `fontFamily` es. `"Courier New"`: font e dimensione della riga quando è vuota, come il segno di paragrafo di Word, vedi sezione 5 "Form"), interruzioni di pagina (`pageBreak`, blocco senza contenuto: il paragrafo che segue inizia su una pagina nuova), a capo (`hardBreak`), testo (`text`) con i segni `bold`, `italic`, `underline`, `textStyle` (attributi `fontFamily`, `fontSize` es. `"14pt"`, `color` es. `"#C00000"`, qualsiasi colore nella forma `#RRGGBB`) e `highlight` (attributo `color`, uno dei colori di `Song::HIGHLIGHTS`, es. `"#FFFF00"`).
- Il testo senza formattazione propria usa **Lucida Console 12 pt**, colore automatico (nero), nessuna evidenziazione.
- Righe automatiche: i paragrafi con l'attributo `auto` (`header` = "Artista - Titolo", `info` = "Strumento: … - Gruppo") sono le righe scritte dall'app in cima al foglio; `autoText` ricorda il testo scritto in automatico (vedi sezione 5, "Form").

### Modello `App\Models\Song`
- Campi compilabili dichiarati con l'attributo `#[Fillable([...])]` sopra la classe (stile Laravel 13): `artist`, `title`, `band`, `instrument`, `strings`, `show_instrument`, `show_band`, `tab`.
- Costanti, usate dal form, dalla lettura del foglio e dal Word:
  - `Song::FONTS`: `Lucida Console`, `Courier New`, `Consolas` (solo font **monospaziati**, così le colonne della tabulatura restano allineate);
  - `Song::FONT_SIZES`: 8, 9, 10, 11, 12, 14, 16, 18, 20;
  - `Song::ALIGNMENTS`: `left` => "Allinea a sinistra", `center` => "Centra", `right` => "Allinea a destra";
  - `Song::TEXT_COLORS`: la tavolozza del colore del testo nella barra, con i "colori standard" di Word (rosso scuro, rosso, arancione, verde, azzurro, blu, blu scuro, viola, grigio), valore esadecimale => nome; senza colore il testo è "Automatico" (nero). Il testo incollato può avere anche altri colori: il foglio ammette qualsiasi colore `#RRGGBB`;
  - `Song::HIGHLIGHTS`: i colori dell'evidenziatore (giallo, verde, turchese, rosa, rosso, grigio chiaro), valore esadecimale => [nome, nome dell'evidenziatore in Word: `yellow`, `green`, `cyan`, `magenta`, `red`, `lightGray`]. Word ammette solo una tavolozza fissa di evidenziatori, quindi si usano quelli;
  - `Song::DEFAULT_FONT` = `Lucida Console`, `Song::DEFAULT_FONT_SIZE` = 12.
- Proprietà `$attributes`: `show_instrument` e `show_band` a `false` per un brano nuovo.
- Cast (metodo `casts()`): `instrument` → Enum `Instrument`, `strings` → integer, `show_instrument`/`show_band` → boolean, `tab` → array (il JSON diventa un array PHP e viceversa).

### Classe `App\Services\TabDocument`
Sa trattare il foglio lato server:
- `empty()`: documento vuoto (un paragrafo senza testo);
- `make($artist, $title, $instrument, $band, $text)`: costruisce un foglio con le righe automatiche e il testo dato (una riga = un paragrafo); usata dal seeder;
- `infoLine($instrument, $band)`: testo della seconda riga automatica, "Strumento: Basso - Nome del gruppo" (o solo una delle due parti);
- `paragraphs($doc)`: legge il documento e restituisce solo ciò che l'app conosce, già controllato (paragrafi con allineamento, `pageBreakBefore` e pezzi di testo con grassetto, corsivo, sottolineato, font, dimensione, colore ed evidenziatore). Elementi sconosciuti, font, dimensioni e colori non ammessi vengono ignorati. I colori passano da `hexColor()`; il nero vale come "automatico" e l'evidenziatore deve essere uno di `Song::HIGHLIGHTS`. Ogni paragrafo ha anche `font` e `size` (dagli attributi `fontFamily` e `fontSize`, controllati come quelli del testo), che contano solo se il paragrafo è vuoto. `pageBreakBefore` è vero per il paragrafo che segue un'interruzione di pagina; due interruzioni di fila danno una pagina vuota (un paragrafo vuoto), un'interruzione in fondo al foglio viene ignorata;
- `hexColor($value)`: porta un colore nella forma `#RRGGBB` maiuscola; accetta `#RGB`, `#RRGGBB` e `rgb(r, g, b)` (il formato in cui il browser legge i colori), altrimenti restituisce null (anche per un colore trasparente);
- `hasText($doc)`: vero se il foglio contiene almeno un carattere che non sia uno spazio;
- `toHtml($doc)`: HTML del foglio per l'anteprima, con l'escaping di tutto il testo (`e()`) e solo valori ammessi negli stili: sicuro da stampare con `{!! !!}`. Prima di un paragrafo con `pageBreakBefore` scrive `<div class="tab-page-break" data-page-break>` (invisibile), che l'impaginazione usa per andare a pagina nuova. Un paragrafo vuoto diventa `<p><br></p>` con il suo `font-family` e `font-size`, se li ha, così nell'anteprima è alto come nel foglio.

### Enum `App\Enums\Instrument`
- Casi `Bass = 'bass'`, `Guitar = 'guitar'` e `Voice = 'voice'`.
- Metodo `label()` che restituisce "Basso" / "Chitarra" / "Voce".
- Metodo `tunings()`: i numeri di corde che si possono scegliere, ognuno con l'accordatura standard dalla corda più acuta alla più grave (l'ordine delle righe di una tabulatura): basso `4 => G D A E` e `5 => G D A E B`; chitarra `6 => e B G D A E` (la "e" minuscola è il Mi acuto, come si usa nelle tablature); voce nessuno. Metodo `strings()`: solo i numeri (`[4, 5]`, `[6]`, `[]`). Metodo `defaultStrings()`: il numero proposto, cioè il primo di `tunings()` (basso 4, chitarra 6, voce null).
- È l'unico punto in cui sono elencati gli strumenti e le loro corde: select del form, validazione e ricerca li leggono da qui.

### Altro
- Le tabelle predefinite di Laravel (`users`, `sessions`, `cache`, `jobs`…) restano: servono al framework.
- Il `DatabaseSeeder` non crea utenti e chiama solo `SongSeeder` (vedi sezione 8).

---

## 5. Pagine e rotte

| Metodo | URL | Azione controller | Nome rotta | Descrizione |
|---|---|---|---|---|
| GET | `/` | — | — | Redirect a `/songs` |
| GET | `/songs` | `index` | `songs.index` | Elenco brani, con ricerca (`?q=`) e ordinamento (`?sort=`) |
| GET | `/songs/create` | `create` | `songs.create` | Form nuovo brano |
| POST | `/songs` | `store` | `songs.store` | Salva nuovo brano |
| GET | `/songs/{song}` | `show` | `songs.show` | Anteprima del foglio |
| GET | `/songs/{song}/edit` | `edit` | `songs.edit` | Form modifica |
| PUT | `/songs/{song}` | `update` | `songs.update` | Aggiorna brano |
| DELETE | `/songs/{song}` | `destroy` | `songs.destroy` | Elimina brano |
| GET | `/songs/{song}/docx` | `download` | `songs.download` | Genera e scarica il .docx |
| GET | `/backup` | `BackupController` (invocabile) | `backup` | Scarica lo ZIP di backup (tutti i brani in .docx + database) |

```php
Route::redirect('/', '/songs');
Route::resource('songs', SongController::class);
Route::get('songs/{song}/docx', [SongController::class, 'download'])->name('songs.download');
Route::get('backup', BackupController::class)->name('backup');
```

`App\Http\Controllers\BackupController` ha una sola azione (`__invoke`): riceve `TabBackup` per dependency injection e risponde con `response()->download($backup->zip(), $backup->zipName(), ['Content-Type' => 'application/zip'])->deleteFileAfterSend()`.

### Controller `App\Http\Controllers\SongController`
- `store` e `update` salvano `$request->songData()` (vedi sezione 6) e fanno un redirect all'anteprima con un **messaggio flash** (`->with('status', ...)`): "Brano salvato.", "Modifiche salvate."; `destroy` torna all'elenco con "Brano eliminato.".
- `index(Request $request)`:
  - **ricerca** `?q=testo`: mostra i brani che contengono il testo nell'artista, nel titolo, nel gruppo o nel nome dello strumento (`where`/`orWhere` con `like`, raggruppati in un `where(function ...)`). Lo strumento è salvato come `bass`/`guitar`/`voice`: si cercano i casi di `Instrument` la cui etichetta ("Basso", "Chitarra", "Voce") contiene il testo e si aggiunge `orWhereIn('instrument', ...)`, così "basso" o "chit" trovano i brani giusti;
  - **ordinamento multiplo** `?sort=artist,-title`: lista di colonne separate da virgole, nell'ordine di priorità; il `-` davanti indica l'ordine decrescente. Chiavi ammesse (costante `SORTABLE`, che fa da lista bianca e dà l'ordine delle colonne): `artist`, `title`, `band`, `instrument`, `date` (→ `updated_at`). **Ordine predefinito**: senza `sort` (anche durante una ricerca) l'elenco va per `updated_at` decrescente, `latest('updated_at')`: in cima il brano modificato più di recente. Non compare nella riga "Ordinato per:" né con le frecce delle intestazioni: è l'ordine normale della tabella, e togliendo tutti gli ordinamenti si torna lì. In SQLite i gruppi vuoti (NULL) vengono prima in ordine crescente;
  - metodi privati: `parseSorts()` (testo → array colonna => direzione), `sortParam()` (l'inverso), `sortColumns()` (per ogni intestazione: etichetta, stato attuale, posizione e link del clic successivo).
- `download(Song $song, TabDocxGenerator $generator)`: vedi sezione 7.

### Aspetto generale (tema chiaro e scuro)
- Layout con `@extends('layouts.app')` / `@section` / `@yield`.
- Due temi con i grigi di Tailwind (`gray`); ogni elemento ha la classe del tema chiaro e quella del tema scuro con il prefisso `dark:`:
  - **scuro** (predefinito), tonalità notturne classiche: sfondo `gray-900`, intestazione `gray-950`, tabella, riquadri e modale `gray-800`, bordi `gray-700`; testi bianchi o grigio chiaro; errori `red-400`; messaggio flash verde scuro; pulsanti principali chiari (`gray-100`) con icona scura;
  - **chiaro**: sfondo `gray-100`, intestazione, tabella e modale bianchi, bordi `gray-200`/`gray-300`; testi scuri; errori `red-600`; messaggio flash verde chiaro; pulsanti principali scuri (`gray-900`) con icona bianca.
- I **campi** (input, select, ricerca) e il **foglio** sono **bianchi** con testo scuro in entrambi i temi.
- Il tema scuro si attiva con la classe `dark` su `<html>`: in `resources/css/app.css` la variante è dichiarata con `@custom-variant dark (&:where(.dark, .dark *));` (quindi non dipende dalle impostazioni del sistema operativo). Sull'`<html>` c'è anche `scheme-dark` o `scheme-light`, così barre di scorrimento e controlli del browser seguono il tema.
- **Interruttore del tema** nell'intestazione, a sinistra dell'icona "Nuovo brano": un interruttore a pillola (`<button role="switch" aria-checked>`). In chiaro mostra il sole a sinistra e il pomello a destra; in scuro il pomello a sinistra e la luna a destra. L'aspetto dipende solo dalla classe `dark` (pomello con `translate-x`, icone con `opacity`).
  - Il clic è gestito da `resources/js/app.js`: cambia subito il tema (senza ricaricare la pagina, così nel form non si perde niente) e salva la scelta nel cookie `theme` (`light` / `dark`, durata un anno).
  - Il layout legge il cookie con `request()->cookie('theme')` e scrive già la classe giusta su `<html>`: nessun "lampo" di colore al caricamento. Senza cookie: tema scuro.
  - Il cookie è scritto dal browser, quindi va escluso dalla cifratura dei cookie di Laravel: in `bootstrap/app.php`, `$middleware->encryptCookies(except: ['theme'])`.
- Il layout carica `@vite(['resources/css/app.css', 'resources/js/app.js'])`; niente `@fonts`, si usano i font di sistema. Le pagine del form caricano in più `@vite('resources/js/tab-editor.js')` (che a sua volta usa `tab-pages.js`), l'anteprima `@vite('resources/js/tab-pages.js')`.
- **Tutti i comandi sono icone**, con il nome come tooltip (attributo `title`) e `aria-label` per i lettori di schermo. Fanno eccezione, come in Word, le lettere della barra di formattazione (**G** / *C* / <u>S</u>, la "A" del colore e "INS") e il link dentro la frase dell'elenco vuoto ("Aggiungi il primo").

### Viste Blade (`resources/views`)
- `layouts/_update-notes.blade.php` (incluso in fondo al layout): la finestra **"Tab-Manager aggiornato alla versione …!"** dopo un aggiornamento automatico. Modale con l'API Popover come quella dell'eliminazione (`id="update-notes"`, alta al massimo l'80% della finestra, con scorrimento), titolo, note della Release (classe `.release-notes`) se non sono vuote e icona ✕ "Chiudi"; si chiude anche con Esc o cliccando fuori. C'è solo quando `$updateNotes` non è null: lo passa un *view composer* registrato in `AppServiceProvider::boot()` per `layouts.app`, che chiama `App\Services\UpdateNotes::pull()`. `pull()` legge `storage/app/private/update-notes.json` e **lo cancella subito** (così la finestra compare una volta sola), toglie la "v" iniziale dal tag e converte le note da Markdown in HTML con `Str::markdown()` (league/commonmark, già incluso in Laravel) con `html_input => strip` e `allow_unsafe_links => false`: l'HTML scritto a mano nelle note viene tolto e non si creano link `javascript:`, quindi si stampa con `{!! !!}`. `resources/js/app.js` apre la finestra appena la trova nella pagina (`showPopover()`) e fa aprire i link delle note in un'altra finestra (`target="_blank"`). In `app.css` la classe `.release-notes` rimette gli stili di titoli, elenchi puntati e numerati, link, grassetto e codice, che il reset di Tailwind toglie.
- `layouts/app.blade.php`: struttura comune; classe del tema su `<html>`; icona dell'app (`public/favicon.svg` e `public/favicon.ico`, usata dalla scheda del browser, dalla finestra dedicata e dalla barra delle applicazioni); intestazione con il logo e il nome dell'app (link all'elenco), l'interruttore del tema e l'icona "Nuovo brano"; messaggio flash `session('status')`. **Nel form** (rotte `songs.create` e `songs.edit`, `request()->routeIs(...)`, quindi anche dopo un errore di validazione) logo e nome non sono un link e l'icona "Nuovo brano" non c'è: dall'intestazione non si può uscire dalla pagina e perdere il lavoro non salvato; si esce solo con l'icona Annulla, che chiede conferma se ci sono modifiche.
- `songs/index.blade.php`:
  - in alto: casella di ricerca larga `w-128` (32rem) con il suggerimento "Cerca per artista, titolo, gruppo o strumento" (per i lettori di schermo, `aria-label`, con i nomi completi delle colonne: "Cerca per artista originale, titolo, il mio gruppo o strumento") (form GET con icona lente e icona per annullare la ricerca) e, dopo un separatore, l'icona **Backup** (`archive-box`, link alla rotta `backup`: scarica lo ZIP con tutti i brani e il database). Ricerca e ordinamento si conservano a vicenda (campo nascosto `sort` nel form, `q` nei link delle intestazioni);
  - **sopra la tabella**, solo quando c'è almeno un ordinamento: la riga "Ordinato per:" con le colonne ordinate nell'ordine di priorità (etichette a pillola con la freccia su/giù) e l'icona ✕ "Azzera ordinamento", che toglie tutti gli ordinamenti e mantiene la ricerca;
  - tabella con **Artista originale**, **Titolo**, **Il mio gruppo** ("—" se vuoto), Strumento, **Data ultimo aggiornamento** (`updated_at`, `d/m/Y`) e Azioni (Modifica, Scarica .docx, Elimina);
  - **intestazioni ordinabili**: ogni clic su una colonna fa crescente → decrescente → tolta; le colonne si accumulano nell'ordine di scelta. La colonna ordinata mostra una freccia (su/giù) e, se le colonne ordinate sono più di una, il numero di priorità. Il tooltip dice cosa farà il clic successivo;
  - **riga cliccabile**: cliccando ovunque su una riga si apre l'anteprima del brano. Senza JavaScript: il link del titolo ha uno pseudo-elemento `after:absolute after:inset-0` che copre tutta la riga (`<tr class="relative">`); la cella delle azioni sta sopra (`relative z-10`), così le icone restano cliccabili;
  - messaggi per elenco vuoto e per ricerca senza risultati.
- `songs/_delete.blade.php`: icona cestino + **finestra modale di conferma** fatta con l'**API Popover** di HTML (`popover` + `popovertarget`), senza JavaScript. Il testo è "Artista - Titolo verrà eliminato definitivamente."; si chiude con l'icona Annulla, con Esc o cliccando fuori; il form DELETE parte solo dal pulsante di conferma. Il pannello è centrato con `m-auto h-fit` (il reset CSS di Tailwind azzera il `margin: auto` dei popover). Richiede un browser recente (Chrome/Edge 114+, Firefox 125+, Safari 17+). Usata nell'elenco e nell'anteprima.
- `songs/create.blade.php` e `songs/edit.blade.php`: contengono il `<form>` (POST, oppure PUT con `@method('PUT')`) e includono i partial `songs/_form-header.blade.php` e `songs/_form.blade.php`.
- `songs/_form-header.blade.php`: la riga in cima al form, con il titolo della pagina ("Nuovo brano" / "Modifica brano") a sinistra e, sulla stessa riga a destra, le icone floppy "Salva brano" / "Salva modifiche" e "Annulla". Sta dentro il `<form>`, così il floppy lo invia. Riceve `$title`, `$submitLabel` e `$cancelUrl`.
  - **Annulla** porta a un indirizzo fisso: l'elenco per un brano nuovo, l'anteprima del brano in modifica (non "la pagina precedente", che dopo un errore di validazione sarebbe il form stesso).
  - Se ci sono **modifiche non salvate**, Annulla non esce subito ma apre una finestra di conferma (API Popover, come quella dell'eliminazione, id `discard-changes`): "Uscire senza salvare? Le modifiche non salvate andranno perse.", con le icone "Continua a modificare" (matita, chiude la finestra) ed "Esci senza salvare" (✕ su fondo rosso). Il controllo lo fa `tab-editor.js`: confronta lo stato del form all'apertura della pagina (tutti i campi tranne `_token`, `_method` e `tab`, più il documento dell'editor con `getJSON()`) con quello attuale; scrivere e poi annullare con Ctrl+Z non conta come modifica. Senza JavaScript Annulla esce senza chiedere.
- `songs/show.blade.php`: titolo, artista, riga "Strumento: … – Il mio gruppo: …" (il gruppo solo se presente); a destra, sulla stessa riga del titolo, le icone in quest'ordine: **Torna all'elenco**, **Modifica**, **Scarica .docx** (pulsante pieno), **Elimina**; sotto, il **foglio** sulle stesse pagine A4 del form (elemento con `data-paginate`, impaginato da `tab-pages.js`; nell'anteprima le pagine sono pulite, senza bordo tratteggiato né scritta dell'interruzione), con l'HTML di `TabDocument::toHtml()`. **Tasto Esc**: torna all'elenco. L'indirizzo è nell'attributo `data-escape-to` e il tasto è gestito da `resources/js/app.js`; se è aperta la modale di eliminazione, Esc chiude solo quella.
- `icons/*.blade.php`: icone SVG scritte direttamente nell'HTML, incluse con `@include('icons.nome')`, senza pacchetti: `logo` (il logo dell'app), `plus`, `pencil`, `download`, `trash`, `save`, `x-mark`, `arrow-left`, `magnifying-glass`, `chevron-up`, `chevron-down`, `align-left`, `align-center`, `align-right`, `page-break`, `tab-staff`, `archive-box`, `highlighter`, `sun`, `moon`. Fonti: Heroicons v2 outline (licenza MIT); il floppy `save` e l'evidenziatore `highlighter` vengono da Lucide (licenza ISC), con lo spessore portato a 1.5; le icone di allineamento, `page-break` (fondo di una pagina, linea tratteggiata, inizio della successiva) e `tab-staff` (le righe di una tabulatura con un "+") sono disegnate sul modello delle Heroicons.
- **Icona e logo dell'app**, disegnati per Tab Manager: quadrato arancione (`#F59E0B`) con gli angoli arrotondati, quattro corde scure (`#1F2937`) e i numeri 3, 5, 7 sulle corde come in una tablatura. Le cifre sono tracciati (non testo), così l'icona è identica su ogni computer. Stessa immagine in `resources/views/icons/logo.blade.php` (intestazione, 32 px), `public/favicon.svg`, `public/favicon.ico` e `windows/tab-manager.ico` (16–256 px).
- `resources/css/app.css`: oltre a Tailwind, la classe `.tab-sheet` del foglio (editor e anteprima): Lucida Console 12 pt, interlinea `normal` (quella del font, come l'interlinea singola del Word), testo scuro, `white-space: pre-wrap` (gli spazi restano), paragrafi senza margini. Inoltre le classi delle pagine: `.tab-paper` (il foglio, largo 21 cm; bianco finché lo script non disegna le pagine), `.tab-paper-pages` e `.tab-page` (le pagine bianche con l'ombra e, solo nell'editor, il bordo tratteggiato della zona scrivibile, più scuro quando si sta scrivendo), `.tab-page-spacer` (spazio fino alla pagina successiva) e `.tab-page-break` (interruzione di pagina: alta 0 nell'anteprima; nell'editor una riga tratteggiata grigia con la scritta "Interruzione di pagina", blu quando è selezionata).

### Form (`songs/_form.blade.php`)
- I campi sono su due colonne, in quest'ordine: **Artista originale**, **Titolo** / **Strumento**, **Corde** / **Il mio gruppo** (da solo nella colonna di sinistra). "Artista originale" è chi ha fatto il brano, "Il mio gruppo" la band con cui lo suona l'utente: le stesse etichette sono usate nell'intestazione dell'elenco (costante `SORTABLE` del controller), nell'anteprima e nei messaggi di errore.
- `artist`, `title`: input di testo.
- `instrument`: select (Basso / Chitarra / Voce), costruita da `Instrument::cases()`, con accanto la spunta **"Mostra nel foglio"** (`show_instrument`).
- `strings`: select **"Corde"**, accanto allo strumento, con le voci "—" e "4 corde", "5 corde", "6 corde" (da `Instrument::tunings()`; ogni voce ha `data-instrument` e `data-tuning` con le corde, es. `"G D A E"`), senza i nomi degli strumenti. Lo script dell'editor mostra solo le voci dello strumento scelto: basso "4 corde" e "5 corde", chitarra "6 corde"; con la voce resta solo "—" e il menu è disattivato. Il valore proposto è `defaultStrings()` (basso 4, chitarra 6): per un brano nuovo, per un brano senza numero di corde e quando si cambia strumento. Serve solo per il pulsante "Inserisci tabulatura vuota": non compare nel foglio, nell'elenco, nell'anteprima né nel Word.
- `band`: input di testo "Il mio gruppo (facoltativo)", con accanto la spunta **"Mostra nel foglio"** (`show_band`).
- Le spunte sono caselle precedute da un campo nascosto con valore `0` (inviato quando la casella è vuota).
- **Il foglio** (`tab`) è esattamente il documento Word che verrà scaricato. È composto da:
  - una **barra di formattazione** in alto (`role="toolbar"`, grigio chiaro o quasi nera secondo il tema), simile a quella di Word: menu **font** (`Song::FONTS`), menu **dimensione** (`Song::FONT_SIZES`), pulsanti **G** / *C* / <u>S</u> (grassetto, corsivo, sottolineato, come nel Word italiano), **colore carattere** ("A" con una barretta del colore attuale) ed **evidenziatore** (icona con una barretta del colore attuale), tre pulsanti di **allineamento**, il pulsante **Interruzione di pagina** (anche con Ctrl+Invio, come in Word), il pulsante **Inserisci tabulatura vuota** (icona `tab-staff`), attivo solo quando è scelto il numero di corde (il tooltip dice quante, oppure di sceglierle prima), e il pulsante **INS** (sovrascrittura, anche con il tasto Ins; premuto quando è acceso). Colore ed evidenziatore aprono un menu a tendina (`<details>`) con la tavolozza (`Song::TEXT_COLORS` più "Automatico" = nero; `Song::HIGHLIGHTS` più "Nessuno"). Il menu si chiude scegliendo un colore, cliccando in qualsiasi altro punto della pagina (aprendo l'altra tavolozza la prima si chiude, quindi ne è aperta al massimo una) o con Esc. Font, dimensione, G/C/S, colore ed evidenziatore si applicano al **testo selezionato**; l'allineamento ai **paragrafi** selezionati. La barra mostra la formattazione del punto in cui si trova il cursore (pulsanti attivi con `aria-pressed`, stile Tailwind `aria-pressed:`);
  - le **pagine**: A4 bianche (21 × 29,7 cm), centrate nello schermo, una sotto l'altra a 1 cm di distanza, con margini di 2 cm. La zona scrivibile (17 × 25,7 cm, la stessa del .docx) è segnata da un bordo tratteggiato, senza righello; il testo va a capo al margine come in Word e, quando la pagina è piena, il paragrafo successivo passa alla pagina dopo (un paragrafo non viene mai spezzato tra due pagine: una riga di tabulatura resta intera);
  - **come si impagina** (`resources/js/tab-pages.js`, condiviso con l'anteprima): dopo ogni modifica si misura l'altezza di ogni blocco del foglio e `paginate()` calcola davanti a quali paragrafi serve uno spazio che li porti all'inizio della pagina successiva; dopo un'interruzione di pagina si va sempre a pagina nuova; un paragrafo più alto di una pagina resta dov'è. Nell'editor gli spazi sono **decorazioni** di Tiptap (si vedono ma non fanno parte del documento: non finiscono né nel JSON né nel Word); dietro al testo `drawPages()` disegna le pagine bianche. Il calcolo si ripete anche quando arrivano i font e quando cambia la finestra. I salti pagina sono molto vicini a quelli del Word, ma non garantiti al millimetro (browser e Word misurano le righe in modo leggermente diverso);
  - la **tabulatura vuota**: il pulsante aggiunge in fondo al foglio un paragrafo per corda, dalla più acuta alla più grave, nella forma `G|` seguito da 38 trattini (40 caratteri, `STAFF_DASHES = 38`; a 12 pt nella riga ce ne stanno circa 66) (basso a 4 corde: righe G, D, A, E; a 5 corde: G, D, A, E, B; chitarra: e, B, G, D, A, E), in Lucida Console, a sinistra, con la **dimensione scelta in quel momento nella barra** (quella del punto in cui si trova il cursore): se è diversa da quella predefinita le righe hanno il segno `textStyle` con quella dimensione, e la riga vuota di distacco aggiunta prima l'attributo `fontSize` (vedi le righe vuote). Tra il testo sopra e la tabulatura resta una riga vuota (se il foglio finisce già con una riga vuota si usa quella); su un foglio vuoto o subito dopo un'interruzione di pagina parte dalla prima riga. Il cursore va in fondo all'ultima riga inserita. Si può premere più volte;
  - la **sovrascrittura** (estensione `Overwrite` in `tab-editor.js`), come il tasto Ins di Word: con la modalità accesa ogni carattere scritto sostituisce quello a destra del cursore invece di spingerlo avanti, così scrivendo un "5" su `G|-----` la riga non si allunga e la tabulatura resta allineata. Vale solo scrivendo senza selezione e dentro lo stesso paragrafo: in fondo alla riga si aggiunge come sempre; con del testo selezionato la selezione viene sostituita come sempre; Canc e l'incolla non cambiano. **Backspace** non accorcia la riga: il carattere a sinistra del cursore diventa un trattino `-` e il cursore torna indietro di uno (il trattino tiene solo font e dimensione del carattere cancellato, non grassetto, colori o evidenziatore); all'inizio del paragrafo o con una selezione Backspace fa come sempre. Anche la **barra spaziatrice** non lascia buchi: senza selezione scrive un trattino `-` invece dello spazio (sostituisce il carattere a destra del cursore, o in fondo alla riga lo aggiunge) e il cursore va avanti di uno; con del testo selezionato fa come sempre. Si accende e spegne con il pulsante "INS" o con il tasto Ins; la scelta resta salvata nel browser (`localStorage`, chiave `tab-manager-overwrite`) e vale per tutti i brani. Quando è accesa il foglio ha la classe `is-overwrite` e il cursore diventa un blocco (`caret-shape: block` in `app.css`, dove il browser lo supporta);
  - le **righe vuote** (estensione `EmptyLineFont` in `tab-editor.js`): una riga vuota non ha testo e quindi non ha segni di font o dimensione; come il segno di paragrafo di Word, il paragrafo ha i suoi attributi `fontSize` e `fontFamily`, che contano solo finché è vuoto (in `app.css`: `p[data-line-size]` / `p[data-line-font]` con un solo `<br>`, con le variabili CSS `--line-size` e `--line-font`). Così cambiando da 10 a 12 pt più righe, anche quelle vuote in mezzo diventano alte 12 pt. Il formato di una riga vuota si decide così: scegliendo font o dimensione dalla barra con una selezione, anche le righe vuote selezionate lo prendono (comando `setEmptyLinesFormat`, chiamato dalla barra insieme a `setFontFamily`/`setFontSize`); con il cursore in una riga vuota, la riga prende i segni "in attesa" (font o dimensione scelti lì dalla barra, oppure portati da Invio dalla riga di sopra: Invio a fine di una riga a 12 pt crea una riga vuota a 12 pt) e, se è appena stata svuotata, il formato dell'ultimo pezzo di testo che aveva; scrivendo in una riga vuota, il testo prende il suo formato (il plugin lo mette nei segni "in attesa"). Gli attributi hanno `keepOnSplit: false`: con Invio il formato arriva dai segni, non dal paragrafo di sopra;
  - l'**interruzione di pagina**: il testo dopo il cursore va sulla pagina nuova, insieme al cursore; a fine riga si crea un paragrafo vuoto sulla pagina nuova; il nuovo paragrafo tiene l'allineamento ma non è mai una riga automatica. Si seleziona e si cancella come un carattere;
  - l'**editor Tiptap** (`resources/js/tab-editor.js`), configurato con paragrafi, interruzioni di pagina, a capo, grassetto, corsivo, sottolineato, font, dimensione, colore, evidenziatore a più colori, allineamento e annulla/ripeti; le scorciatoie "markdown" sono disattivate (in una tabulatura `*` e `_` sono testo). Il testo incollato senza formattazione diventa un paragrafo per riga, **comprese le righe vuote**. Il testo incollato con formattazione (dal foglio stesso, da un altro brano, da Word o da una pagina web) la conserva:
    - colore del testo: riconosciuto in qualsiasi forma (`#C00000`, `rgb(...)`, nomi come `red`) e salvato come `#RRGGBB`. Quasi nero diventa "Automatico"; quasi bianco viene ignorato, perché sul foglio bianco sarebbe invisibile (per esempio copiando da una pagina con il tema scuro);
    - evidenziatore: riconosciuto da `<mark>` e dagli sfondi del testo (`background-color`, e da Word `background` e `mso-highlight`). Diventa il colore più vicino di `Song::HIGHLIGHTS`; sfondi bianchi o lontani da ogni colore (per esempio lo sfondo di una pagina) vengono ignorati;
    - font e dimensione: puliti (virgolette tolte, solo il primo font; `10.0pt` → `10pt`, `px` convertiti in punti).
  
  A ogni modifica il documento JSON viene copiato nel campo nascosto `tab`, che parte con il form.
- **Righe automatiche** in cima al foglio, inserite dall'editor appena i campi sono compilati:
  1. "**Artista - Titolo**": centrata, grassetto, 16 pt (nei brani creati prima resta com'era, 14 pt: è scritta nel foglio);
  2. "**Strumento: Basso - Nome del gruppo**": centrata, corsivo, 8 pt. Contiene solo le parti spuntate con "Mostra nel foglio" ("Strumento: Basso", oppure solo il nome del gruppo); senza spunte (o con il gruppo vuoto e solo la sua spunta) la riga non c'è.
  
  Seguono i campi: se cambiano titolo, artista, strumento, gruppo o le spunte, le righe si aggiornano (togliendo tutte le spunte la seconda riga sparisce). Ogni riga ricorda il testo scritto in automatico (`autoText`): se nel foglio il testo è diverso, vuol dire che è stata **modificata a mano** e da lì in poi non viene più toccata. Andando a capo dentro una riga automatica, il nuovo paragrafo è normale.
- I valori iniziali vengono da `old()` se la validazione è fallita, altrimenti dal brano (o, per un brano nuovo, da un foglio vuoto).
- Gli errori di validazione compaiono sotto ogni campo.
- I pulsanti Salva e Annulla stanno in alto, accanto al titolo della pagina (`songs/_form-header.blade.php`), non in fondo al form.
- **Tasto Tab** nel foglio (estensione `TabKey` in `tab-editor.js`): non passa al campo successivo del form ma porta il cursore alla colonna successiva multipla di 4 (`TAB_SIZE`), contando i caratteri dall'inizio della riga (o dall'ultimo a capo). Usa **spazi**, non il carattere di tabulazione: con un font a larghezza fissa gli spazi restano allineati uguali sullo schermo e nel Word, mentre le tabulazioni del Word cadono in altre posizioni. Scrivendo normalmente inserisce gli spazi che servono (con del testo selezionato lo sostituisce); con la sovrascrittura accesa non cambia il testo e sposta solo il cursore a destra, allungando la riga con spazi se finisce prima. Maiuscolo+Tab fa come sempre (va al campo precedente), così con la tastiera si può uscire dal foglio.
- **Tasto Esc**: chiude la tavolozza aperta e nient'altro. Nel form non fa uscire dalla pagina, così non si perdono le modifiche per sbaglio (nell'anteprima invece Esc torna all'elenco).

---

## 6. Validazione

Con una Form Request `App\Http\Requests\SongRequest` (`authorize()` restituisce `true`: un solo utente, nessun login), usata sia da `store` sia da `update`:

```php
'artist'          => ['required', 'string', 'max:255'],
'title'           => ['required', 'string', 'max:255'],
'band'            => ['nullable', 'string', 'max:255'],
'instrument'      => ['required', Rule::in(array_column(Instrument::cases(), 'value'))], // bass, guitar, voice
'strings'         => ['nullable', Rule::in(Instrument::tryFrom($this->input('instrument'))?->strings() ?? [])],
'show_instrument' => ['required', 'boolean'],
'show_band'       => ['required', 'boolean'],
'tab'             => ['required', 'json', /* closure: il foglio deve contenere testo (TabDocument::hasText) */],
```

- La regola del foglio è una **closure**: decodifica il JSON e, se non contiene almeno un carattere, fallisce con il messaggio di `required` ("Il campo tabulatura è obbligatorio.").
- `strings` ammette solo i numeri di corde dello strumento scelto (basso 4 o 5, chitarra 6); con la voce, o con uno strumento non valido, nessun numero.
- Il metodo `songData()` restituisce i dati validati con `tab` già decodificato in array, pronto per il modello, e `strings`, se non è arrivato, uguale a `defaultStrings()` dello strumento (con la voce il menu è disattivato e il browser non lo invia: diventa null, e in modifica non resta il numero vecchio).
- I messaggi di errore sono in italiano grazie a `APP_LOCALE=it` e al file di traduzione **`lang/it/validation.php`**, scritto a mano: contiene solo le regole usate (`required`, `string`, `in`, `boolean`, `json`, `max.string`) e, in `attributes`, i nomi italiani dei campi (artista originale, titolo, il mio gruppo, strumento, numero di corde, mostra strumento, mostra gruppo, tabulatura). Per le regole non tradotte Laravel usa l'inglese (`APP_FALLBACK_LOCALE=en`).

---

## 7. Generazione del file .docx

Il documento Word è **esattamente il contenuto del foglio**: non c'è nessuna intestazione aggiunta dall'app (titolo, artista, strumento e gruppo stanno nelle righe automatiche del foglio).

### Classe `App\Services\TabDocxGenerator`
- `generate(Song $song): string`: crea il documento e restituisce il percorso del file temporaneo (creato con `tempnam()` nella cartella temporanea di sistema).
- `filename(Song $song): string`: nome `Artista - Titolo.docx` (dai campi del brano), ripulito dai caratteri non validi per Windows (`\ / : * ? " < > |`) e dagli spazi doppi; `tabulatura.docx` se resta vuoto.
- Il controller riceve il servizio per **dependency injection** e risponde con `response()->download($path, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->deleteFileAfterSend()`.

### Impaginazione del documento
| Elemento | Formato |
|---|---|
| Pagina | A4, margini 2 cm |
| Formattazione predefinita | Lucida Console 12 pt (`setDefaultFontName` / `setDefaultFontSize`, da `Song::DEFAULT_FONT` e `Song::DEFAULT_FONT_SIZE`) |
| Paragrafi | Uno per ogni paragrafo del foglio (`addTextRun`), con il suo allineamento; interlinea singola, spaziatura prima/dopo = 0; i paragrafi vuoti restano righe vuote. Una riga vuota con un font o una dimensione contiene uno spazio con quel formato, così Word le dà la stessa altezza che ha nel foglio |
| Interruzioni di pagina | Il paragrafo che segue un'interruzione ha `pageBreakBefore` ("Anteponi interruzione di pagina"): inizia su una pagina nuova. Meglio di `addPageBreak()`, che lascerebbe una riga vuota in cima alla pagina nuova |
| Testo | Ogni pezzo di testo (`addText`) con il suo font, la sua dimensione, grassetto, corsivo, sottolineato, colore (`color`, qualsiasi `#RRGGBB`; senza colore = automatico) ed evidenziatore (`fgColor` con il nome dell'evidenziatore di Word, es. `yellow`); gli a capo dentro un paragrafo diventano `addTextBreak()` |

### Regole importanti
- Il foglio si legge solo attraverso `TabDocument::paragraphs()`: nel Word finisce solo ciò che l'app conosce e ammette.
- **Mantenere gli spazi** all'inizio e dentro le righe, altrimenti la tabulatura si disallinea. PHPWord 1.4 scrive il testo con `xml:space="preserve"`, quindi gli spazi restano.
- **Attivare l'escaping** con `\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true)`: di default è spento e caratteri come `&` o `<` (es. armonici `<7>`) renderebbero il file illeggibile.
- Allineamento con `Jc::LEFT` / `Jc::CENTER` / `Jc::RIGHT`: i valori "classici" del formato .docx, letti da tutte le versioni di Word (PHPWord li segna come deprecati a favore di `START`/`END`, meno compatibili).
- La zona utile della pagina è 17 × 25,7 cm: a 12 pt ci stanno circa 66 caratteri per riga (a 10 pt circa 80). Il foglio del form ha le stesse misure e mostra già dove il testo andrà a capo e dove inizierà una nuova pagina.

---

## 8. Dati di prova

Esempio di tabulatura:

```
G|-----------------|-----------------|
D|-----------------|-----------------|
A|-----5---7-------|-----5---7---5---|
E|--5--------------|--5--------------|
```

Il **Seeder** `SongSeeder` inserisce tre brani con tabulature **inventate** (non di brani reali protetti), artista "Esercizi Tab Manager". Ogni foglio è costruito con `TabDocument::make()`: righe automatiche, una riga vuota, poi la tabulatura.
- "Groove di prova in La": basso a 4 corde, gruppo "I Prova Generale"; seconda riga "Strumento: Basso - I Prova Generale";
- "Linea a cinque corde": basso a 5 corde, senza gruppo; seconda riga "Strumento: Basso"; la tabulatura inizia con "Accordatura: B E A D G";
- "Arpeggio di studio": chitarra a 6 corde, gruppo "Trio Acustico"; seconda riga "Trio Acustico" (strumento non spuntato).

Il seeder parte da solo solo quando l'installazione crea un database nuovo. Per ricreare il database da zero con i dati di prova: `php artisan migrate:fresh --seed`. **Attenzione**: cancella tutti i brani dell'archivio in uso (quello di `DB_DATABASE`); prima fare un backup.

---

## 9. Creazione del progetto
- `laravel new tab-manager`: nessuno starter kit, database SQLite. Il `.gitignore` è quello di Laravel 13;
- `composer require phpoffice/phpword`;
- `npm install @tiptap/core @tiptap/pm @tiptap/starter-kit @tiptap/extension-text-style @tiptap/extension-text-align @tiptap/extension-highlight`;
- versioni: Laravel Framework 13.44.1, `phpoffice/phpword` 1.4.0 (con `phpoffice/math` 0.3.0), Tiptap 3.x;
- `.env` e `.env.example`: `APP_NAME="Tab Manager"`, `APP_LOCALE=it` (`DB_DATABASE` lo scrive `windows\setup.ps1`, sezione 2).

Le classi si creano con i comandi di Laravel: `php artisan make:model Song -m -s`, `make:enum Enums/Instrument --string`, `make:controller SongController --resource --model=Song`, `make:request SongRequest`, `make:class Services/TabDocxGenerator`.

---

## 10. Istruzioni per l'assistente

Quando ricevi questo file:
- Considera l'ambiente descritto nella sezione 2 (Windows, PowerShell 5.1, Smart App Control, percorso con spazi).
- Lavora **per gradi**, spiegando cosa fa ogni file e ogni comando. L'utente sta imparando PHP e Laravel.
- Rispondi in **italiano**.
- Mantieni l'app **semplice**: non aggiungere librerie o funzioni non previste senza chiedere.
- **Non gestire commit e push su git**: li fa l'utente quando lo ritiene opportuno.
- Se una decisione modifica quanto scritto qui, **aggiorna questo file** (sia nel Project sia nella cartella `docs/`), correggendo direttamente il punto interessato: le specifiche descrivono sempre l'app com'è, come se fosse stata progettata così fin dall'inizio, senza la storia delle modifiche.
- Il database contiene i brani veri dell'utente: le modifiche alla tabella si fanno con una **migration nuova** (mai riscrivendo quelle esistenti né con `migrate:fresh`).
