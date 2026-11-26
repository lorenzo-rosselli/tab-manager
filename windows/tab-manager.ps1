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
| Prima di tutto controlla se su GitHub c'è una Release nuova: se c'è, apre la finestra di
| aggiornamento (update.ps1), che alla fine riapre l'app. Dopo aver aperto la finestra, una volta al
| giorno, fa in background una copia di sicurezza dell'archivio dei brani
| (php artisan tab:backup --daily → Documenti\Tab Manager\Backup).
|
| Quale PHP: se nel computer c'è WSL (Ubuntu) con PHP 8.4 o più recente, usa sempre quello.
| Smart App Control controlla solo i programmi di Windows, non quelli Linux di WSL, quindi così
| l'app si apre anche se Windows blocca il php.exe. Senza WSL usa il PHP di php.new.
|
| Un solo avvio alla volta: un "lucchetto" di Windows (mutex) fa sì che cliccare più volte il
| collegamento mentre l'app si sta avviando non apra altre finestre. Mentre si avvia compare subito
| una piccola finestra "Avvio di Tab Manager…", che si chiude quando si apre l'app.
|
| Le pagine usano i file compilati con "npm run build" (cartella public\build): dopo ogni modifica
| al codice va rifatto "npm run build". Se invece "npm run dev" è acceso, usa quello.
| Gli errori compaiono in una finestra di messaggio (lo script gira senza console).
#>

