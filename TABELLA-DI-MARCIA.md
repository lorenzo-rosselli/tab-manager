# Tab Manager – Tabella di marcia

> Piano operativo per lo sviluppo, tappa per tappa, basato su `SPECIFICHE-TABULATURE.md` (sezione 9).
> Aggiornato al 24/11/2026.
> I comandi sono per **PowerShell**, da lanciare **uno alla volta** nella cartella del progetto:
> `cd "D:\Workspaces\Rosse Production\Tab Manager"`

---

## Stato generale

| # | Tappa | Stato | Cosa produce |
|---|---|---|---|
| 1 | Setup | ✅ Completata | Progetto Laravel 13 pulito + PHPWord |
| 2 | Database | ✅ Completata | Tabella `songs`, modello `Song`, Enum `Instrument`, dati di prova |
| 3 | Rotte e controller | ✅ Completata | Rotte `/songs`, `SongController`, `SongRequest` |
| 4 | Viste | ✅ Completata | Layout, elenco, form, anteprima (Blade + Tailwind) |
| 5 | Export Word | ✅ Completata | Servizio `TabDocxGenerator`, download del `.docx` |
| 6 | Rifiniture | ⬜ Da fare | Messaggi flash, conferma eliminazione, ricerca, italiano |
| 7 | Test | ⬜ Da fare | Test Pest su tutte le funzioni principali |

Legenda: ✅ completata · 🔄 in corso · ⬜ da fare

---

## Tappa 1 – Setup ✅

- [x] `laravel new tab-manager` (nessuno starter kit, Pest, SQLite)
- [x] Progetto spostato in `Tab Manager`, collegamento a GitHub mantenuto
- [x] `composer require phpoffice/phpword` (1.4.0)
- [x] `.env` / `.env.example`: `APP_NAME="Tab Manager"`, `APP_LOCALE=it`
- [x] Verifica: `php artisan --version` → Laravel Framework 13.44.1

---

## Tappa 2 – Database ✅

**Obiettivo:** avere nel database SQLite la tabella dei brani e qualche brano di prova.

**Cosa impari:** migration, modelli Eloquent, attributo `#[Fillable]`, cast, Enum PHP, seeder.

**Comandi**
```powershell
php artisan make:model Song -m -s
php artisan make:enum Enums/Instrument --string
php artisan migrate
php artisan db:seed
```
- `make:model Song -m -s` crea il modello e, con `-m` e `-s`, anche la migration e il seeder.
- `make:enum` crea l'Enum `App\Enums\Instrument`.
- `migrate` crea la tabella nel database; `db:seed` inserisce i brani di prova.

