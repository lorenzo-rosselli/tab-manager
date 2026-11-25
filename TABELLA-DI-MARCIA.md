# Tab Manager – Tabella di marcia

> Piano operativo per lo sviluppo, tappa per tappa, basato su `SPECIFICHE-TABULATURE.md` (sezione 9).
> Aggiornato al 25/11/2026.
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
| 6 | Rifiniture | 🔄 In corso | Messaggi flash, conferma eliminazione, ricerca, italiano; via accordatura e note, Gruppo, ordinamento multiplo, foglio e barra stile Word, riga cliccabile, tema scuro; editor di testo ricco, righe automatiche, colore ed evidenziatore; Artista prima di Titolo, interruttore del tema; azzera ordinamento sopra la tabella; ricerca per strumento, Esc, tavolozze, formattazione incollata, testo della modale, strumento Voce |
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
- Accordature suggerite con `<datalist>`; avviso fisso sugli 80 caratteri sotto la tabulatura. *(Superato nella tappa 6: accordatura tolta, la tabulatura si scrive su un foglio stile Word.)*
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

> 25/11/2026 (tappa 6): la riga info del Word contiene solo lo strumento; font e dimensione dipendono dal brano; le note non ci sono più.

---

## Tappa 6 – Rifiniture 🔄

**Obiettivo:** rendere l'app comoda da usare, tutta in italiano, e adattarla al modo in cui la uso davvero.

**Cosa impari:** session flash, query con `where`/`like`, query string, localizzazione, ordinamento dinamico con `orderBy`, costanti e `$attributes` nel modello, `Rule::in`, CSS in unità fisiche (cm), JavaScript con un pacchetto npm caricato da Vite, dati JSON in una colonna (cast `array`), regole di validazione con closure.

### Parte 1 – Rifiniture previste (24/11/2026) ✅

**Attività**
- [x] Messaggi flash dopo salvataggio, modifica, eliminazione ("Brano salvato", ecc.)
- [x] Conferma prima dell'eliminazione con finestra modale (API Popover di HTML, nessun JavaScript; sostituisce il `confirm()` del browser)
- [x] Ricerca nell'elenco (`/songs?q=...`)
- [x] Messaggi di validazione in italiano

**Decisione presa:** messaggi in italiano con il file di traduzione `lang/it/validation.php` scritto a mano (solo le regole usate + nomi dei campi in `attributes`). Nessun pacchetto esterno.

**File coinvolti**
| File | Contenuto |
|---|---|
| `lang/it/validation.php` | Messaggi delle regole usate e nomi italiani dei campi |
| `app/Http/Controllers/SongController.php` | `index(Request)` con ricerca `?q=` (`when` + `where`/`orWhere` `like`); `->with('status', ...)` dopo store/update/destroy |
| `resources/views/songs/index.blade.php` | Form di ricerca GET, messaggio "nessun brano trovato", include `_delete` |
| `resources/views/songs/show.blade.php` | Include `_delete` |
| `resources/views/songs/_delete.blade.php` | Pulsante cestino + modale di conferma (Popover API) con il form DELETE |
| `resources/views/icons/magnifying-glass.blade.php` | Icona lente (Heroicons, MIT) |

**Verifica**
- [x] Verifica nel browser
- [x] Modale centrata: aggiunte le classi `m-auto h-fit` (il Preflight di Tailwind azzera il `margin: auto` del popover)

### Parte 2 – Nuove rifiniture (25/11/2026) ✅

