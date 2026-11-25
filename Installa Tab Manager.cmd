@echo off
rem ----------------------------------------------------------------------------
rem  Tab Manager - installazione e aggiornamento (doppio clic su questo file)
rem  Avvia windows\setup.ps1, che installa cio' che manca, prepara l'app,
rem  crea il collegamento "Tab Manager" sul desktop e apre l'app.
rem  -ExecutionPolicy Bypass vale solo per questo avvio: non cambia le
rem  impostazioni di PowerShell del computer.
rem ----------------------------------------------------------------------------
title Installazione di Tab Manager
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0windows\setup.ps1"
echo.
pause
