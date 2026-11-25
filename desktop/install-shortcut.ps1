<#
|--------------------------------------------------------------------------
| Crea il collegamento "Tab Manager" sul desktop e nella cartella del progetto
|--------------------------------------------------------------------------
| Da eseguire una volta sola. Dal terminale, nella cartella del progetto:
|     powershell -NoProfile -File desktop\install-shortcut.ps1
| (funziona da PowerShell e dal Prompt dei comandi; nel Prompt dei comandi scrivere solo
| .\desktop\install-shortcut.ps1 aprirebbe il file nel Blocco note invece di eseguirlo)
|
| Il collegamento avvia desktop\tab-manager.ps1 con "conhost --headless", cioè senza nessuna
| finestra del terminale, e usa l'icona desktop\tab-manager.ico.
| Contiene percorsi assoluti: se la cartella del progetto viene spostata, basta eseguire di nuovo
| questo script. Per lo stesso motivo il collegamento nella cartella del progetto non va su git
| (è nel .gitignore).
#>

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$desktop = [Environment]::GetFolderPath('Desktop')   # funziona anche con il desktop su OneDrive
$shell = New-Object -ComObject WScript.Shell

foreach ($folder in @($desktop, $root)) {
    $path = Join-Path $folder 'Tab Manager.lnk'

    $link = $shell.CreateShortcut($path)
    $link.TargetPath = Join-Path $env:SystemRoot 'System32\conhost.exe'
    $link.Arguments = "--headless powershell.exe -NoProfile -File `"$root\desktop\tab-manager.ps1`""
    $link.WorkingDirectory = $root
    $link.IconLocation = "$root\desktop\tab-manager.ico,0"
    $link.Description = 'Apri Tab Manager'
    $link.Save()

    Write-Host "Collegamento creato: $path"
}
