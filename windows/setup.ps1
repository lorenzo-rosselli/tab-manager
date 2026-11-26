<#
|--------------------------------------------------------------------------
| Tab Manager – installazione e aggiornamento (lo avvia "Installa Tab Manager.cmd")
|--------------------------------------------------------------------------
| Prepara tutto ciò che serve per usare l'app su questo computer. Per prima cosa svuota
| storage\framework\views (le pagine compilate da Laravel), così dopo un aggiornamento si vedono
| subito quelle nuove. Poi:
|  1. PHP e Composer. Se nel computer c'è WSL (Ubuntu) con PHP 8.4 o più recente e Composer, usa
|     quelli: Smart App Control controlla solo i programmi di Windows, non quelli Linux di WSL, quindi
|     funzionano anche quando Windows blocca il php.exe (l'app poi usa lo stesso PHP, vedi
|     tab-manager.ps1). Altrimenti PHP e Composer di php.new (come nella documentazione di Laravel),
|     installati se mancano. Tab Manager usa sempre questo PHP, anche se sul computer ce n'è già un
|     altro (per esempio quello di XAMPP): php.new lo installa nella sua cartella
|     (%USERPROFILE%\.config\herd-lite\bin) senza toccare l'altro, e per i comandi di questa
|     installazione la sua cartella viene messa per prima nel PATH. Così le due versioni non vanno
|     in conflitto;
|  2. Node.js, se manca (con winget, già presente in Windows 11);
|  3. le librerie PHP (composer install) e JavaScript (npm install);
|  4. l'interfaccia compilata (npm run build);
|  5. la configurazione (.env, solo la prima volta);
|  6. l'archivio dei brani in Documenti\Tab Manager\database.sqlite, fuori dalla cartella dell'app
|     (scritto nel .env come DB_DATABASE): l'app si può cancellare, riscaricare e aggiornare senza
|     perdere i brani;
|  7. il database: prima di aggiornarlo ne fa una copia in Documenti\Tab Manager\Backup;
|     la prima volta lo crea con i brani di prova;
|  8. il collegamento "Tab Manager" sul desktop e nella cartella dell'app, e avvia l'app.
|
| Si può rilanciare quando si vuole: è anche il modo per aggiornare l'app a mano. L'aggiornamento
| automatico (update.ps1) lo esegue con -NoLaunch: alla fine non apre l'app, ci pensa lui.
#>

param(
    [switch] $NoLaunch
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

$root = Split-Path -Parent $PSScriptRoot   # cartella dell'app
$dataDir = Join-Path ([Environment]::GetFolderPath('MyDocuments')) 'Tab Manager'   # anche con Documenti su OneDrive
$database = Join-Path $dataDir 'database.sqlite'
$backupDir = Join-Path $dataDir 'Backup'
$herdBin = Join-Path $env:USERPROFILE '.config\herd-lite\bin'
$wsl = Join-Path $env:WINDIR 'System32\wsl.exe'
$useWsl = $false
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
    $null | & $file @arguments   # niente input dalla tastiera: il programma non resta mai in attesa
    if ($LASTEXITCODE -ne 0) {
        throw "Il comando '$file $($arguments -join ' ')' non è riuscito (codice $LASTEXITCODE)."
    }
}

# Vero se WSL c'è e il comando dà codice 0 (per esempio: php con la versione giusta, composer).
function Test-Wsl([string[]] $command) {
    if (-not (Test-Path $wsl)) {
        return $false
    }
    $ErrorActionPreference = 'Continue'   # un messaggio di errore di wsl.exe non deve fermare lo script
    try {
        $null | & $wsl --exec @command 2>$null | Out-Null
        return $LASTEXITCODE -eq 0
    } catch {
        return $false
    }
}

# Un percorso di Windows visto da WSL: C:\Users\... → /mnt/c/Users/...
function ConvertTo-WslPath([string] $path) {
    $full = [System.IO.Path]::GetFullPath($path)
    if ($full -notmatch '^([A-Za-z]):\\(.*)$') {
        throw "WSL non può raggiungere questo percorso: $path"
    }
    return '/mnt/' + $Matches[1].ToLower() + '/' + ($Matches[2] -replace '\\', '/')
}

# php e composer: in WSL (nella cartella dell'app, con il percorso dell'archivio tradotto per
# Linux: una variabile d'ambiente vince sul .env) oppure quelli di php.new.
function Invoke-Php([string[]] $arguments) {
    if ($useWsl) {
        Invoke-Checked $wsl (@('--cd', $root, '--exec', 'env', "DB_DATABASE=$(ConvertTo-WslPath $database)", 'php') + $arguments)
    } else {
        Invoke-Checked 'php' $arguments
    }
}

