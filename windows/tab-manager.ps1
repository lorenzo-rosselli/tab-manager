<#
|--------------------------------------------------------------------------
| Tab Manager – avvio come finestra dedicata (lo lancia il collegamento sul desktop)
|--------------------------------------------------------------------------
| 1. Avvia di nascosto il server web integrato di PHP sulla porta 8765, se non è già acceso
|    (la porta è diversa da quella di "php artisan serve", così sviluppo e app non si scontrano).
| 2. Apre l'app in una finestra di Microsoft Edge in "modalità app": niente barra degli indirizzi,
|    schede o menu; titolo e icona sono quelli di Tab Manager. Edge usa un profilo tutto suo
|    (cartella %LOCALAPPDATA%\TabManager\Edge), separato dal browser di tutti i giorni.
| 3. Quando la finestra viene chiusa, spegne il server.
| Prima di tutto, una volta al giorno, fa una copia di sicurezza dell'archivio dei brani
| (php artisan tab:backup --daily → Documenti\Tab Manager\Backup).
|
| Quale PHP: se nel computer c'è WSL (Ubuntu) con PHP 8.4 o più recente, usa sempre quello.
| Smart App Control controlla solo i programmi di Windows, non quelli Linux di WSL, quindi così
| l'app si apre anche se Windows blocca il php.exe. Senza WSL usa il PHP di php.new.
|
| Le pagine usano i file compilati con "npm run build" (cartella public\build): dopo ogni modifica
| al codice va rifatto "npm run build". Se invece "npm run dev" è acceso, usa quello.
| Gli errori compaiono in una finestra di messaggio (lo script gira senza console).
#>

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot   # cartella del progetto (questo file è in "windows")
$port = 8765
$url = "http://127.0.0.1:$port"
$edgeProfile = Join-Path $env:LOCALAPPDATA 'TabManager\Edge'
$wsl = Join-Path $env:WINDIR 'System32\wsl.exe'
$server = $null
$useWsl = $false
$wslDatabase = $null

Add-Type -AssemblyName PresentationFramework

function Show-Error([string] $message) {
    [System.Windows.MessageBox]::Show($message, 'Tab Manager', 'OK', 'Error') | Out-Null
}

# Vero se qualcosa risponde su host:porta.
function Test-Port([string] $hostName, [int] $number) {
    try {
        $client = New-Object System.Net.Sockets.TcpClient($hostName, $number)
        $client.Dispose()
        return $true
    } catch {
        return $false
    }
}

# Vero se è ancora aperta almeno una finestra/processo di Edge con il profilo di Tab Manager.
function Test-Window {
    $processes = Get-CimInstance Win32_Process -Filter "Name = 'msedge.exe'" -ErrorAction SilentlyContinue
    return [bool]($processes | Where-Object { $_.CommandLine -like "*$edgeProfile*" })
}

# Vero se WSL c'è e il suo PHP è almeno 8.4.1 (la versione minima delle librerie in composer.lock).
function Test-WslPhp {
    if (-not (Test-Path $wsl)) {
        return $false
    }
    $ErrorActionPreference = 'Continue'   # un messaggio di errore di wsl.exe non deve fermare lo script
    try {
        & $wsl --exec php -r 'exit(PHP_VERSION_ID >= 80401 ? 0 : 1);' 2>$null | Out-Null
        return $LASTEXITCODE -eq 0
    } catch {
        return $false
    }
}

# Un percorso di Windows visto da WSL: C:\Users\... → /mnt/c/Users/...
function ConvertTo-WslPath([string] $path) {
    $full = [System.IO.Path]::GetFullPath($path)
    if ($full -notmatch '^([A-Za-z]):\\(.*)$') {
        throw "WSL non può raggiungere questo percorso:`n$path"
    }
    return '/mnt/' + $Matches[1].ToLower() + '/' + ($Matches[2] -replace '\\', '/')
}

# Argomenti di wsl.exe per eseguire "php ..." nella cartella $dir. L'archivio dei brani nel .env
# ha un percorso di Windows: lo si passa a PHP già tradotto (una variabile d'ambiente vince sul .env).
function Get-WslArgs([string] $dir, [string[]] $phpArgs) {
    $list = @('--cd', $dir, '--exec')
    if ($wslDatabase) {
        $list += @('env', "DB_DATABASE=$wslDatabase")
    }
    return $list + @('php') + $phpArgs
}

