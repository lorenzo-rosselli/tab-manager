# Tab Manager – Specifiche del progetto

> Documento di riferimento per ricreare l'applicazione da zero.
> Da passare all'assistente all'inizio di una nuova sessione di lavoro.

---

## 1. Descrizione del programma

**Tab Manager** è una piccola applicazione web, personale e locale, per **scrivere, archiviare ed esportare in Word (.docx) tabulature per basso e chitarra**.

L'app **non cerca e non genera** le tabulature da sola. È l'utente che le scrive o le incolla, e l'app le salva in un archivio e le impagina in un documento Word pronto da stampare o da modificare.

### Obiettivi
- Essere **snella e leggerissima**: poche pagine, nessun framework JavaScript, nessun servizio esterno.
- Usare un solo utente, sul proprio computer, senza login.
- Servire anche come **progetto di studio** per imparare le basi di Laravel.

### Cosa NON fa (fuori dallo scopo)
- Non scarica le tabulature da siti esterni (niente scraping: ci sono problemi legali e di diritto d'autore).
- Non usa l'intelligenza artificiale per generare le note.
- Non trascrive dall'audio.
- Non ha più utenti, registrazione o login.

---

## 2. Ambiente di sviluppo

| Elemento | Valore |
|---|---|
| Sistema operativo | Windows 11 (con Smart App Control attivo) |
| PHP | 8.5.x, installato con **php.new** (Herd Lite) in `C:\Users\lorer\.config\herd-lite\bin` |
| Composer | 2.8.x (Herd Lite) |
| Laravel Installer | 5.33.x |
| Node.js / npm | Node v26 (installazione Windows) |
| Editor | Visual Studio Code |
| Terminale | Windows PowerShell 5.1 (consigliato PowerShell 7) |
| Cartella progetto | `D:\Workspaces\Rosse Production\Tab Manager` (repository GitHub `lorenzo-rosselli/tab-manager`, ramo `main`) |

### Note sull'ambiente (problemi già risolti)
- **Smart App Control** blocca i `php.exe` scaricati a mano da windows.php.net (errore "bloccato dai criteri di Device Guard"). Il PHP installato con php.new invece funziona. Non si possono aggiungere eccezioni per singole app.
- **Alternativa di riserva:** WSL con Ubuntu è già installato e configurato (PHP 8.5, Composer, Laravel Installer, Node). Se su Windows compare un nuovo blocco, si lavora lì, con i progetti in `~/progetti`.
- **PowerShell 5.1 non supporta `&&`**: i comandi vanno eseguiti uno alla volta, oppure si usa PowerShell 7.
- La **Execution Policy** è stata impostata a `RemoteSigned` per l'utente corrente, così `npm` funziona.
- Il percorso `Rosse Production\Tab Manager` contiene spazi: nei comandi va scritto **tra virgolette**.

---

## 3. Stack tecnologico

| Parte | Tecnologia | Note |
|---|---|---|
| Backend | **Laravel 13** su PHP 8.5 | Rotte, controller, validazione, Eloquent |
| Frontend | **Blade** + **Tailwind CSS** (via Vite) | HTML generato dal server, già incluso nel progetto Laravel |
| JavaScript | Nessuno (eventualmente **Alpine.js** in futuro) | Solo se serve un po' di interattività |
| Database | **SQLite** | File unico `database/database.sqlite`, predefinito in Laravel |
| Export Word | **PHPWord** (`phpoffice/phpword`) | Unica dipendenza aggiuntiva |
| Test | **Pest** | Predefinito in Laravel |
| Supporto sviluppo | **Laravel Boost** (`laravel/boost`, solo `require-dev`) | Installato da `laravel new`; fornisce linee guida agli assistenti AI tramite `AGENTS.md` e `CLAUDE.md`. Non fa parte dell'app: si tiene |

### Escluso volutamente
React, Vue, Livewire, Inertia, starter kit con autenticazione, MySQL/PostgreSQL.

---

## 4. Modello dati

### Tabella `songs`

| Campo | Tipo (migration) | Obbligatorio | Esempio |
|---|---|---|---|
| `id` | `id()` | sì | 1 |
| `title` | `string(255)` | sì | Another One Bites the Dust |
| `artist` | `string(255)` | sì | Queen |
| `instrument` | `string(10)`: valori `bass` / `guitar` | sì | bass |
| `tuning` | `string(50)` | sì | E A D G |
| `tab` | `longText` | sì | le righe della tabulatura |
| `notes` | `text`, nullable | no | Capotasto al 2° tasto |
| `created_at` / `updated_at` | `timestamps()` | auto | — |

### Modello `App\Models\Song`
- Campi compilabili: `title`, `artist`, `instrument`, `tuning`, `tab`, `notes`. Si dichiarano con l'attributo `#[Fillable([...])]` sopra la classe, lo stile di Laravel 13 (equivalente alla vecchia proprietà `$fillable`).
- Enum PHP `App\Enums\Instrument` (adottato) con i casi `Bass = 'bass'` e `Guitar = 'guitar'` e un metodo `label()` che restituisce "Basso" / "Chitarra". Il campo `instrument` è castato all'Enum nel metodo `casts()` del modello.
- Le tabelle predefinite di Laravel (`users`, `sessions`, `cache`, `jobs`…) restano: servono al framework. Il `DatabaseSeeder` però non crea più l'utente di prova e chiama solo `SongSeeder`.

### Valori predefiniti dell'accordatura (suggeriti nel form)
- Basso 4 corde: `E A D G`
- Basso 5 corde: `B E A D G`
- Chitarra 6 corde: `E A D G B E`

---

## 5. Pagine e rotte

| Metodo | URL | Azione controller | Nome rotta | Descrizione |
|---|---|---|---|---|
| GET | `/` | — | — | Redirect a `/songs` |
| GET | `/songs` | `index` | `songs.index` | Elenco brani; con `?q=testo` filtra per titolo o artista (`LIKE`) |
| GET | `/songs/create` | `create` | `songs.create` | Form nuovo brano |
| POST | `/songs` | `store` | `songs.store` | Salva nuovo brano |
| GET | `/songs/{song}` | `show` | `songs.show` | Anteprima della tabulatura |
| GET | `/songs/{song}/edit` | `edit` | `songs.edit` | Form modifica |
| PUT | `/songs/{song}` | `update` | `songs.update` | Aggiorna brano |
| DELETE | `/songs/{song}` | `destroy` | `songs.destroy` | Elimina brano |
| GET | `/songs/{song}/docx` | `download` | `songs.download` | Genera e scarica il .docx |

Implementazione suggerita:
```php
Route::redirect('/', '/songs');
Route::resource('songs', SongController::class);
Route::get('songs/{song}/docx', [SongController::class, 'download'])->name('songs.download');
```

### Viste Blade (`resources/views`)
Layout con `@extends('layouts.app')` / `@section` / `@yield` (scelta del 24/11/2026, al posto dei componenti Blade). La vista `welcome.blade.php` di Laravel è stata eliminata. Il layout carica solo il CSS con `@vite(['resources/css/app.css'])`: niente JavaScript e niente `@fonts` (si usano i font di sistema).

- `layouts/app.blade.php`: layout comune (titolo app, link "Nuovo brano", messaggi flash `session('status')`: "Brano salvato.", "Modifiche salvate.", "Brano eliminato.")
- `songs/index.blade.php`: casella di ricerca (form GET con icona lente e icona per annullare la ricerca), tabella con titolo, artista, strumento, data, e azioni (Vedi, Modifica, Scarica .docx, Elimina con conferma).
- Conferma di eliminazione (elenco e anteprima): **finestra modale** nel partial `songs/_delete.blade.php`, fatta con l'**API Popover** di HTML (`popover` + `popovertarget`), senza JavaScript. Mostra titolo e artista del brano; si chiude con l'icona Annulla, con Esc o cliccando fuori; il form DELETE parte solo dal pulsante di conferma. Sostituisce il `confirm()` del browser (scelta del 24/11/2026). Richiede un browser recente (Chrome/Edge 114+, Firefox 125+, Safari 17+).
- **Tutti i comandi dell'app sono icone** (elenco, anteprima, form, intestazione), con il nome come tooltip (attributo `title`) e `aria-label`. Resta testo solo il link dentro la frase dell'elenco vuoto ("Aggiungi il primo").
- `icons/*.blade.php` (`eye`, `pencil`, `download`, `trash`, `plus`, `arrow-left`, `save`, `x-mark`, `magnifying-glass`): icone SVG inline incluse con `@include('icons.nome')`, copiando l'SVG senza installare pacchetti. Fonti: Heroicons v2 outline (licenza MIT); il floppy `save` (pulsante Salva) viene da Lucide (licenza ISC), con lo spessore portato a 1.5.
- `songs/create.blade.php` e `songs/edit.blade.php`: usano un partial comune `songs/_form.blade.php`
- `songs/show.blade.php`: dati del brano e tabulatura in un blocco `<pre>` con font monospace

### Form (`_form.blade.php`)
- `title`: input di testo
- `artist`: input di testo
- `instrument`: select (Basso / Chitarra)
- `tuning`: input di testo, con i valori predefiniti suggeriti tramite `<datalist>` HTML (nessun JavaScript)
- `tab`: `<textarea>` grande, font monospace, `wrap="off"`, circa 20 righe, con sotto un avviso fisso sul limite di ~80 caratteri per riga nel Word
- `notes`: `<textarea>` piccola, opzionale
- Mostra gli errori di validazione sotto ogni campo e ripopola i campi con `old()`

---

## 6. Validazione

Con una Form Request `App\Http\Requests\SongRequest`, usata sia da `store` sia da `update`:

```php
'title'      => ['required', 'string', 'max:255'],
'artist'     => ['required', 'string', 'max:255'],
'instrument' => ['required', 'in:bass,guitar'],
'tuning'     => ['required', 'string', 'max:50'],
'tab'        => ['required', 'string'],
'notes'      => ['nullable', 'string'],
```

I messaggi di errore sono in italiano grazie a `APP_LOCALE=it` e al file di traduzione **`lang/it/validation.php`** (scelta del 24/11/2026). Il file contiene solo le regole usate (`required`, `string`, `in`, `max.string`) e, in `attributes`, i nomi italiani dei campi (titolo, artista, strumento, accordatura, tabulatura, note). Per le regole non tradotte Laravel usa l'inglese (`APP_FALLBACK_LOCALE=en`).

---

## 7. Generazione del file .docx

### Classe `App\Services\TabDocxGenerator`
- Metodo principale: `generate(Song $song): string`, che restituisce il percorso del file temporaneo creato (con `tempnam()` nella cartella temporanea di sistema).
- Metodo `filename(Song $song): string`: restituisce il nome `Artista - Titolo.docx` ripulito (fallback `tabulatura.docx` se resta vuoto).
- Metodo privato `lines()`: normalizza gli a capo e divide in righe; le righe vuote in fondo al testo vengono tolte, quelle in mezzo restano.
- Il controller riceve il servizio per dependency injection (`download(Song $song, TabDocxGenerator $generator)`) e lo restituisce con `response()->download($path, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->deleteFileAfterSend()`.

### Impaginazione del documento
| Elemento | Formato |
|---|---|
| Pagina | A4, margini 2 cm |
| Titolo brano | 18 pt, grassetto |
| Artista | 13 pt, corsivo |
| Riga info | "Strumento: Basso – Accordatura: E A D G" – 10 pt |
| Note (se presenti) | 10 pt, normale |
| Tabulatura | **Courier New 10 pt**, una riga del testo = un paragrafo, interlinea singola, spaziatura prima/dopo = 0 |

### Regole importanti
- **Mantenere gli spazi** all'inizio e dentro le righe, altrimenti la tabulatura si disallinea. Verificato: PHPWord 1.4 scrive il testo con `xml:space="preserve"`.
- **Attivare l'escaping** con `\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true)`: di default è spento e caratteri come `&` o `<` corromperebbero il file.
- Le righe vuote nella tabulatura restano righe vuote nel documento (separano i blocchi o le battute).
- Una riga di circa **80 caratteri** sta nella larghezza di una pagina A4 con Courier New 10 pt. Le righe più lunghe vanno a capo nel Word: nel form va mostrato un avviso.
- Normalizzare gli a capo (`\r\n` → `\n`).
- Nome file: `Artista - Titolo.docx`, ripulito dai caratteri non validi per Windows (`\ / : * ? " < > |`).

---

## 8. Esempio di tabulatura (dati di prova)

```
G|-----------------|-----------------|
D|-----------------|-----------------|
A|-----5---7-------|-----5---7---5---|
E|--5--------------|--5--------------|
```

Va creato un **Seeder** con 2–3 brani di esempio, con tabulature inventate (non di brani reali protetti), per provare l'app.
`SongSeeder` contiene tre esercizi inventati (artista "Esercizi Tab Manager"): basso 4 corde, basso 5 corde e chitarra.

---

## 9. Piano di sviluppo a tappe

1. **Setup** ✅ *(completata il 24/11/2026)*: progetto `laravel new tab-manager` (nessuno starter kit, Pest, SQLite), poi `composer require phpoffice/phpword`.
   - Poiché la cartella `Tab Manager` conteneva già il repository git, il progetto è stato creato in una cartella temporanea e poi spostato dentro `Tab Manager`, mantenendo `README.md`, `SPECIFICHE-TABULATURE.md` e il collegamento a GitHub. Il `.gitignore` è quello di Laravel 13.
   - Versioni installate: Laravel Framework 13.44.1, `phpoffice/phpword` 1.4.0 (con la dipendenza `phpoffice/math` 0.3.0).
   - `.env` e `.env.example`: `APP_NAME="Tab Manager"`, `APP_LOCALE=it` (i file di traduzione italiani arrivano nella tappa 6).
2. **Database** ✅ *(completata il 24/11/2026)*: migration `songs`, modello `Song`, Enum `Instrument`, seeder con dati di prova.
   - Verificato con `tinker`: 3 brani inseriti, `instrument->label()` restituisce "Basso".
3. **Rotte e controller** ✅ *(completata il 24/11/2026)*: `SongController` resource, Form Request `SongRequest`.
   - `download` risponde 501 finché non viene implementato nella tappa 5; messaggi flash e ricerca arrivano nella tappa 6.
4. **Viste** ✅ *(completata il 24/11/2026)*: layout, elenco, form (create/edit), anteprima.
   - Verificate nel browser; tutti i comandi sono icone con tooltip.
5. **Export Word** ✅ *(completata il 24/11/2026)*: servizio `TabDocxGenerator` e azione `download`.
   - Verificato in Word: impaginazione, allineamento della tabulatura, righe vuote, nome file e caratteri speciali (`&`, `<`, `>`).
6. **Rifiniture** ✅ *(completata il 24/11/2026)*: messaggi flash, conferma di eliminazione, ricerca per titolo/artista nell'elenco, traduzioni in italiano.
   - Verificato nel browser: messaggi di validazione in italiano, messaggi flash, modale di conferma (centrata con `m-auto h-fit`, perché il Preflight di Tailwind azzera i margini), ricerca.
7. **Test** (Pest): creazione, validazione, modifica, eliminazione, download del .docx (risposta 200 e content-type corretto).

### Possibili sviluppi futuri (non ora)
- Anteprima live della tabulatura mentre si scrive (Alpine.js)
- Trasposizione automatica di semitoni
- Esportazione anche in PDF o in .txt
- Importazione da MusicXML / Guitar Pro
- Editor battuta per battuta

---

## 10. Istruzioni per l'assistente

Quando ricevi questo file:
- Considera l'ambiente descritto nella sezione 2 (Windows, PowerShell 5.1, Smart App Control, percorso con spazi).
- Lavora **per gradi**, una tappa alla volta (sezione 9), spiegando cosa fa ogni file e ogni comando. L'utente sta imparando PHP e Laravel.
- Rispondi in **italiano**.
- Mantieni l'app **semplice**: non aggiungere librerie o funzioni non previste senza chiedere.
- **Non gestire commit e push su git**: li fa l'utente quando lo ritiene opportuno.
- Se una decisione modifica quanto scritto qui, **aggiorna questo file** (sia nel progetto sia nella cartella).