function Invoke-Composer([string[]] $arguments) {
    if ($useWsl) {
        # Il PHP di WSL di solito non ha l'estensione gd, che PHPWord chiede solo per le immagini.
        Invoke-Checked $wsl (@('--cd', $root, '--exec', 'composer') + $arguments + @('--ignore-platform-req=ext-gd'))
    } else {
        Invoke-Checked 'composer' $arguments
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

# Scrive DB_DATABASE nel .env, subito dopo DB_CONNECTION (toglie le righe DB_DATABASE vecchie,
# anche commentate). Il percorso va tra apici (contiene spazi) e con le barre "/", che PHP accetta.
function Set-DatabasePath([string] $path) {
    $value = $path -replace '\\', '/'
    $lines = Get-Content '.env' | Where-Object { $_ -notmatch '^\s*#?\s*DB_DATABASE\s*=' }
    $lines = $lines | ForEach-Object {
        if ($_ -match '^\s*DB_CONNECTION\s*=') { $_; "DB_DATABASE='$value'" } else { $_ }
    }
    [System.IO.File]::WriteAllLines((Join-Path $root '.env'), [string[]] $lines)   # UTF-8 senza BOM
}

# Stesso percorso? (maiuscole, barre e percorsi relativi non contano)
function Test-SamePath([string] $a, [string] $b) {
    return [System.IO.Path]::GetFullPath($a).TrimEnd('\') -ieq [System.IO.Path]::GetFullPath($b).TrimEnd('\')
}

try {
    Write-Host 'Installazione di Tab Manager' -ForegroundColor White
    Write-Host "Cartella dell'app: $root"

    # File scaricati da Internet (ZIP non sbloccato): si toglie il segno, così gli script
    # e il collegamento partono senza blocchi. Su file già sbloccati non fa niente.
    Get-ChildItem -Path $root -Recurse -File -ErrorAction SilentlyContinue |
        Where-Object { $_.FullName -notmatch '\\(node_modules|vendor)\\' } |
        Unblock-File -ErrorAction SilentlyContinue

    # Pagine già compilate da Laravel (storage\framework\views): si cancellano a ogni installazione
    # e aggiornamento, tranne .gitignore. Laravel ricompila una vista Blade solo se il file della vista
    # è più recente di quello compilato, ma i file estratti da uno ZIP di GitHub hanno la data del
    # commit, che può essere più vecchia: senza questa pulizia l'app continuerebbe a mostrare le
    # pagine della versione precedente. Alla prima apertura Laravel le ricompila da sole.
    $compiledViews = Join-Path $root 'storage\framework\views'
    if (Test-Path $compiledViews) {
        Get-ChildItem -LiteralPath $compiledViews -File -Force |
            Where-Object { $_.Name -ne '.gitignore' } |
            Remove-Item -Force -ErrorAction SilentlyContinue
    }

    Update-Path

    # 1. PHP e Composer: quelli di WSL, se ci sono; altrimenti quelli di php.new
    #    (anche se sul computer c'è già un altro PHP)
    $herdPhp = Join-Path $herdBin 'php.exe'
    if (Test-Wsl @('php', '-r', 'exit(PHP_VERSION_ID >= 80401 ? 0 : 1);')) {
        if (-not (Test-Wsl @('composer', '--version'))) {
            throw "In WSL c'è PHP ma non Composer.`nInstallalo in Ubuntu (https://getcomposer.org/download) e rilancia l'installazione."
        }
        $useWsl = $true
        Write-Host "PHP $($null | & $wsl --exec php -r 'echo PHP_VERSION;') (WSL)"
    } elseif (-not (Test-Path $herdPhp) -or -not (Test-Command 'composer')) {
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
    if (-not $useWsl) {
        # Le librerie del progetto chiedono PHP 8.4 o più recente.
        & $herdPhp -r "exit(version_compare(PHP_VERSION, '8.4.0', '<') ? 1 : 0);"
        if ($LASTEXITCODE -ne 0) {
            throw "Serve PHP 8.4 o più recente (trovato: $(& $herdPhp -r 'echo PHP_VERSION;')).`nAggiornalo rilanciando il comando di https://php.new e poi rilancia l'installazione."
        }
        Write-Host "PHP $(& $herdPhp -r 'echo PHP_VERSION;') ($herdPhp)"
    }

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
        Copy-Item '.env.example' '.env'
    }

    # 4. Archivio dei brani in Documenti\Tab Manager, fuori dalla cartella dell'app.
    New-Item -ItemType Directory -Force -Path $dataDir, $backupDir | Out-Null
    if (-not ((Get-EnvValue 'DB_DATABASE') -and (Test-SamePath (Get-EnvValue 'DB_DATABASE') $database))) {
        Set-DatabasePath $database
    }
    Write-Host "Archivio dei brani: $database"

    # 5. Librerie
    Write-Step 'Scarico le librerie PHP (composer install)...'
    Invoke-Composer @('install', '--no-interaction', '--no-progress')

    if ($newEnv -or -not (Get-EnvValue 'APP_KEY')) {
        Invoke-Php @('artisan', 'key:generate', '--force')
    }

    Write-Step "Scarico le librerie JavaScript e compilo l'interfaccia (npm)..."
    Invoke-Checked 'npm.cmd' @('install', '--no-fund', '--no-audit')
    Invoke-Checked 'npm.cmd' @('run', 'build')

    # 6. Database (prima di aggiornarlo, una copia nella cartella Backup)
    $newDatabase = -not (Test-Path $database)
    if ($newDatabase) {
        Write-Step "Creo il database dei brani: $database"
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $database) | Out-Null
        New-Item -ItemType File -Path $database | Out-Null
        Invoke-Php @('artisan', 'migrate', '--force')
        Invoke-Php @('artisan', 'db:seed', '--force')
    } else {
        Write-Step "Copia di sicurezza dell'archivio, poi aggiorno il database (i brani restano)..."
        Invoke-Php @('artisan', 'tab:backup', "--label=prima dell'aggiornamento")
        Invoke-Php @('artisan', 'migrate', '--force')
    }

    # 7. Collegamenti e avvio
    Write-Step 'Creo i collegamenti...'
    & (Join-Path $PSScriptRoot 'install-shortcut.ps1')

    Write-Host ''
    if ($NoLaunch) {
        Write-Host 'Installazione completata.' -ForegroundColor Green
        exit 0
    }
    Write-Host 'Installazione completata. Apro Tab Manager...' -ForegroundColor Green
    Start-Process -FilePath (Join-Path $root 'Tab Manager.lnk')
    exit 0
} catch {
    Write-Host ''
    Write-Host 'Installazione non riuscita:' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
