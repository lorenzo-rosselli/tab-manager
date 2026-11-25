<#
|--------------------------------------------------------------------------
| Tab Manager – installazione e aggiornamento (lo avvia "Installa Tab Manager.cmd")
|--------------------------------------------------------------------------
| Prepara tutto ciò che serve per usare l'app su questo computer:
|  1. PHP e Composer di php.new (come nella documentazione di Laravel), se mancano. Tab Manager usa
|     sempre questo PHP, anche se sul computer ce n'è già un altro (per esempio quello di XAMPP):
|     php.new lo installa nella sua cartella (%USERPROFILE%\.config\herd-lite\bin) senza toccare
|     l'altro, e per i comandi di questa installazione la sua cartella viene messa per prima nel PATH.
|     Così le due versioni non vanno in conflitto;
|  2. Node.js, se manca (con winget, già presente in Windows 11);
|  3. le librerie PHP (composer install) e JavaScript (npm install);
|  4. l'interfaccia compilata (npm run build);
|  5. la configurazione (.env, solo la prima volta): il database dei brani sta in
|     %LOCALAPPDATA%\TabManager\database.sqlite, fuori dalla cartella dell'app, così
|     aggiornando con un nuovo ZIP i brani restano;
|  6. il database (creato con i brani di prova solo la prima volta, poi solo aggiornato);
|  7. il collegamento "Tab Manager" sul desktop e nella cartella dell'app, e avvia l'app.
|
| Si può rilanciare quando si vuole: è anche il modo per aggiornare l'app.
| Se la cartella ha già un .env (per esempio sul computer di sviluppo), la configurazione
| e il database esistenti non vengono toccati.
#>

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot   # cartella dell'app
$dataDir = Join-Path $env:LOCALAPPDATA 'TabManager'
$herdBin = Join-Path $env:USERPROFILE '.config\herd-lite\bin'
Set-Location $root

function Write-Step([string] $text) {
    Write-Host ''
    Write-Host "==> $text" -ForegroundColor Cyan
}