param(
    [switch] $SkipUpdate   # lo passa update.ps1 quando riapre l'app: niente nuovo controllo
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot   # cartella del progetto (questo file è in "windows")
$port = 8765
$url = "http://127.0.0.1:$port"
$edgeProfile = Join-Path $env:LOCALAPPDATA 'TabManager\Edge'
$wsl = Join-Path $env:WINDIR 'System32\wsl.exe'
$repo = 'lorenzo-rosselli/tab-manager'
$versionFile = Join-Path $PSScriptRoot 'version.txt'   # la Release installata (la scrive update.ps1)
$server = $null
$useWsl = $false
$wslDatabase = $null
$splash = $null
$locked = $false

# "Lucchetto" con nome: lo tiene chi sta avviando l'app (o aggiornandola, vedi update.ps1). Un secondo
# clic sul collegamento lo trova occupato e non fa niente. Si libera appena la finestra dell'app è
# aperta: da lì un altro clic apre un'altra finestra, come sempre.
$lock = New-Object System.Threading.Mutex($false, 'Local\TabManager-Avvio')

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

# Nome dell'ultima Release pubblicata su GitHub (es. "v1.1"), oppure null se non ce n'è nessuna,
# se manca Internet o se GitHub non risponde entro 5 secondi: in quei casi l'app si apre e basta.
function Get-LatestRelease {
    try {
        [Net.ServicePointManager]::SecurityProtocol = [Net.ServicePointManager]::SecurityProtocol -bor [Net.SecurityProtocolType]::Tls12
        $release = Invoke-RestMethod -Uri "https://api.github.com/repos/$repo/releases/latest" `
            -Headers @{ 'User-Agent' = 'TabManager' } -TimeoutSec 5 -UseBasicParsing
        return [string] $release.tag_name
    } catch {
        return $null
    }
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

# Argomenti di wsl.exe per eseguire "php ..." nella cartella $dir, con le variabili d'ambiente
# dell'app (una variabile d'ambiente vince sul .env): la sessione in un file (vedi sotto) e
# l'archivio dei brani, che nel .env ha un percorso di Windows, già tradotto per Linux.
function Get-WslArgs([string] $dir, [string[]] $phpArgs) {
    $list = @('--cd', $dir, '--exec', 'env', 'SESSION_DRIVER=file')
    if ($wslDatabase) {
        $list += "DB_DATABASE=$wslDatabase"
    }
    return $list + @('php') + $phpArgs
}

# Gli argomenti in una sola riga di comando, tra virgolette quelli con spazi (per Start-Process).
function Join-Args([string[]] $list) {
    return ($list | ForEach-Object { if ($_ -match '\s') { "`"$_`"" } else { $_ } }) -join ' '
}

# Prende il lucchetto senza aspettare: vero se ci si riesce. Se chi lo teneva si è chiuso senza
# liberarlo, Windows lo segnala con un'eccezione ma il lucchetto passa comunque a noi.
function Enter-Lock {
    try {
        $script:locked = $lock.WaitOne(0)
    } catch [System.Threading.AbandonedMutexException] {
        $script:locked = $true
    }

    return $script:locked
}

function Exit-Lock {
    if ($script:locked) {
        try {
            $lock.ReleaseMutex()
        } catch {
        }
        $script:locked = $false
    }
}

# Finestrella "Avvio di Tab Manager…" (WPF, la grafica di Windows), mostrata subito mentre lo script
# controlla gli aggiornamenti e avvia il server, così si vede che l'app sta partendo. Gira in un
# thread a parte (runspace), altrimenti mentre lo script aspetta resterebbe bloccata ("Non risponde").
# Si chiude quando Stop-Splash alza il segnale Close; da sola dopo 2 minuti, per sicurezza.
function Start-Splash {
    try {
        $state = [hashtable]::Synchronized(@{ Close = $false; Icon = Join-Path $PSScriptRoot 'tab-manager.ico' })
        $runspace = [runspacefactory]::CreateRunspace()
        $runspace.ApartmentState = 'STA'   # le finestre WPF lo richiedono
        $runspace.Open()
        $runspace.SessionStateProxy.SetVariable('state', $state)

        $shell = [powershell]::Create()
        $shell.Runspace = $runspace
        [void] $shell.AddScript({
            Add-Type -AssemblyName PresentationFramework
            [xml] $xaml = @'
<Window xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
        Title="Tab Manager" Width="380" Height="130" WindowStyle="None" ResizeMode="NoResize"
        WindowStartupLocation="CenterScreen" Topmost="True" AllowsTransparency="True" Background="Transparent">
    <Border Background="#111827" BorderBrush="#374151" BorderThickness="1" CornerRadius="10" Padding="28,22">
        <StackPanel VerticalAlignment="Center">
            <TextBlock Text="Avvio di Tab Manager…" Foreground="White" FontFamily="Segoe UI" FontSize="18" HorizontalAlignment="Center"/>
            <ProgressBar IsIndeterminate="True" Height="4" Margin="0,18,0,0" Foreground="#F59E0B" Background="#374151" BorderThickness="0"/>
        </StackPanel>
    </Border>
</Window>
'@
            # Tutto ciò che serve al timer sta in $state (variabile condivisa), non in variabili locali.
            $state.Window = [Windows.Markup.XamlReader]::Load((New-Object System.Xml.XmlNodeReader $xaml))
            $state.Started = Get-Date
            try {
                $state.Window.Icon = [System.Windows.Media.Imaging.BitmapFrame]::Create([Uri] $state.Icon)
            } catch {
            }
            $state.Timer = New-Object System.Windows.Threading.DispatcherTimer
            $state.Timer.Interval = [TimeSpan]::FromMilliseconds(200)
            $state.Timer.Add_Tick({
                if ($state.Close -or ((Get-Date) - $state.Started).TotalMinutes -ge 2) {
                    $state.Timer.Stop()
                    $state.Window.Close()
                }
            })
            $state.Timer.Start()
            [void] $state.Window.ShowDialog()
        })
        [void] $shell.BeginInvoke()

        return $state
    } catch {
        return $null   # senza finestrella l'app parte lo stesso
    }
}

function Stop-Splash {
    if ($script:splash) {
        $script:splash.Close = $true
        $script:splash = $null
    }
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

# Sessione di Laravel in un file (storage\framework\sessions) invece che nel database: Laravel
# la salva a ogni pagina, e una scrittura su SQLite (archivio in Documenti) è molto più lenta,
# soprattutto con il PHP di WSL. Vale per l'app aperta dal collegamento, non tocca il .env.
# (Non nel cookie: dopo un errore di validazione la sessione contiene tutto il foglio, troppo
# grande per un cookie.) I programmi avviati da qui ereditano la variabile.
$env:SESSION_DRIVER = 'file'

# Opzioni di PHP per il server dell'app:
# - OPcache tiene in memoria il codice già letto e, con validate_timestamps=0, non ricontrolla più
#   i file sul disco: ogni file si legge una volta sola finché l'app resta aperta (con il PHP di
#   WSL leggere i file di Windows è lento). Dopo una modifica al codice basta riaprire l'app;
# - realpath_cache: PHP ricorda più a lungo dove si trovano i file, con meno controlli sul disco.
$phpOptions = @(
    '-d', 'opcache.enable_cli=1',
    '-d', 'opcache.validate_timestamps=0',
    '-d', 'realpath_cache_size=4096K',
    '-d', 'realpath_cache_ttl=3600'
)

# Un solo avvio alla volta: se il lucchetto è già preso (l'app si sta avviando o aggiornando),
# questo clic non fa niente.
if (-not (Enter-Lock)) {
    exit 0
}

try {
    # Se l'app non è già aperta, la finestrella "Avvio di Tab Manager…" compare subito.
    if (-not (Test-Port '127.0.0.1' $port)) {
        $splash = Start-Splash
    }

    # Aggiornamento: non sul computer di sviluppo (cartella con .git: lì si usa git) e non se l'app
    # è già aperta (server acceso). Se c'è una Release nuova, la finestra di aggiornamento fa il resto.
    if (-not $SkipUpdate -and -not (Test-Path (Join-Path $root '.git')) -and -not (Test-Port '127.0.0.1' $port)) {
        $latest = Get-LatestRelease
        $installed = if (Test-Path $versionFile) { (Get-Content $versionFile -Raw).Trim() } else { '' }
        if ($latest -and $latest -ne $installed) {
            Stop-Splash
            Start-Process -FilePath (Join-Path $PSHOME 'powershell.exe') -ArgumentList `
                "-NoProfile -ExecutionPolicy Bypass -File `"$(Join-Path $PSScriptRoot 'update.ps1')`" -Tag `"$latest`""
            Exit-Lock   # update.ps1 lo prende e lo tiene finché l'aggiornamento non è finito
            exit 0
        }
    }

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

    # Server web integrato di PHP, come fa "php artisan serve": cartella public e router di Laravel.
    if (-not (Test-Port '127.0.0.1' $port)) {
        if ($useWsl) {
            # WSL inoltra a Windows le porte aperte su 127.0.0.1.
            $wslArgs = Get-WslArgs (Join-Path $root 'public') ($phpOptions + $routerArgs)
            $server = Start-Process -FilePath $wsl -WindowStyle Hidden -PassThru -ArgumentList (Join-Args $wslArgs)
        } else {
            $server = Start-Process -FilePath $php -WindowStyle Hidden -PassThru `
                -WorkingDirectory (Join-Path $root 'public') -ArgumentList ($phpOptions + $routerArgs)
        }

        # Fino a 30 secondi: la prima volta WSL impiega qualche secondo ad accendersi.
        for ($i = 0; $i -lt 120 -and -not (Test-Port '127.0.0.1' $port); $i++) {
            Start-Sleep -Milliseconds 250
        }
        if (-not (Test-Port '127.0.0.1' $port)) {
            throw "Il server dell'app non si è avviato (porta $port)."
        }
    }

    # Finestra di 1280 × 900 al centro dello schermo principale. L'area di lavoro (lo schermo senza la
    # barra delle applicazioni) è in unità "logiche", come le misure di Edge, così il centro è giusto
    # anche con lo schermo ingrandito (es. 125%). Se lo schermo è più piccolo, la finestra lo riempie.
    $area = [System.Windows.SystemParameters]::WorkArea
    $width = [int][Math]::Min(1280, $area.Width)
    $height = [int][Math]::Min(900, $area.Height)
    $left = [int]($area.Left + ($area.Width - $width) / 2)
    $top = [int]($area.Top + ($area.Height - $height) / 2)

    Start-Process -FilePath $edge -ArgumentList @(
        "--app=$url",
        "--user-data-dir=`"$edgeProfile`"",
        "--window-size=$width,$height",
        "--window-position=$left,$top",
        '--no-first-run',
        '--no-default-browser-check'
    )

    # Edge impiega un attimo a mostrare la finestra: la finestrella resta ancora un paio di secondi.
    # Poi l'avvio è finito: si libera il lucchetto (un altro clic aprirà un'altra finestra).
    Start-Sleep -Seconds 2
    Stop-Splash
    Exit-Lock

    # Copia di sicurezza del giorno (solo se oggi non c'è già), in background dopo aver aperto la
    # finestra, così l'app compare subito. Un errore qui non blocca l'app.
    try {
        if ($useWsl) {
            Start-Process -FilePath $wsl -WindowStyle Hidden `
                -ArgumentList (Join-Args (Get-WslArgs $root @('artisan', 'tab:backup', '--daily')))
        } else {
            Start-Process -FilePath $php -WindowStyle Hidden -WorkingDirectory $root `
                -ArgumentList @('artisan', 'tab:backup', '--daily')
        }
    } catch {
    }

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
    Stop-Splash
    Stop-Server
    Show-Error $_.Exception.Message
} finally {
    Exit-Lock
}
