<#
|--------------------------------------------------------------------------
| Tab Manager – aggiornamento automatico (lo avvia tab-manager.ps1)
|--------------------------------------------------------------------------
| All'avvio, tab-manager.ps1 chiede a GitHub qual è l'ultima Release pubblicata. Se è diversa da
| quella installata (scritta in windows\version.txt), apre questa finestra, che:
|  1. scarica lo ZIP della Release da GitHub e lo estrae in una cartella temporanea;
|  2. copia la versione attuale dell'app in %LOCALAPPDATA%\TabManager\versione-precedente
|     (senza node_modules), per rimetterla a posto se l'aggiornamento non riesce;
|  3. mette i file nuovi al posto di quelli vecchi (robocopy /MIR: toglie anche i file che nella
|     nuova versione non ci sono più). Non tocca ciò che è solo di questo computer: .env, vendor,
|     node_modules, public\build, storage, bootstrap\cache, il collegamento e version.txt.
|     L'archivio dei brani è in Documenti, fuori dalla cartella dell'app: non viene mai toccato;
|  4. esegue setup.ps1 della nuova versione (librerie, interfaccia, copia di sicurezza del
|     database e migrate), scrive la nuova versione in version.txt e apre l'app.
| Se qualcosa va storto rimette la versione precedente e apre quella: al prossimo avvio riprova.
| Sul computer di sviluppo (cartella con .git) l'aggiornamento non parte mai: lì si usa git.
#>

param(
    [Parameter(Mandatory = $true)] [string] $Tag
)

$ErrorActionPreference = 'Stop'

# Disattiva la "Modifica rapida" di questa finestra del terminale. Con la modifica rapida accesa, un
# clic nella finestra inizia una selezione di testo e Windows mette in pausa lo script finché non si
# preme un tasto: sembra bloccato. Vale solo per questa finestra; se non riesce, si va avanti lo stesso.
try {
    Add-Type -Namespace TabManager -Name ConsoleMode -MemberDefinition @'
[DllImport("kernel32.dll")] public static extern IntPtr GetStdHandle(int nStdHandle);
[DllImport("kernel32.dll")] public static extern bool GetConsoleMode(IntPtr hConsole, out uint mode);
[DllImport("kernel32.dll")] public static extern bool SetConsoleMode(IntPtr hConsole, uint mode);
'@
    $consoleInput = [TabManager.ConsoleMode]::GetStdHandle(-10)   # STD_INPUT_HANDLE
    $consoleMode = [uint32] 0
    if ([TabManager.ConsoleMode]::GetConsoleMode($consoleInput, [ref] $consoleMode)) {
        # toglie ENABLE_QUICK_EDIT_MODE (0x40); ENABLE_EXTENDED_FLAGS (0x80) serve perché la modifica valga
        [void] [TabManager.ConsoleMode]::SetConsoleMode($consoleInput, ($consoleMode -band -bnot 0x40) -bor 0x80)
    }
} catch {
}

$ProgressPreference = 'SilentlyContinue'   # la barra di Invoke-WebRequest rallenterebbe molto il download

$repo = 'lorenzo-rosselli/tab-manager'
$root = Split-Path -Parent $PSScriptRoot   # cartella dell'app
$versionFile = Join-Path $PSScriptRoot 'version.txt'
$work = Join-Path $env:TEMP 'TabManager-update'
$previous = Join-Path $env:LOCALAPPDATA 'TabManager\versione-precedente'
$Host.UI.RawUI.WindowTitle = 'Aggiornamento di Tab Manager'

function Write-Step([string] $text) {
    Write-Host ''
    Write-Host "==> $text" -ForegroundColor Cyan
}

# Copia "a specchio" da $from a $to (i file che in $from non ci sono vengono tolti da $to).
# Le cartelle da escludere si passano sia con il percorso di origine sia con quello di arrivo,
# così robocopy non le copia e non le cancella.
function Invoke-Mirror([string] $from, [string] $to, [string[]] $skipDirs, [string[]] $skipFiles) {
    $options = @('/MIR', '/R:2', '/W:1', '/NFL', '/NDL', '/NJH', '/NJS', '/NP')
    if ($skipDirs) {
        $options += '/XD'
        foreach ($dir in $skipDirs) {
            $options += @("$from\$dir", "$to\$dir")
        }
    }
    if ($skipFiles) {
        $options += @('/XF') + $skipFiles
    }
    & robocopy.exe $from $to @options | Out-Null
    if ($LASTEXITCODE -ge 8) {   # da 0 a 7 vuol dire riuscito
        throw "Copia dei file non riuscita (robocopy, codice $LASTEXITCODE)."
    }
}