# Gli argomenti in una sola riga di comando, tra virgolette quelli con spazi (per Start-Process).
function Join-Args([string[]] $list) {
    return ($list | ForEach-Object { if ($_ -match '\s') { "`"$_`"" } else { $_ } }) -join ' '
}

function Stop-Server {
    if (-not $server) {
        return
    }
    if ($useWsl) {
        # Chiudere wsl.exe non basta sempre a spegnere il PHP dentro Linux.
        $ErrorActionPreference = 'Continue'
        & $wsl --exec pkill -f "php .*-S 127.0.0.1:$port" 2>$null | Out-Null
    }
    Stop-Process -Id $server.Id -ErrorAction SilentlyContinue
}

try {
    $routerArgs = @('-S', "127.0.0.1:$port", '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')

    if (Test-WslPhp) {
        $useWsl = $true
        $envFile = Join-Path $root '.env'
        if (Test-Path $envFile) {
            $line = Get-Content $envFile -Encoding UTF8 | Where-Object { $_ -match '^\s*DB_DATABASE\s*=' } | Select-Object -First 1
            $database = if ($line) { ($line -replace '^\s*DB_DATABASE\s*=\s*', '').Trim().Trim('"', "'") } else { '' }
            if ($database -match '^[A-Za-z]:') {
                $wslDatabase = ConvertTo-WslPath $database
            }
        }
    } else {
        # PHP di Windows: quello installato da php.new (Herd Lite), anche se sul computer c'è un altro
        # PHP; solo se manca, quello nel PATH. Così altre versioni di PHP non vanno in conflitto.
        $php = Join-Path $env:USERPROFILE '.config\herd-lite\bin\php.exe'
        if (-not (Test-Path $php)) {
            $php = Get-Command php -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty Source
        }
        if (-not $php -or -not (Test-Path $php)) {
            throw "PHP non trovato.`nInstallalo con php.new oppure aggiungilo al PATH."
        }
    }

    $edge = @(
        "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
        "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe"
    ) | Where-Object { Test-Path $_ } | Select-Object -First 1
    if (-not $edge) {
        throw 'Microsoft Edge non trovato.'
    }

    # "public\hot" lo crea "npm run dev" e lo toglie quando si chiude. Se è rimasto da una sessione
    # chiusa male (server di Vite spento), le pagine resterebbero senza stile: lo si toglie.
    $hot = Join-Path $root 'public\hot'
    if (Test-Path $hot) {
        $vite = [Uri]((Get-Content $hot -Raw).Trim())
        if (-not (Test-Port $vite.DnsSafeHost $vite.Port)) {
            Remove-Item $hot
        }
    }
    if (-not (Test-Path $hot) -and -not (Test-Path (Join-Path $root 'public\build\manifest.json'))) {
        throw "Mancano i file dell'interfaccia.`nApri il terminale nella cartella del progetto ed esegui:`nnpm run build"
    }

    # Copia di sicurezza del giorno (solo se oggi non c'è già). Un errore qui non blocca l'app.
    try {
        if ($useWsl) {
            $ErrorActionPreference = 'Continue'
            & $wsl (Get-WslArgs $root @('artisan', 'tab:backup', '--daily')) 2>$null | Out-Null
            $ErrorActionPreference = 'Stop'
        } else {
            & $php (Join-Path $root 'artisan') tab:backup --daily | Out-Null
        }
    } catch {
        $ErrorActionPreference = 'Stop'
    }

    # Server web integrato di PHP, come fa "php artisan serve": cartella public e router di Laravel.
    if (-not (Test-Port '127.0.0.1' $port)) {
        if ($useWsl) {
            # WSL inoltra a Windows le porte aperte su 127.0.0.1. OPcache rende più veloci le pagine.
            $wslArgs = Get-WslArgs (Join-Path $root 'public') (@('-d', 'opcache.enable_cli=1') + $routerArgs)
            $server = Start-Process -FilePath $wsl -WindowStyle Hidden -PassThru -ArgumentList (Join-Args $wslArgs)
        } else {
            $server = Start-Process -FilePath $php -WindowStyle Hidden -PassThru `
                -WorkingDirectory (Join-Path $root 'public') -ArgumentList $routerArgs
        }

        # Fino a 30 secondi: la prima volta WSL impiega qualche secondo ad accendersi.
        for ($i = 0; $i -lt 120 -and -not (Test-Port '127.0.0.1' $port); $i++) {
            Start-Sleep -Milliseconds 250
        }
        if (-not (Test-Port '127.0.0.1' $port)) {
            throw "Il server dell'app non si è avviato (porta $port)."
        }
    }

    Start-Process -FilePath $edge -ArgumentList @(
        "--app=$url",
        "--user-data-dir=`"$edgeProfile`"",
        '--window-size=1280,900',
        '--no-first-run',
        '--no-default-browser-check'
    )

    # Se il server l'ha acceso questo script, lo spegne quando la finestra viene chiusa.
    # (Se era già acceso, c'è già un'altra finestra aperta: ci pensa lo script che l'ha aperta.)
    if ($server) {
        Start-Sleep -Seconds 5
        while (Test-Window) {
            Start-Sleep -Seconds 2
        }
        Stop-Server
    }
} catch {
    Stop-Server
    Show-Error $_.Exception.Message
}
