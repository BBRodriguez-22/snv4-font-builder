@echo off
title SNV4 Font Builder Web UI
color 0B

cd /d "%~dp0"

if not exist scan mkdir scan
if not exist output mkdir output
if not exist logs mkdir logs
if not exist data\jobs mkdir data\jobs

if not exist php\php.exe (
    echo ERROR: php\php.exe not found.
    echo.
    echo Required PHP package:
    echo   Official PHP 8.5.4 VS17 x64 Non Thread Safe ZIP
    echo.
    echo Extract the FULL PHP ZIP into:
    echo   %~dp0php
    echo.
    pause
    exit /b 1
)

if not exist php\snv4-webui.ini (
    echo ERROR: php\snv4-webui.ini not found.
    pause
    exit /b 1
)

if not exist php\ext\php_zip.dll (
    echo ERROR: php\ext\php_zip.dll not found.
    echo.
    echo The full official PHP ZIP does not appear to be extracted.
    echo Extract the FULL PHP ZIP into the php folder.
    echo.
    pause
    exit /b 1
)

echo Checking ZIP extension...
php\php.exe -c php\snv4-webui.ini -m | findstr /i "zip" >nul
if errorlevel 1 (
    echo.
    echo PHP started but the ZIP extension is not available.
    echo This bundle expects:
    echo   extension_dir = "./ext"
    echo   extension=zip
    echo inside php\snv4-webui.ini
    echo.
    echo Required PHP package:
    echo   Official PHP 8.5.4 VS17 x64 Non Thread Safe ZIP
    echo.
    pause
    exit /b 1
)

echo Starting local web UI...
echo.
echo Browser URL:
echo   http://127.0.0.1:8000
echo.
start "" http://127.0.0.1:8000
php\php.exe -c php\snv4-webui.ini -S 127.0.0.1:8000 -t app\public

pause
