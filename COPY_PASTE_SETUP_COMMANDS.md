# BrightCV Copy-and-Paste Commands

This file is for Windows 11 users running WAMP and PowerShell. The main block
works for both a first installation and an existing checkout. It does not
delete an existing project or database.

## Before copying the command

1. Install Git, WAMP, VS Code, and Node.js 20 or newer.
2. Start WAMP.
3. Wait until the WAMP icon is green.
4. Open **Windows PowerShell**.

## Complete automatic setup or update

Copy this entire block, paste it into PowerShell, and press Enter:

```powershell
$ErrorActionPreference = "Stop"

$RepoUrl = "https://github.com/baahe0044-netizen/NewArchitecture_BrightCv.git"
$Branch = "agent/production-cv-builder"
$InstallRoot = "C:\wamp64\www"
$ProjectName = "NewArchitecture_BrightCv"
$ProjectPath = Join-Path $InstallRoot $ProjectName
$AppUrl = "http://localhost/NewArchitecture_BrightCv/public"

function Assert-CommandSucceeded {
    param(
        [string]$Step,
        [int]$ExitCode
    )

    if ($ExitCode -ne 0) {
        throw "$Step failed with exit code $ExitCode. Read the error shown above."
    }
}

function Get-PhpFolderVersion {
    param($PhpFile)

    $versionText = $PhpFile.Directory.Name -replace '^php', ''
    try {
        return [version]$versionText
    }
    catch {
        return [version]"0.0.0"
    }
}

function Set-EnvValue {
    param(
        [string]$Path,
        [string]$Name,
        [string]$Value
    )

    $lines = @([System.IO.File]::ReadAllLines($Path))
    $pattern = "^$([regex]::Escape($Name))="
    $found = $false

    for ($index = 0; $index -lt $lines.Count; $index++) {
        if ($lines[$index] -match $pattern) {
            $lines[$index] = "$Name=$Value"
            $found = $true
            break
        }
    }

    if (-not $found) {
        $lines += "$Name=$Value"
    }

    $utf8WithoutBom = [System.Text.UTF8Encoding]::new($false)
    [System.IO.File]::WriteAllLines($Path, [string[]]$lines, $utf8WithoutBom)
}

if (-not (Get-Command git.exe -ErrorAction SilentlyContinue)) {
    throw "Git is not installed or is not available in PATH. Install Git, reopen PowerShell, and run this block again."
}

if (-not (Get-Command node.exe -ErrorAction SilentlyContinue)) {
    throw "Node.js 20 or newer is not installed. Install Node.js, reopen PowerShell, and run this block again."
}

if (-not (Get-Command npm.cmd -ErrorAction SilentlyContinue)) {
    throw "npm is not available. Reinstall Node.js, reopen PowerShell, and run this block again."
}

if (-not (Test-Path "C:\wamp64")) {
    throw "WAMP was not found at C:\wamp64. Install WAMP there or change InstallRoot and the PHP path in this block."
}

if (-not (Test-Path $InstallRoot)) {
    New-Item -ItemType Directory -Path $InstallRoot -Force | Out-Null
}

if (-not (Test-Path $ProjectPath)) {
    Write-Host "Cloning BrightCV..." -ForegroundColor Cyan
    Set-Location $InstallRoot
    & git.exe clone $RepoUrl $ProjectName
    Assert-CommandSucceeded "Git clone" $LASTEXITCODE
}
elseif (-not (Test-Path (Join-Path $ProjectPath ".git"))) {
    throw "$ProjectPath already exists but is not a Git repository. Rename that folder and run this block again."
}

Set-Location $ProjectPath

Write-Host "Downloading the latest production branch..." -ForegroundColor Cyan
& git.exe fetch origin
Assert-CommandSucceeded "Git fetch" $LASTEXITCODE

$existingBranch = (& git.exe branch --list $Branch) -join ""
Assert-CommandSucceeded "Checking the local branch" $LASTEXITCODE

if ([string]::IsNullOrWhiteSpace($existingBranch)) {
    & git.exe switch --track "origin/$Branch"
    Assert-CommandSucceeded "Creating the local production branch" $LASTEXITCODE
}
else {
    & git.exe switch $Branch
    Assert-CommandSucceeded "Switching to the production branch" $LASTEXITCODE
}

& git.exe pull --ff-only origin $Branch
Assert-CommandSucceeded "Git pull" $LASTEXITCODE

$phpRoot = "C:\wamp64\bin\php"
$phpFile = Get-ChildItem -Path $phpRoot -Filter "php.exe" -File -Recurse |
    Sort-Object { Get-PhpFolderVersion $_ } -Descending |
    Select-Object -First 1

if ($null -eq $phpFile) {
    throw "No WAMP PHP executable was found under $phpRoot."
}

$php = $phpFile.FullName
$phpVersionText = (& $php -r "echo PHP_VERSION;").Trim()
Assert-CommandSucceeded "Reading the PHP version" $LASTEXITCODE
$phpVersionNumber = [version](($phpVersionText -split '-')[0])

if ($phpVersionNumber -lt [version]"8.1.0") {
    throw "BrightCV requires PHP 8.1 or newer. WAMP PHP $phpVersionText was found."
}

Write-Host "Using PHP $phpVersionText at $php" -ForegroundColor Green

$phpModules = @(& $php -m)
Assert-CommandSucceeded "Reading PHP modules" $LASTEXITCODE

foreach ($requiredModule in @("pdo_mysql", "mbstring", "json")) {
    if ($phpModules -notcontains $requiredModule) {
        throw "The required PHP module '$requiredModule' is disabled in $php. Enable it in WAMP and run this block again."
    }
}

$nodeVersionText = (& node.exe --version).TrimStart('v')
Assert-CommandSucceeded "Reading the Node.js version" $LASTEXITCODE
$nodeVersionNumber = [version](($nodeVersionText -split '-')[0])

if ($nodeVersionNumber.Major -lt 20) {
    throw "BrightCV quality checks require Node.js 20 or newer. Node.js $nodeVersionText was found."
}

$envPath = Join-Path $ProjectPath ".env"
if (-not (Test-Path $envPath)) {
    Copy-Item (Join-Path $ProjectPath ".env.example") $envPath
}

Set-EnvValue $envPath "APP_URL" $AppUrl
Set-EnvValue $envPath "DB_DATABASE" "brightcv_db"

$keyLine = Get-Content $envPath |
    Where-Object { $_ -match '^APP_KEY=' } |
    Select-Object -First 1
$currentKey = ""

if ($null -ne $keyLine) {
    $currentKey = $keyLine.Substring("APP_KEY=".Length).Trim()
}

if ($currentKey.Length -lt 32 -or $currentKey -match 'replace|paste|change') {
    $appKey = (& $php -r "echo bin2hex(random_bytes(32));").Trim()
    Assert-CommandSucceeded "Generating APP_KEY" $LASTEXITCODE
    Set-EnvValue $envPath "APP_KEY" $appKey
    Write-Host "A secure APP_KEY was generated." -ForegroundColor Green
}
else {
    Write-Host "The existing APP_KEY was preserved." -ForegroundColor Green
}

Write-Host "Creating or updating brightcv_db..." -ForegroundColor Cyan
& $php "database\migrate.php"
Assert-CommandSucceeded "Database migration" $LASTEXITCODE

Write-Host "Installing exact test dependencies..." -ForegroundColor Cyan
& npm.cmd ci
Assert-CommandSucceeded "npm ci" $LASTEXITCODE

Write-Host "Running the complete quality suite..." -ForegroundColor Cyan
& npm.cmd run check
Assert-CommandSucceeded "npm run check" $LASTEXITCODE

Write-Host "Running tests with WAMP PHP..." -ForegroundColor Cyan
& $php "tests\run.php"
Assert-CommandSucceeded "Native PHP tests" $LASTEXITCODE

Write-Host "" 
Write-Host "BrightCV setup completed successfully." -ForegroundColor Green
Write-Host "Project: $ProjectPath"
Write-Host "Database: brightcv_db"
Write-Host "URL: $AppUrl"
Write-Host "If WAMP changed configuration, use Restart All Services before testing."

Start-Process $AppUrl
```