**Richieste**
1. Togliere l'**accordatura** (se non è standard si scrive nella tabulatura).
2. Aggiungere il campo facoltativo **Gruppo** (`band`, la band con cui suono il brano): nell'elenco, nel form, nell'anteprima e nella ricerca. Non nel Word.
3. **Ordinamento multiplo** dell'elenco su tutte le colonne: crescente → decrescente → tolta, accumulabile nell'ordine di scelta, valido anche durante la ricerca.
4. Togliere le **note**.
5. **Font** del brano, valido per tutto il documento Word e per il foglio della tabulatura (default Lucida Console).
6. **Dimensione** dei caratteri della tabulatura, ripresa nel .docx.
7. La tabulatura si scrive su un **foglio stile Word**: foglio centrato, margini che impediscono di scrivere all'infinito, nessun righello ma limite ben visibile.
8. Nell'elenco si apre l'anteprima **cliccando ovunque sulla riga**, al posto dell'icona "Vedi".
9. **Allineamento** (sinistra, centro, destra), **grassetto**, **corsivo** e **sottolineato** della tabulatura, in una **barra di formattazione** simile a quella di Word, dentro il riquadro della tabulatura (insieme a font e dimensione).
10. **Tema scuro** con tonalità notturne classiche: testi bianchi, campi e foglio della tabulatura bianchi.

**Decisioni**
- Database: riscritta la migration originale di `songs` (via `tuning` e `notes`; nuovi `band`, `font`, `font_size`) e ricreato il database con `migrate:fresh --seed`: si perdono i brani inseriti a mano.
- Ordinamento nell'indirizzo: `?sort=artist,-title` (`-` = decrescente), chiavi `title`, `artist`, `band`, `instrument`, `date`. Nessun JavaScript. Senza ordinamento: dal più recente. I gruppi vuoti vengono prima in ordine crescente (comportamento di SQLite).
- Font: menu con soli font monospaziati (`Song::FONTS`: Lucida Console, Courier New, Consolas).
- Dimensione: menu 8, 9, 10, 11, 12, 14 pt (`Song::FONT_SIZES`), default 10.
- Foglio: A4 21 cm centrato nello schermo, margini 2 cm, zona scrivibile di 17 cm (come il .docx) segnata da un bordo tratteggiato; il testo parte dal margine sinistro e va a capo al margine destro (`wrap="soft"`). Anche l'anteprima del brano usa lo stesso foglio.
- La formattazione scelta nella barra si applica subito al foglio con **un piccolo script JavaScript nativo** nel form: unica eccezione alla regola "niente JavaScript".
- Riga cliccabile senza JavaScript: link del titolo esteso a tutta la riga con `after:absolute after:inset-0`; la cella delle azioni sta sopra (`relative z-10`). Eliminata l'icona `eye`.
- Barra di formattazione: font e dimensione come menu; **G** / *C* / <u>S</u> (come nel Word italiano) come caselle di spunta; allineamento come radio. Aspetto da pulsante con `has-checked:`. Formattazione per tutta la tabulatura (una textarea non permette di formattare singole parole). Nel Word: `bold`, `italic`, `underline` sul font e `Jc::LEFT/CENTER/RIGHT` sui paragrafi della tabulatura.
- Nuove colonne `text_align` (default `left`), `bold`, `italic`, `underline` (default `false`), ancora riscrivendo la migration originale (`migrate:fresh --seed`).
- Tema scuro: grigi Tailwind `gray` (sfondo 900, intestazione 950, riquadri 800, bordi 700), testi bianchi; campi e foglio bianchi con testo scuro (`scheme-light`), resto della pagina `scheme-dark`.

