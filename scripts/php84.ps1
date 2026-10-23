param(
    [ValidateSet('composer', 'artisan', 'test', 'php')]
    [string] $Command = 'artisan',

    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $Arguments
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$php = 'C:\Users\LENOVO\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe'
$phpDirectory = Split-Path -Parent $php
$composerCommand = Get-Command composer.bat -CommandType Application | Select-Object -First 1
$composer = Join-Path (Split-Path -Parent $composerCommand.Source) 'composer.phar'

if (-not (Test-Path -LiteralPath $php)) {
    throw "PHP 8.4 tidak ditemukan: $php"
}

if (-not (Test-Path -LiteralPath $composer)) {
    throw "composer.phar tidak ditemukan: $composer"
}

# Konfigurasi ini hanya berlaku untuk proses dan child-process-nya.
# php.ini global serta PATH Windows tidak diubah.
$temporaryIni = Join-Path $env:TEMP "perumahan-backend-php84-$PID.ini"
$extensions = @(
    'curl', 'fileinfo', 'gd', 'intl', 'mbstring', 'openssl',
    'pdo_mysql', 'pdo_sqlite', 'sqlite3', 'zip'
)
Set-Content -LiteralPath $temporaryIni -Encoding ASCII -Value @(
    "extension_dir=$phpDirectory\ext"
    $extensions | ForEach-Object { "extension=$_" }
)

$oldPath = $env:Path
$oldPhprc = $env:PHPRC
try {
    $env:Path = "$phpDirectory;$oldPath"
    $env:PHPRC = $temporaryIni

    Push-Location $projectRoot
    try {
        switch ($Command) {
            'composer' { & $php $composer @Arguments }
            'artisan' { & $php 'artisan' @Arguments }
            'test' { & $php 'artisan' 'test' @Arguments }
            'php' { & $php @Arguments }
        }
        exit $LASTEXITCODE
    }
    finally {
        Pop-Location
    }
}
finally {
    $env:Path = $oldPath
    if ($null -eq $oldPhprc) { Remove-Item Env:PHPRC -ErrorAction SilentlyContinue } else { $env:PHPRC = $oldPhprc }
    Remove-Item -LiteralPath $temporaryIni -Force -ErrorAction SilentlyContinue
}