When it finishes successfully, the browser opens:

```text
http://localhost/NewArchitecture_BrightCv/public
```

Create an account and test the dashboard and CV builder.

## Short command for later updates

After the first setup, start WAMP, open PowerShell, copy this entire block, and
press Enter:

```powershell
$ErrorActionPreference = "Stop"
$Project = "C:\wamp64\www\NewArchitecture_BrightCv"
$Branch = "agent/production-cv-builder"

Set-Location $Project
& git.exe switch $Branch
if ($LASTEXITCODE -ne 0) { throw "Could not switch to $Branch." }

& git.exe pull --ff-only origin $Branch
if ($LASTEXITCODE -ne 0) { throw "Git pull failed. Run git status and resolve local changes first." }

$php = Get-ChildItem "C:\wamp64\bin\php" -Filter "php.exe" -File -Recurse |
    Sort-Object { try { [version]($_.Directory.Name -replace '^php', '') } catch { [version]"0.0.0" } } -Descending |
    Select-Object -First 1 -ExpandProperty FullName

& $php "database\migrate.php"
if ($LASTEXITCODE -ne 0) { throw "Database migration failed." }

& npm.cmd ci
if ($LASTEXITCODE -ne 0) { throw "npm ci failed." }

& npm.cmd run check
if ($LASTEXITCODE -ne 0) { throw "The quality checks failed." }

Write-Host "BrightCV is updated and all tests passed." -ForegroundColor Green
Start-Process "http://localhost/NewArchitecture_BrightCv/public"
```