**File coinvolti**
| File | Modifica |
|---|---|
| `database/migrations/2026_11_24_182440_create_songs_table.php` | Tolti `tuning` e `notes`; aggiunti `band` (nullable), `font` (default Lucida Console), `font_size` (default 10), `text_align` (default left), `bold`, `italic`, `underline` (default false) |
| `app/Models/Song.php` | `#[Fillable]` aggiornato; costanti `FONTS`, `FONT_SIZES`, `ALIGNMENTS`; `$attributes` con i default; cast integer/boolean; metodo `tabStyle()` |
| `app/Http/Requests/SongRequest.php` | Regole per `band`, `font`, `font_size`, `text_align`, `bold`, `italic`, `underline`; tolte `tuning` e `notes` |
| `lang/it/validation.php` | Regole `integer` e `boolean`; nomi "gruppo", "font", "dimensione caratteri", "allineamento", "grassetto", "corsivo", "sottolineato" |
| `database/seeders/SongSeeder.php` | Tolte accordatura e note; gruppo a due brani; il brano di chitarra in Courier New 11 pt |
| `app/Services/TabDocxGenerator.php` | Font del brano per tutto il documento; dimensione, grassetto, corsivo, sottolineato e allineamento del brano per la tabulatura; riga info solo "Strumento: …"; tolte le note |
| `app/Http/Controllers/SongController.php` | Ricerca anche su `band`; ordinamento con `SORTABLE`, `parseSorts()`, `sortParam()`, `sortColumns()` |
| `resources/views/layouts/app.blade.php` | Tema scuro (`scheme-dark`, sfondi `gray`, testi bianchi), flash verde scuro, pulsante "Nuovo brano" chiaro |
| `resources/views/songs/index.blade.php` | Colonna Gruppo, intestazioni ordinabili con freccia e numero di priorità, "Azzera ordinamento", `sort` conservato nella ricerca, riga cliccabile, tolta l'icona Vedi, tema scuro |
| `resources/views/songs/_form.blade.php` | Via accordatura (e `<datalist>`) e note; campo Gruppo; riquadro tabulatura con barra di formattazione (font, dimensione, G/C/S, allineamento) e foglio stile Word; script per applicare la formattazione; campi bianchi |
| `resources/views/songs/show.blade.php` | Riga "Strumento – Gruppo"; tabulatura sul foglio A4 con `tabStyle()`; tolte le note; tema scuro |
| `resources/views/songs/_delete.blade.php` | Modale e cestino nei colori del tema scuro |
| `resources/views/icons/` | Nuove `chevron-up`, `chevron-down`, `arrows-up-down` (Heroicons, MIT; `arrows-up-down` poi eliminata nella parte 6) e `align-left`, `align-center`, `align-right`; eliminata `eye` |

**Avanzamento**
- [x] Codice scritto
- [x] `php artisan migrate:fresh --seed`
- [x] Verifica nel browser di gruppo, ricerca, ordinamento, foglio, font e dimensione ("quello che vedo per ora mi piace")
- [x] Verifica nel browser: riga cliccabile, barra di formattazione, tema scuro
- [x] Verifica in Word (font su tutto il documento; dimensione, G/C/S e allineamento della tabulatura; niente note né accordatura)

### Parte 3 – Editor di testo ricco e righe automatiche (25/11/2026) 🔄

**Richieste**
11. I comandi di formattazione (font, dimensione, G/C/S, allineamento) devono valere per **singole parti del testo**, non per tutto il foglio.
12. Il foglio è lo **stato finale del documento .docx**. In prima riga "Artista - Titolo" (centrati, grassetto, 14 pt), inseriti automaticamente quando i campi sono compilati, ma poi modificabili nel foglio e anche diversi dai campi.
13. Accanto a **Strumento** e **Gruppo** una spunta che aggiunge il relativo valore sotto "Artista - Titolo" (centrato, corsivo, 8 pt), con lo stesso comportamento automatico/modificabile.

**Decisioni**
- In prima riga va l'**Artista** (es. "Queen - Another One Bites the Dust"); strumento e gruppo con le spunte vanno sotto.
- Editor di testo ricco **Tiptap 3** (MIT), installato con npm e caricato da Vite solo nel form: seconda dipendenza dell'app (lato JavaScript). Sostituisce il piccolo script della parte 2.
- Il foglio si salva in `tab` come **JSON di Tiptap** (cast `array`). Tolte le colonne di formattazione globale (`font`, `font_size`, `text_align`, `bold`, `italic`, `underline`) e il metodo `tabStyle()`; aggiunte `show_instrument` e `show_band`.
- Righe automatiche: **seguono i campi finché non vengono modificate a mano** (attributi di paragrafo `auto` e `autoText`); togliendo la spunta la riga sparisce, se non è stata modificata.
- Il **Word è esattamente il foglio**: tolta l'intestazione automatica (titolo 18 pt, artista 13 pt, riga "Strumento").
- Dimensioni disponibili: 8, 9, 10, 11, 12, 14, 16, 18 pt; testo senza formattazione propria: Lucida Console 10 pt.
- Nuova classe `App\Services\TabDocument`: costruisce, legge in modo sicuro e trasforma in HTML il foglio; la usano seeder, validazione, anteprima e Word.
- Incolla: ogni riga diventa un paragrafo, righe vuote comprese. Scorciatoie markdown disattivate.
- Ancora riscritta la migration originale (`migrate:fresh --seed`).