# Apre l'app come il collegamento, senza controllare di nuovo gli aggiornamenti.
function Start-App {
    $script = Join-Path $root 'windows\tab-manager.ps1'
    Start-Process -FilePath (Join-Path $env:SystemRoot 'System32\conhost.exe') `
        -ArgumentList "--headless powershell.exe -NoProfile -ExecutionPolicy Bypass -File `"$script`" -SkipUpdate"
}

$localDirs = @('.git', 'vendor', 'node_modules', 'storage', 'public\build', 'bootstrap\cache')
$localFiles = @('.env', 'Tab Manager.lnk', 'hot', 'version.txt', 'database.sqlite*')
$saved = $false

try {
    Write-Host "Aggiornamento di Tab Manager alla versione $Tag" -ForegroundColor White
    Write-Host 'Ci vuole qualche minuto. Non chiudere questa finestra: alla fine Tab Manager si apre da solo.'
    Write-Host 'I brani non vengono toccati.'

    # 1. Download ed estrazione
    Write-Step 'Scarico la nuova versione da GitHub...'
    if (Test-Path $work) {
        Remove-Item $work -Recurse -Force
    }
    New-Item -ItemType Directory -Path $work | Out-Null
    $zip = Join-Path $work 'tab-manager.zip'
    [Net.ServicePointManager]::SecurityProtocol = [Net.ServicePointManager]::SecurityProtocol -bor [Net.SecurityProtocolType]::Tls12
    Invoke-WebRequest -Uri "https://github.com/$repo/archive/refs/tags/$Tag.zip" -OutFile $zip `
        -UseBasicParsing -Headers @{ 'User-Agent' = 'TabManager' }
    Expand-Archive -Path $zip -DestinationPath (Join-Path $work 'files')
    # Lo ZIP di GitHub contiene una sola cartella, per esempio "tab-manager-1.1".
    $source = Get-ChildItem (Join-Path $work 'files') -Directory | Select-Object -First 1 -ExpandProperty FullName
    if (-not $source -or -not (Test-Path (Join-Path $source 'artisan'))) {
        throw 'Lo ZIP scaricato non contiene Tab Manager.'
    }

    # 2. Copia della versione attuale, per rimetterla a posto se qualcosa va storto
    Write-Step 'Metto da parte la versione attuale...'
    Invoke-Mirror $root $previous @('node_modules')
    $saved = $true

    # 3. File nuovi al posto di quelli vecchi
    Write-Step 'Sostituisco i file...'
    Invoke-Mirror $source $root $localDirs $localFiles

    # 4. Librerie, interfaccia e database, con lo setup.ps1 della nuova versione
    & (Join-Path $PSHOME 'powershell.exe') -NoProfile -ExecutionPolicy Bypass -File (Join-Path $root 'windows\setup.ps1') -NoLaunch
    if ($LASTEXITCODE -ne 0) {
        throw "La preparazione della nuova versione non è riuscita (vedi i messaggi sopra)."
    }

    [System.IO.File]::WriteAllText($versionFile, $Tag)
    Remove-Item $work -Recurse -Force -ErrorAction SilentlyContinue

    Write-Host ''
    Write-Host "Tab Manager è aggiornato alla versione $Tag. Lo apro..." -ForegroundColor Green
    Start-Sleep -Seconds 2
    Start-App
    exit 0
} catch {
    Write-Host ''
    Write-Host 'Aggiornamento non riuscito:' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    if ($saved) {
        try {
            Invoke-Mirror $previous $root @('node_modules')
            Write-Host 'Ho rimesso la versione precedente: Tab Manager funziona come prima.' -ForegroundColor Yellow
        } catch {
            Write-Host "Non sono riuscito a rimettere la versione precedente: è in $previous" -ForegroundColor Red
            Write-Host 'Rilancia "Installa Tab Manager.cmd" oppure scarica di nuovo l''app da GitHub.' -ForegroundColor Red
        }
    }
    Write-Host 'Al prossimo avvio l''aggiornamento verrà riprovato.'
    Write-Host ''
    Read-Host 'Premi Invio per aprire Tab Manager' | Out-Null
    Start-App
    exit 1
}
