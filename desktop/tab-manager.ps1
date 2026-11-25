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
|
| Le pagine usano i file compilati con "npm run build" (cartella public\build): dopo ogni modifica
| al codice va rifatto "npm run build". Se invece "npm run dev" è acceso, usa quello.
| Gli errori compaiono in una finestra di messaggio (lo script gira senza console).
#>

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot   # cartella del progetto (questo file è in "desktop")
$port = 8765
$url = "http://127.0.0.1:$port"
$edgeProfile = Join-Path $env:LOCALAPPDATA 'TabManager\Edge'
$server = $null

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

try {
    # PHP: quello nel PATH, altrimenti quello installato da php.new (Herd Lite).
    $php = Get-Command php -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty Source
    if (-not $php) {
        $php = Join-Path $env:USERPROFILE '.config\herd-lite\bin\php.exe'
    }
    if (-not (Test-Path $php)) {
        throw "PHP non trovato.`nInstallalo con php.new oppure aggiungilo al PATH."
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

    # Server web integrato di PHP, come fa "php artisan serve": cartella public e router di Laravel.
    if (-not (Test-Port '127.0.0.1' $port)) {
        $server = Start-Process -FilePath $php -WindowStyle Hidden -PassThru `
            -WorkingDirectory (Join-Path $root 'public') `
            -ArgumentList '-S', "127.0.0.1:$port", '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'

        for ($i = 0; $i -lt 60 -and -not (Test-Port '127.0.0.1' $port); $i++) {
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
        Stop-Process -Id $server.Id -ErrorAction SilentlyContinue
    }
} catch {
    if ($server) {
        Stop-Process -Id $server.Id -ErrorAction SilentlyContinue
    }
    Show-Error $_.Exception.Message
}