**File coinvolti**
| File | Modifica |
|---|---|
| `package.json` / `package-lock.json` | Pacchetti Tiptap (li aggiunge `npm install`) |
| `vite.config.js` | Nuovo ingresso `resources/js/tab-editor.js` |
| `resources/js/tab-editor.js` | Nuovo: editor Tiptap, barra di formattazione, righe automatiche, incolla, copia del JSON nel campo `tab` |
| `resources/css/app.css` | Classe `.tab-sheet` del foglio |
| `database/migrations/2026_11_24_182440_create_songs_table.php` | Via le colonne di formattazione; `show_instrument`, `show_band`; `tab` contiene JSON |
| `app/Models/Song.php` | Nuovi fillable e cast (`tab` → array); costanti `DEFAULT_FONT`, `DEFAULT_FONT_SIZE`; dimensioni fino a 18; tolto `tabStyle()` |
| `app/Services/TabDocument.php` | Nuovo: `empty()`, `make()`, `paragraphs()`, `hasText()`, `toHtml()` |
| `app/Http/Requests/SongRequest.php` | Regole per le spunte; `tab` JSON con closure "deve contenere testo"; metodo `songData()` |
| `lang/it/validation.php` | Regola `json`; nomi "mostra strumento", "mostra gruppo"; tolti i nomi della formattazione globale |
| `app/Http/Controllers/SongController.php` | `store`/`update` usano `songData()` |
| `app/Services/TabDocxGenerator.php` | Il Word è il foglio: un paragrafo per paragrafo, un pezzo di testo per pezzo con la sua formattazione |
| `database/seeders/SongSeeder.php` | Fogli costruiti con `TabDocument::make()` |
| `resources/views/songs/_form.blade.php` | Spunte "Mostra nel foglio"; barra con pulsanti che agiscono sulla selezione; contenitore dell'editor; campo nascosto `tab`; `@vite` dello script |
| `resources/views/songs/show.blade.php` | Foglio con `TabDocument::toHtml()` |