## Command to test without changing files

```powershell
Set-Location C:\wamp64\www\NewArchitecture_BrightCv
& npm.cmd run check
```

Run the PHP test suite directly with WAMP PHP:

```powershell
$php = Get-ChildItem "C:\wamp64\bin\php" -Filter "php.exe" -File -Recurse |
    Sort-Object { try { [version]($_.Directory.Name -replace '^php', '') } catch { [version]"0.0.0" } } -Descending |
    Select-Object -First 1 -ExpandProperty FullName

Set-Location C:\wamp64\www\NewArchitecture_BrightCv
& $php "tests\run.php"
```

## Command to use a fresh test database safely

This creates a separate database without deleting `brightcv_db` or the legacy
`lunettistar_db`:

```powershell
$Project = "C:\wamp64\www\NewArchitecture_BrightCv"
$Database = "brightcv_rebuild_test_db"
Set-Location $Project

$envPath = Join-Path $Project ".env"
$content = [System.IO.File]::ReadAllText($envPath)
$content = [regex]::Replace($content, '(?m)^DB_DATABASE=.*$', "DB_DATABASE=$Database")
[System.IO.File]::WriteAllText($envPath, $content, [System.Text.UTF8Encoding]::new($false))

$php = Get-ChildItem "C:\wamp64\bin\php" -Filter "php.exe" -File -Recurse |
    Sort-Object { try { [version]($_.Directory.Name -replace '^php', '') } catch { [version]"0.0.0" } } -Descending |
    Select-Object -First 1 -ExpandProperty FullName

& $php "database\migrate.php"
if ($LASTEXITCODE -ne 0) { throw "Fresh database migration failed." }

Write-Host "Fresh database $Database is ready." -ForegroundColor Green
Write-Host "Restart all WAMP services before opening the application."
```

To return to the normal local database, change the `.env` line back to:

```env
DB_DATABASE=brightcv_db
```

Then restart all WAMP services.

## If PowerShell reports that Git cannot pull

Run:

```powershell
Set-Location C:\wamp64\www\NewArchitecture_BrightCv
git status
```

Do not delete or overwrite the listed changes. Commit your work on a feature
branch or ask the project maintainer to help resolve it.