# Rilegge il PATH di sistema e dell'utente (i programmi appena installati lo modificano) e mette
# per prima la cartella del PHP di php.new: "php" e "composer" sono sempre quelli, anche se nel
# PATH c'è un altro PHP. Vale solo per questa installazione, non cambia le impostazioni del PC.
function Update-Path {
    $paths = ([Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' +
        [Environment]::GetEnvironmentVariable('Path', 'User')) -split ';' |
        Where-Object { $_ -and ($_.TrimEnd('\') -ne $herdBin) }
    $env:Path = (@($herdBin) + $paths) -join ';'
}

function Test-Command([string] $name) {
    return [bool](Get-Command $name -ErrorAction SilentlyContinue)
}

# Esegue un programma e si ferma se non riesce.
function Invoke-Checked([string] $file, [string[]] $arguments) {
    & $file @arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Il comando '$file $($arguments -join ' ')' non è riuscito (codice $LASTEXITCODE)."
    }
}

# Valore di una variabile nel file .env (null se manca o è commentata).
function Get-EnvValue([string] $name) {
    $line = Get-Content '.env' | Where-Object { $_ -match "^\s*$name\s*=" } | Select-Object -First 1
    if (-not $line) {
        return $null
    }
    return ($line -replace "^\s*$name\s*=\s*", '').Trim().Trim('"', "'")
}

try {
    Write-Host 'Installazione di Tab Manager' -ForegroundColor White
    Write-Host "Cartella dell'app: $root"

    # File scaricati da Internet (ZIP non sbloccato): si toglie il segno, così gli script
    # e il collegamento partono senza blocchi. Su file già sbloccati non fa niente.
    Get-ChildItem -Path $root -Recurse -File -ErrorAction SilentlyContinue |
        Where-Object { $_.FullName -notmatch '\\(node_modules|vendor)\\' } |
        Unblock-File -ErrorAction SilentlyContinue

    Update-Path

    # 1. PHP e Composer di php.new (anche se sul computer c'è già un altro PHP)
    $herdPhp = Join-Path $herdBin 'php.exe'
    if (-not (Test-Path $herdPhp) -or -not (Test-Command 'composer')) {
        Write-Step 'Installo PHP e Composer (php.new), accanto a eventuali altre versioni di PHP...'
        # Comando ufficiale di php.new, in un PowerShell separato.
        $phpNew = "Set-ExecutionPolicy Bypass -Scope Process -Force; " +
            "[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; " +
            "iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))"
        & powershell.exe -NoProfile -ExecutionPolicy Bypass -Command $phpNew
        Update-Path
        if (-not (Test-Path $herdPhp) -or -not (Test-Command 'composer')) {
            throw "Non sono riuscito a installare PHP e Composer.`nInstallali da https://php.new e rilancia l'installazione."
        }
    }
    # Le librerie del progetto chiedono PHP 8.4 o più recente.
    & $herdPhp -r "exit(version_compare(PHP_VERSION, '8.4.0', '<') ? 1 : 0);"
    if ($LASTEXITCODE -ne 0) {
        throw "Serve PHP 8.4 o più recente (trovato: $(& $herdPhp -r 'echo PHP_VERSION;')).`nAggiornalo rilanciando il comando di https://php.new e poi rilancia l'installazione."
    }
    Write-Host "PHP $(& $herdPhp -r 'echo PHP_VERSION;') ($herdPhp)"

    # 2. Node.js (serve solo per compilare l'interfaccia)
    if (-not (Test-Command 'npm.cmd')) {
        Write-Step 'Installo Node.js (winget): Windows potrebbe chiedere una conferma...'
        if (-not (Test-Command 'winget')) {
            throw "Node.js non è installato e winget non è disponibile.`nInstalla Node.js (versione LTS) da https://nodejs.org e rilancia l'installazione."
        }
        & winget install --id OpenJS.NodeJS.LTS --exact --silent --accept-source-agreements --accept-package-agreements
        Update-Path
        if (-not (Test-Command 'npm.cmd')) {
            throw "Non sono riuscito a installare Node.js.`nInstallalo (versione LTS) da https://nodejs.org e rilancia l'installazione."
        }
    }
    Write-Host "Node.js $(& node --version)"

    # 3. Configurazione (.env), solo se non c'è ancora
    $newEnv = -not (Test-Path '.env')
    if ($newEnv) {
        Write-Step 'Preparo la configurazione (.env)...'
        New-Item -ItemType Directory -Force -Path $dataDir | Out-Null
        $database = (Join-Path $dataDir 'database.sqlite') -replace '\\', '/'   # PHP accetta le barre "/" anche su Windows
        $lines = Get-Content '.env.example' | Where-Object { $_ -notmatch '^\s*#?\s*DB_DATABASE\s*=' }
        $lines = $lines | ForEach-Object {
            if ($_ -match '^\s*DB_CONNECTION\s*=') { $_; "DB_DATABASE='$database'" } else { $_ }
        }
        [System.IO.File]::WriteAllLines((Join-Path $root '.env'), [string[]] $lines)   # UTF-8 senza BOM
    }

    # 4. Librerie
    Write-Step 'Scarico le librerie PHP (composer install)...'
    Invoke-Checked 'composer' @('install', '--no-interaction', '--no-progress')

    if ($newEnv -or -not (Get-EnvValue 'APP_KEY')) {
        Invoke-Checked 'php' @('artisan', 'key:generate', '--force')
    }

    Write-Step "Scarico le librerie JavaScript e compilo l'interfaccia (npm)..."
    Invoke-Checked 'npm.cmd' @('install', '--no-fund', '--no-audit')
    Invoke-Checked 'npm.cmd' @('run', 'build')

    # 5. Database
    $database = Get-EnvValue 'DB_DATABASE'
    if (-not $database) {
        $database = Join-Path $root 'database\database.sqlite'   # predefinito di Laravel
    }
    $newDatabase = -not (Test-Path $database)
    if ($newDatabase) {
        Write-Step "Creo il database dei brani: $database"
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $database) | Out-Null
        New-Item -ItemType File -Path $database | Out-Null
        Invoke-Checked 'php' @('artisan', 'migrate', '--force')
        Invoke-Checked 'php' @('artisan', 'db:seed', '--force')
    } else {
        Write-Step "Aggiorno il database dei brani (i brani restano): $database"
        Invoke-Checked 'php' @('artisan', 'migrate', '--force')
    }

    # 6. Collegamenti e avvio
    Write-Step 'Creo i collegamenti...'
    & (Join-Path $PSScriptRoot 'install-shortcut.ps1')

    Write-Host ''
    Write-Host 'Installazione completata. Apro Tab Manager...' -ForegroundColor Green
    Start-Process -FilePath (Join-Path $root 'Tab Manager.lnk')
    exit 0
} catch {
    Write-Host ''
    Write-Host 'Installazione non riuscita:' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