**Verifiche fatte in fase di sviluppo** (simulazione dell'editor con jsdom e prova di `TabDocument`/`TabDocxGenerator` con PHP)
- [x] Righe automatiche: compaiono, si aggiornano, scompaiono togliendo la spunta, restano se modificate a mano
- [x] Formattazione di una sola parte del testo; incolla con righe vuote
- [x] HTML dell'anteprima sicuro (escaping, font/dimensioni non ammessi ignorati)
- [x] Word: spazi conservati, caratteri speciali, centratura, 14 pt

**Avanzamento**
- [x] Codice scritto
- [x] `npm install` dei pacchetti Tiptap e riavvio di `npm run dev`
- [x] `php artisan migrate:fresh --seed`
- [x] Prima prova nel browser: da qui le richieste della parte 4

### Parte 4 – Riga unica strumento/gruppo, colore ed evidenziatore (25/11/2026) 🔄

**Richieste**
14. Strumento e gruppo sulla **stessa riga**, nella forma "Strumento: Basso - Nome del gruppo".
15. Nell'editor il **colore del testo** (automatico = nero) e l'**evidenziatore**.

**Decisioni**
- Una sola riga automatica `info` al posto di `instrument` e `band`: contiene solo le parti spuntate ("Strumento: Basso", il nome del gruppo, o entrambe separate da " - "). Stessa regola in `TabDocument::infoLine()` e nello script.
- Colori del testo: i "colori standard" di Word (`Song::TEXT_COLORS`) più "Automatico" (nero, nessun colore salvato).
- Evidenziatore: tavolozza fissa (`Song::HIGHLIGHTS`), perché Word ammette solo i suoi colori di evidenziazione; nel .docx diventa un vero evidenziatore (`w:highlight`, es. `yellow`).
- Nella barra: "A" con barretta colorata e icona evidenziatore (Lucide, ISC) con barretta; tavolozze in menu a tendina `<details>`. Il contenitore del riquadro non ha più `overflow-hidden`, per non tagliare i menu.
- Nuovo pacchetto `@tiptap/extension-highlight` (stessa famiglia Tiptap, MIT).

**File coinvolti**
| File | Modifica |
|---|---|
| `package.json` / `package-lock.json` | `@tiptap/extension-highlight` |
| `resources/js/tab-editor.js` | Riga automatica unica `info`; estensioni colore ed evidenziatore; gestione tavolozze e barrette |
| `app/Models/Song.php` | Costanti `TEXT_COLORS` e `HIGHLIGHTS` |
| `app/Services/TabDocument.php` | `infoLine()`; `make()` con la riga unica; lettura di colore ed evidenziatore (solo valori ammessi); HTML con `color` e `<mark>` |
| `app/Services/TabDocxGenerator.php` | `color` e `fgColor` (evidenziatore di Word) sui pezzi di testo |
| `resources/views/songs/_form.blade.php` | Pulsanti colore ed evidenziatore con tavolozze |
| `resources/views/icons/highlighter.blade.php` | Nuova icona (Lucide, ISC) |

**Verifiche fatte in fase di sviluppo**
- [x] Editor (jsdom): riga "Strumento: Basso - I Rumorosi", aggiornamento con strumento/spunte, solo gruppo senza strumento; colore ed evidenziatore su una sola parola, "Automatico" toglie il colore
- [x] Word (PHP): riga info, colore `C00000`, evidenziatore `yellow`, colore non ammesso ignorato

**Avanzamento**
- [x] Codice scritto
- [x] `npm install @tiptap/extension-highlight` e riavvio di `npm run dev`
- [x] Prova nel browser: da qui le richieste della parte 5

### Parte 5 – Artista prima di Titolo, interruttore del tema (25/11/2026) 🔄

**Richieste**
16. **Artista prima di Titolo** nella tabella dei brani e nei campi dei form (non nel contenuto del foglio).
17. **Interruttore del tema** chiaro/scuro in alto, a sinistra del pulsante "Nuovo brano", a forma di interruttore con sole e luna (come nell'immagine di riferimento).

**Decisioni**
- Colonne dell'elenco: Artista, Titolo, Gruppo, Strumento, Data (ordine dato da `SORTABLE`). Il link "esteso" a tutta la riga resta sul titolo.
- Due temi: ogni classe ha la versione chiara e quella `dark:`. Variante `dark` legata alla classe `dark` su `<html>` (`@custom-variant` in `app.css`), non al sistema operativo. Predefinito: scuro.
- L'interruttore usa **poche righe di JavaScript** (`resources/js/app.js`): senza, cambiare tema ricaricherebbe la pagina e nel form si perderebbe il lavoro. La scelta si salva nel cookie `theme`; il layout lo legge e scrive subito la classe giusta (niente "lampo" al caricamento).
- Il cookie `theme` è escluso dalla cifratura dei cookie di Laravel (`bootstrap/app.php`), perché lo scrive il browser.
- Icone `sun` e `moon` (Heroicons, MIT); la luna è piena (`fill-current`).

**File coinvolti**
| File | Modifica |
|---|---|
| `app/Http/Controllers/SongController.php` | `SORTABLE`: `artist` prima di `title` |
| `resources/views/songs/index.blade.php` | Colonne Artista, Titolo; classi chiare + `dark:` |
| `resources/views/songs/_form.blade.php` | Campo Artista prima di Titolo; classi chiare + `dark:` (campi, spunte, barra, tavolozze, scrivania) |
| `resources/views/layouts/app.blade.php` | Lettura del cookie e classe del tema su `<html>`; interruttore; classi chiare + `dark:`; `@vite` anche di `app.js` |
| `resources/views/songs/show.blade.php`, `_delete.blade.php` | Classi chiare + `dark:` |
| `resources/css/app.css` | `@custom-variant dark` |
| `resources/js/app.js` | Interruttore: classe `dark`, `scheme-*`, `aria-checked`, cookie `theme` |
| `bootstrap/app.php` | `encryptCookies(except: ['theme'])` |
| `resources/views/icons/sun.blade.php`, `moon.blade.php` | Nuove icone (Heroicons, MIT) |

**Verifiche fatte in fase di sviluppo**
- [x] Compilazione Tailwind: le classi `dark:` (anche combinate con `aria-pressed:` e `backdrop:`) vengono generate e valgono solo dentro `.dark`

**Avanzamento**
- [x] Codice scritto
- [ ] Riavvio di `npm run dev` (nuovo CSS e `app.js`)
- [ ] Verifica nel browser: ordine dei campi, interruttore (anche nel form senza perdere il testo), tema mantenuto cambiando pagina
- [ ] Verifica di colore ed evidenziatore in Word (parte 4)

### Parte 6 – Azzera ordinamento sopra la tabella (25/11/2026) 🔄

**Richiesta**
18. Il **reset dell'ordinamento** deve apparire **sopra la tabella** quando è stato scelto almeno un ordinamento.

**Decisioni**
- Sopra la tabella, solo con un ordinamento attivo, la riga "Ordinato per:" con le colonne ordinate in ordine di priorità (pillole con freccia) e l'icona ✕ "Azzera ordinamento" (mantiene la ricerca).
- Tolta l'icona "Azzera ordinamento" accanto alla ricerca (si notava poco) ed eliminata l'icona `arrows-up-down`, non più usata.

**File coinvolti**
| File | Modifica |
|---|---|
| `resources/views/songs/index.blade.php` | Riga "Ordinato per:" con pillole e icona ✕ sopra la tabella; tolta l'icona accanto alla ricerca |
| `resources/views/icons/arrows-up-down.blade.php` | Eliminata |

**Avanzamento**
- [x] Codice scritto
- [ ] Verifica nel browser

### Parte 7 – Correzioni dopo le prove (25/11/2026) 🔄

Dalla lista di prove della tappa 6 (52 prove su tema, elenco, eliminazione, form, editor, validazione, anteprima e Word).

**Richieste**
19. **Ricerca anche per strumento** (es. "basso").
20. Nel form (nuovo brano e modifica) e nell'anteprima del brano il tasto **Esc** porta all'elenco.
21. Le tavolozze di **colore ed evidenziatore** si chiudono anche **cliccando in qualsiasi punto** della pagina (prima restavano aperte e si sovrapponevano).
22. Il testo **incollato** con colore o evidenziatore deve **mantenerli**.
23. Testo della modale di eliminazione: "Artista - Titolo verrà eliminato definitivamente".
24. Tra gli strumenti anche la **Voce**.

**Decisioni**
- Ricerca per strumento: si confronta il testo con le etichette italiane dell'Enum (`label()`) e si filtra con `orWhereIn('instrument', ...)`. Basta anche una parte del nome ("chit").
- Esc nel form: se è aperta una tavolozza la chiude, altrimenti va all'elenco (non alla pagina precedente) e le modifiche non salvate si perdono, come richiesto.
- Esc nell'anteprima: gestito da `resources/js/app.js` (già caricato in tutte le pagine) per gli elementi con `data-escape-to`. Se è aperta la modale di eliminazione, Esc chiude solo quella.
- Tavolozze: un ascoltatore sul clic di tutta la pagina chiude quelle aperte se il clic è fuori; così ne resta aperta al massimo una.
- Causa del problema dell'incolla: il browser legge i colori incollati come `rgb(192, 0, 0)`, mentre il server ammetteva solo i valori esadecimali della tavolozza e li scartava al salvataggio. Inoltre l'evidenziatore era riconosciuto solo dentro `<mark>`, mentre Word e le pagine web usano gli sfondi del testo.
- Soluzione nell'editor: la formattazione incollata viene ripulita (`transformPasted`). Il colore del testo diventa `#RRGGBB`, con il quasi nero trattato come "Automatico" e il quasi bianco ignorato. L'evidenziatore, riconosciuto anche dagli sfondi, diventa il più vicino tra quelli di Word, mentre gli sfondi bianchi o lontani vengono ignorati. Già che c'eravamo, si puliscono anche font e dimensione incollati da Word (`"Lucida Console"` con le virgolette, `10.0pt`).
- Il colore del testo non è più limitato alla tavolozza: il foglio e il Word ammettono qualsiasi `#RRGGBB` (Word lo supporta). L'evidenziatore resta limitato alla tavolozza di Word.
- Lato server `TabDocument::hexColor()` converte anche `rgb(...)`: si recuperano i colori dei brani salvati prima della correzione.
- Voce: nuovo caso `Voice = 'voice'` dell'Enum, etichetta "Voce". La validazione legge i valori ammessi dall'Enum (`Rule::in(...)`), quindi l'Enum è l'unico punto da cambiare per aggiungere uno strumento. La colonna resta `string(10)`: nessuna modifica al database, nessun `migrate:fresh`.

**File coinvolti**
| File | Modifica |
|---|---|
| `app/Http/Controllers/SongController.php` | Ricerca anche sul nome dello strumento |
| `resources/views/songs/index.blade.php` | Testo della ricerca "Cerca artista, titolo, gruppo o strumento" |
| `resources/views/songs/_delete.blade.php` | "Artista - Titolo verrà eliminato definitivamente." |
| `resources/views/songs/_form.blade.php` | Attributo `data-home` sul foglio |
| `resources/views/songs/show.blade.php` | Attributo `data-escape-to` (Esc → elenco) |
| `resources/js/app.js` | Tasto Esc nelle pagine con `data-escape-to` |
| `resources/js/tab-editor.js` | Esc; chiusura delle tavolozze con clic esterno; evidenziatore riconosciuto dagli sfondi; pulizia di colori, evidenziatori, font e dimensioni incollati |
| `app/Services/TabDocument.php` | Nuovo `hexColor()`; qualsiasi colore del testo `#RRGGBB` |
| `app/Models/Song.php` | Commento di `TEXT_COLORS` (è la tavolozza della barra) |
| `app/Enums/Instrument.php` | Nuovo caso `Voice` ("Voce") |
| `app/Http/Requests/SongRequest.php` | `instrument` validato con i valori dell'Enum |
| `database/migrations/2026_11_24_182440_create_songs_table.php` | Solo il commento della colonna `instrument` |

**Verifiche fatte in fase di sviluppo**
- [x] Editor (jsdom): incolla dal foglio (colore `rgb` → `#C00000`, evidenziatore giallo), da Word (`color:#C00000`, `background:yellow`, `mso-highlight`, `color:black` → automatico, font senza virgolette), da una pagina scura (testo bianco e sfondo scuro ignorati, sfondo quasi giallo → giallo)
- [x] Editor (jsdom): aprire una tavolozza chiude l'altra; clic fuori chiude; Esc chiude la tavolozza aperta
- [x] PHP: `hexColor()` con `#RGB`, `rgb()`, trasparente e valori non validi; anteprima con colore qualsiasi ed evidenziatore non ammesso ignorato

**Avanzamento**
- [x] Codice scritto
- [ ] Verifica nel browser (ricaricare la pagina con `npm run dev` attivo)

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
trasposizione di semitoni, export PDF/.txt, import MusicXML/Guitar Pro, editor battuta per battuta.

---

## Regole di lavoro

- Una tappa alla volta, con spiegazione di ogni file e comando.
- Niente librerie o funzioni non previste senza chiedere.
- Ogni decisione che cambia le specifiche va riportata in `SPECIFICHE-TABULATURE.md`, correggendo direttamente il punto interessato: le specifiche descrivono l'app com'è; la storia delle modifiche resta qui, nella tabella di marcia.
- Commit e push li gestisce l'utente: la fine di ogni tappa è un buon momento per farli.
