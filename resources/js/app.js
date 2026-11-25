/*
|--------------------------------------------------------------------------
| JavaScript comune a tutte le pagine: interruttore del tema e tasto Esc
|--------------------------------------------------------------------------
|
| Interruttore del tema (chiaro / scuro)
|--------------------------------------------------------------------------
| Cambia subito il tema mettendo o togliendo la classe "dark" su <html> (senza ricaricare la pagina,
| così nel form non si perde quello che si sta scrivendo) e salva la scelta nel cookie "theme":
| alla pagina successiva è il server (layouts/app.blade.php) a scrivere già la classe giusta.
|
| Tasto Esc: nelle pagine con un elemento data-escape-to (l'anteprima del brano) porta a quell'indirizzo.
| Se è aperta la finestra di conferma dell'eliminazione, Esc chiude solo quella.
| Nel form Esc non fa uscire (si perderebbero le modifiche): chiude solo le tavolozze (resources/js/tab-editor.js).
*/

const toggle = document.getElementById('theme-toggle');

if (toggle) {
    toggle.addEventListener('click', () => {
        const root = document.documentElement;
        const dark = !root.classList.contains('dark');

        root.classList.toggle('dark', dark);
        root.classList.toggle('scheme-dark', dark);   // barre di scorrimento e controlli del browser
        root.classList.toggle('scheme-light', !dark);
        toggle.setAttribute('aria-checked', dark ? 'true' : 'false');

        document.cookie = `theme=${dark ? 'dark' : 'light'}; path=/; max-age=31536000; SameSite=Lax`; // 1 anno
    });
}

const escapeTarget = document.querySelector('[data-escape-to]');

if (escapeTarget) {
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || event.defaultPrevented || event.isComposing) {
            return;
        }
        if (document.querySelector('[popover]:popover-open')) {
            return; // la modale aperta la chiude il browser
        }

        window.location.href = escapeTarget.dataset.escapeTo;
    });
}
