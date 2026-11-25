@echo off
rem ----------------------------------------------------------------------------
rem  Tab Manager - disinstallazione (doppio clic su questo file)
rem  Avvia windows\uninstall.ps1: chiude l'app, cancella i collegamenti e il
rem  profilo di Edge e, solo se confermi, l'archivio delle tabulature.
rem  La cartella dell'app va cancellata a mano alla fine.
rem ----------------------------------------------------------------------------
title Disinstallazione di Tab Manager
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0windows\uninstall.ps1"
echo.
pause