**File coinvolti**
| File | Contenuto |
|---|---|
| `database/migrations/xxxx_create_songs_table.php` | Campi della tabella (sezione 4 delle specifiche) |
| `app/Models/Song.php` | Attributo `#[Fillable]` e cast di `instrument` all'Enum |
| `app/Enums/Instrument.php` | Casi `Bass` / `Guitar` e metodo `label()` |
| `database/seeders/SongSeeder.php` | 2–3 brani con tabulature **inventate** |
| `database/seeders/DatabaseSeeder.php` | Chiama solo `SongSeeder` (tolto l'utente di prova) |

**Avanzamento**
- [x] `make:model Song -m -s` e `make:enum Enums/Instrument --string` eseguiti
- [x] File compilati: migration, modello, Enum, `SongSeeder`, `DatabaseSeeder`
- [x] `php artisan migrate` e `php artisan db:seed`

**Fatto quando**
- [x] La migration gira senza errori
- [x] `php artisan tinker` → `App\Models\Song::count()` restituisce il numero dei brani di prova (`3`)
- [x] `App\Models\Song::first()->instrument->label()` restituisce "Basso" o "Chitarra" (`"Basso"`)

---

## Tappa 3 – Rotte e controller ✅

**Obiettivo:** definire tutti gli indirizzi dell'app e la logica che li gestisce.

**Cosa impari:** rotte resource, controller, route model binding, Form Request, redirect.

**Comandi**
```powershell
php artisan make:controller SongController --resource --model=Song
php artisan make:request SongRequest
php artisan route:list --path=songs
```
- Si usa **una sola** Form Request (`SongRequest`) per `store` e `update`, come da specifiche.
- `route:list` serve a controllare che le rotte siano tutte presenti.

**File coinvolti**
| File | Contenuto |
|---|---|
| `routes/web.php` | Redirect `/` → `/songs`, `Route::resource`, rotta `songs.download` |
| `app/Http/Controllers/SongController.php` | Azioni `index`, `create`, `store`, `show`, `edit`, `update`, `destroy` (e `download` vuota fino alla tappa 5) |
| `app/Http/Requests/SongRequest.php` | Regole di validazione (sezione 6) e `authorize()` → `true` |

**Scelte fatte**
- `store` e `update` ricevono `SongRequest` e usano `$request->validated()`; dopo il salvataggio redirect a `songs.show`, dopo l'eliminazione a `songs.index`.
- `download` è un segnaposto che risponde 501 fino alla tappa 5.
- Messaggi flash e ricerca rinviati alla tappa 6, come da piano.

**Fatto quando**
- [x] `route:list` mostra le 7 rotte `songs.*` + `songs.download`
- [x] Ogni azione restituisce la vista giusta o fa il redirect corretto (verificato nella tappa 4)

---

## Tappa 4 – Viste ✅

**Obiettivo:** vedere e usare l'app nel browser: elenco, inserimento, modifica, anteprima.

**Cosa impari:** Blade (layout, `@extends`/`@section` o componenti, `@foreach`, `@error`, `old()`, `@csrf`, `@method`), Tailwind via Vite.

**Comandi** (in due terminali separati)
```powershell
php artisan serve
```
```powershell
npm run dev
```
- `serve` avvia l'app su `http://127.0.0.1:8000`.
- `npm run dev` compila gli stili Tailwind e li aggiorna mentre modifichi i file.

**File coinvolti**
| File | Contenuto |
|---|---|
| `resources/views/layouts/app.blade.php` | Struttura comune, titolo, link "Nuovo brano", messaggi flash |
| `resources/views/songs/index.blade.php` | Tabella brani con azioni |
| `resources/views/songs/_form.blade.php` | Campi del form, errori sotto ogni campo, `old()` |
| `resources/views/songs/create.blade.php` | Form nuovo brano |
| `resources/views/songs/edit.blade.php` | Form modifica |
| `resources/views/songs/show.blade.php` | Dati del brano + tabulatura in `<pre>` monospace |
| `resources/views/icons/*.blade.php` | Icone SVG (Vedi, Modifica, Scarica, Elimina, Nuovo, Indietro, Salva, Annulla) |

**Scelte fatte**
- Layout con `@extends` / `@section` (non componenti Blade).
- Eliminata `resources/views/welcome.blade.php`.
- Il layout carica solo `resources/css/app.css` (niente JS, niente `@fonts`).
- Il layout mostra già il messaggio flash `session('status')`: i messaggi veri li imposta il controller nella tappa 6.
- Elimina: per ora senza conferma (arriva nella tappa 6).
- Accordature suggerite con `<datalist>`; avviso fisso sugli 80 caratteri sotto la tabulatura.
- Tutti i comandi dell'app sono icone SVG inline (Heroicons MIT; floppy "Salva" da Lucide ISC; nessun pacchetto) con tooltip `title` e `aria-label`: elenco, anteprima, form (Salva/Annulla) e "Nuovo brano" nell'intestazione.

**Avanzamento**
- [x] Sei viste create, `welcome.blade.php` eliminata
- [x] Verifica nel browser (tutto ok; download 501 come previsto)
- [x] Verifica delle icone in tutte le pagine

**Fatto quando**
- [x] Si può creare, vedere, modificare ed eliminare un brano dal browser
- [x] La tabulatura nel `<textarea>` e nel `<pre>` resta allineata (font monospace, `wrap="off"`)
- [x] Gli errori di validazione compaiono sotto i campi e i dati inseriti non si perdono

---

## Tappa 5 – Export Word ✅

**Obiettivo:** scaricare un brano come file `.docx` impaginato.

**Cosa impari:** classi di servizio, dependency injection, uso di una libreria esterna (PHPWord), risposte di download.

**Comandi**
```powershell
php artisan make:class Services/TabDocxGenerator
```

**File coinvolti**
| File | Contenuto |
|---|---|
| `app/Services/TabDocxGenerator.php` | `generate(Song $song): string` con l'impaginazione della sezione 7; `filename()` per il nome del file; `lines()` privato per dividere il testo in righe |
| `app/Http/Controllers/SongController.php` | Azione `download` con dependency injection del servizio, `Content-Type` .docx esplicito e `->deleteFileAfterSend()` |

**Note tecniche (verificate su PHPWord 1.4.0)**
- PHPWord scrive il testo con `xml:space="preserve"`: gli spazi iniziali e interni vengono mantenuti.
- L'escaping dei caratteri speciali è **disattivato** di default: va attivato con `Settings::setOutputEscapingEnabled(true)`, altrimenti un `&` o un `<` nel titolo o nella tabulatura (es. armonici `<7>`) rende il file .docx illeggibile.

**Punti da verificare con attenzione**
- [x] Pagina A4, margini 2 cm; titolo 18 pt grassetto, artista 13 pt corsivo, riga info e note 10 pt
- [x] Tabulatura in Courier New 10 pt, una riga = un paragrafo, spaziatura 0
- [x] **Gli spazi vengono preservati** (aprire il file in Word e controllare l'allineamento)
- [x] Le righe vuote restano righe vuote; gli a capo `\r\n` sono normalizzati
- [x] Nome file `Artista - Titolo.docx` senza caratteri non validi per Windows
- [x] Caratteri speciali (`&`, `<`, `>`) nel titolo e nella tabulatura: il file si apre senza errori
- [x] Avviso nel form per le righe oltre ~80 caratteri (testo fisso sotto la textarea, tappa 4)

---

## Tappa 6 – Rifiniture

**Obiettivo:** rendere l'app comoda da usare e tutta in italiano.

**Cosa impari:** session flash, query con `where`/`like`, query string, localizzazione.

**Attività**
- [ ] Messaggi flash dopo salvataggio, modifica, eliminazione ("Brano salvato", ecc.)
- [ ] Conferma prima dell'eliminazione (`onsubmit="return confirm(...)"`, nessuna libreria)
- [ ] Ricerca per titolo/artista nell'elenco (`/songs?q=...`)
- [ ] Messaggi di validazione in italiano

**Decisione da prendere prima di iniziare:** per i messaggi in italiano le specifiche indicano due strade:
1. scrivere a mano `lang/it/validation.php` (servono solo i messaggi delle regole usate), oppure
2. messaggi personalizzati nel metodo `messages()` di `SongRequest`.

Un pacchetto di traduzioni esterno sarebbe una dipendenza in più, quindi va concordato prima di usarlo.

---

## Tappa 7 – Test

**Obiettivo:** verificare in automatico che le funzioni principali non si rompano.

**Cosa impari:** Pest, test di feature, database di test, asserzioni sulle risposte HTTP.

**Comandi**
```powershell
php artisan make:test SongTest
php artisan test
```

**Test da scrivere**
- [ ] Creazione di un brano valido (redirect + record nel database)
- [ ] Validazione: campi obbligatori mancanti, `instrument` non valido
- [ ] Modifica di un brano
- [ ] Eliminazione di un brano
- [ ] Download `.docx`: risposta 200 e content-type
      `application/vnd.openxmlformats-officedocument.wordprocessingml.document`

> Per creare i brani nei test può servire una **factory** (`php artisan make:factory SongFactory`): è uno strumento standard di Laravel, da aggiungere qui se utile.

---

## Dopo la tappa 7 (non ora)

Dalle specifiche, sviluppi futuri da valutare solo a progetto finito:
anteprima live (Alpine.js), trasposizione di semitoni, export PDF/.txt, import MusicXML/Guitar Pro, editor battuta per battuta.

---

## Regole di lavoro

- Una tappa alla volta, con spiegazione di ogni file e comando.
- Niente librerie o funzioni non previste senza chiedere.
- Ogni decisione che cambia le specifiche va riportata in `SPECIFICHE-TABULATURE.md`.
- Commit e push li gestisce l'utente: la fine di ogni tappa è un buon momento per farli.
