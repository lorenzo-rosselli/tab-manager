<#
|--------------------------------------------------------------------------
| Tab Manager – disinstallazione (lo avvia "Disinstalla Tab Manager.cmd")
|--------------------------------------------------------------------------
| Toglie da questo computer ciò che l'installazione ha creato fuori dalla cartella dell'app:
|  1. chiude l'app, se è aperta (finestra di Edge e server PHP sulla porta 8765, anche in WSL);
|  2. cancella il collegamento "Tab Manager" sul desktop e quello nella cartella dell'app;
|  3. cancella il profilo di Edge della finestra dell'app (%LOCALAPPDATA%\TabManager\Edge) e la copia
|     della versione precedente lasciata dall'aggiornamento automatico (...\versione-precedente);
|
| Non tocca mai:
|  - l'archivio dei brani e le sue copie (Documenti\Tab Manager): reinstallando Tab Manager si
|    ritrova tutto com'era; chi vuole davvero cancellarlo lo fa a mano;
|  - la cartella dell'app: si cancella a mano alla fine (uno script non può cancellare la
|    cartella da cui è in esecuzione). Su un computer di sviluppo è il progetto con git: va tenuta;
|  - PHP, Composer (php.new) e Node.js, che possono servire ad altri programmi.
#>

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot   # cartella dell'app
$appDataDir = Join-Path $env:LOCALAPPDATA 'TabManager'   # solo il profilo di Edge
$edgeProfile = Join-Path $appDataDir 'Edge'
$port = 8765
Set-Location $root

function Write-Step([string] $text) {
    Write-Host ''
    Write-Host "==> $text" -ForegroundColor Cyan
}

# Valore di una variabile nel file .env (null se manca, è commentata o non c'è il .env).
function Get-EnvValue([string] $name) {
    if (-not (Test-Path '.env')) {
        return $null
    }
    $line = Get-Content '.env' | Where-Object { $_ -match "^\s*$name\s*=" } | Select-Object -First 1
    if (-not $line) {
        return $null
    }
    return ($line -replace "^\s*$name\s*=\s*", '').Trim().Trim('"', "'")
}

# Cancella un file o una cartella; riprova per qualche secondo se è ancora in uso
# (Edge appena chiuso può tenere aperti i suoi file per un attimo).
function Remove-Path([string] $path) {
    for ($i = 0; $i -lt 20; $i++) {
        try {
            Remove-Item -LiteralPath $path -Recurse -Force
            return
        } catch {
            Start-Sleep -Milliseconds 500
        }
    }
    throw "Non riesco a cancellare $path (forse è ancora in uso). Chiudi Tab Manager e riprova."
}

try {
    Write-Host 'Disinstallazione di Tab Manager' -ForegroundColor White
    Write-Host "Cartella dell'app: $root"
    Write-Host ''
    $answer = Read-Host 'Vuoi disinstallare Tab Manager da questo computer? (S/N)'
    if ($answer.Trim() -notmatch '^(s|si|sì)$') {
        Write-Host 'Disinstallazione annullata: non è stato toccato niente.'
        exit 0
    }

    # 1. Chiude l'app: le finestre di Edge con il profilo di Tab Manager e il server PHP.
    Write-Step "Chiudo Tab Manager, se è aperto..."
    $processes = Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object {
        ($_.Name -eq 'msedge.exe' -and $_.CommandLine -like "*$edgeProfile*") -or
        ($_.Name -in @('php.exe', 'wsl.exe') -and $_.CommandLine -like "*127.0.0.1:$port*")
    }
    foreach ($process in $processes) {
        Stop-Process -Id $process.ProcessId -Force -ErrorAction SilentlyContinue
    }
    # Se l'app usa il PHP di WSL, il server gira dentro Linux: lo si spegne da lì.
    $wsl = Join-Path $env:WINDIR 'System32\wsl.exe'
    if (Test-Path $wsl) {
        $ErrorActionPreference = 'Continue'
        & $wsl --exec pkill -f "php .*-S 127.0.0.1:$port" 2>$null | Out-Null
        $ErrorActionPreference = 'Stop'
    }

    # 2. Collegamenti: sul desktop solo se è davvero quello di Tab Manager.
    Write-Step 'Cancello i collegamenti...'
    $shell = New-Object -ComObject WScript.Shell
    $desktop = [Environment]::GetFolderPath('Desktop')   # funziona anche con il desktop su OneDrive
    foreach ($folder in @($desktop, $root)) {
        $path = Join-Path $folder 'Tab Manager.lnk'
        if ((Test-Path $path) -and ($shell.CreateShortcut($path).Arguments -like '*tab-manager.ps1*')) {
            Remove-Item -LiteralPath $path -Force
            Write-Host "Collegamento cancellato: $path"
        }
    }

    # 3. Profilo di Edge della finestra dell'app.
    if (Test-Path $edgeProfile) {
        Write-Step "Cancello il profilo di Edge dell'app..."
        Remove-Path $edgeProfile
        Write-Host "Cancellato: $edgeProfile"
    }

    # Copia di riserva lasciata dall'aggiornamento automatico (update.ps1).
    $previous = Join-Path $appDataDir 'versione-precedente'
    if (Test-Path $previous) {
        Write-Step "Cancello la copia della versione precedente dell'app..."
        Remove-Path $previous
        Write-Host "Cancellato: $previous"
    }

    # 4. Archivio dei brani: non si tocca. Si dice solo dov'è (dal .env, come in setup.ps1).
    $database = Get-EnvValue 'DB_DATABASE'
    if (-not $database) {
        $database = Join-Path $root 'database\database.sqlite'   # predefinito di Laravel
    }
    $database = [System.IO.Path]::GetFullPath($database)
    $insideApp = $database.StartsWith($root.TrimEnd('\') + '\', [StringComparison]::OrdinalIgnoreCase)

    # La cartella dell'app in AppData, se è rimasta vuota.
    if ((Test-Path $appDataDir) -and -not (Get-ChildItem -LiteralPath $appDataDir -Force)) {
        Remove-Item -LiteralPath $appDataDir -Force
    }

    Write-Host ''
    Write-Host 'Disinstallazione completata.' -ForegroundColor Green
    if (Test-Path $database) {
        Write-Host "L'archivio dei brani NON è stato toccato: $database"
        Write-Host "Le copie di sicurezza sono nella cartella Backup accanto."
        if ($insideApp) {
            Write-Host 'ATTENZIONE: questo archivio è dentro la cartella dell''app. Prima di cancellarla,' -ForegroundColor Yellow
            Write-Host 'rilancia "Installa Tab Manager.cmd" (lo sposta in Documenti\Tab Manager) oppure copialo altrove.' -ForegroundColor Yellow
        }
    }
    Write-Host ''
    Write-Host "Ultimo passo, a mano: chiudi questa finestra e cancella la cartella dell'app"
    Write-Host "  $root"
    Write-Host "(si apre ora in Esplora file, già selezionata)."
    Write-Host ''
    Write-Host 'PHP, Composer e Node.js restano installati, perché possono servire ad altri programmi.'
    Write-Host 'Se vuoi toglierli, le istruzioni sono nel README, alla voce "Disinstallare".'
    Start-Process -FilePath 'explorer.exe' -ArgumentList "/select,`"$root`""
    exit 0
} catch {
    Write-Host ''
    Write-Host 'Disinstallazione non riuscita:' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
