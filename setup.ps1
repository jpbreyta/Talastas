Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# Always run commands from the folder containing this script.
$ProjectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ProjectRoot

function Write-Section {
    param(
        [Parameter(Mandatory)]
        [string] $Message
    )

    Write-Host ""
    Write-Host "=================================================="
    Write-Host $Message
    Write-Host "=================================================="
}

function Confirm-CommandSuccess {
    param(
        [Parameter(Mandatory)]
        [string] $CommandName
    )

    if ($LASTEXITCODE -ne 0) {
        throw "$CommandName failed with exit code $LASTEXITCODE."
    }
}

function Find-NativeWindowsPython {
    $candidates = [System.Collections.Generic.List[string]]::new()

    # Allow a developer to manually specify Python.
    if ($env:PYTHON_EXE) {
        $candidates.Add($env:PYTHON_EXE)
    }

    # Find Python commands currently available in PATH.
    foreach ($commandName in @("python", "python3")) {
        $commands = Get-Command $commandName -All -ErrorAction SilentlyContinue

        foreach ($command in $commands) {
            if ($command.Source) {
                $candidates.Add($command.Source)
            }
        }
    }

    # Search common native Windows Python locations.
    $commonPythonPaths = @(
        "$env:LOCALAPPDATA\Programs\Python\Python*\python.exe",
        "$env:LOCALAPPDATA\Microsoft\WindowsApps\PythonSoftwareFoundation.Python.*\python.exe",
        "$env:ProgramFiles\Python*\python.exe"
    )

    foreach ($pathPattern in $commonPythonPaths) {
        $matchingPaths = Get-ChildItem -Path $pathPattern -ErrorAction SilentlyContinue

        foreach ($matchingPath in $matchingPaths) {
            $candidates.Add($matchingPath.FullName)
        }
    }

    $uniqueCandidates = $candidates |
        Where-Object { $_ } |
        Select-Object -Unique

    foreach ($candidate in $uniqueCandidates) {
        # Skip Python installations supplied by MSYS2, Git Bash, or Cygwin.
        if ($candidate -match "\\msys64\\" -or
            $candidate -match "\\cygwin" -or
            $candidate -match "\\Git\\usr\\bin\\") {
            continue
        }

        try {
            $output = & $candidate -c "import sys; print(sys.executable)" 2>$null

            if ($LASTEXITCODE -ne 0 -or -not $output) {
                continue
            }

            $resolvedPython = [string]($output | Select-Object -Last 1)
            $resolvedPython = $resolvedPython.Trim()

            if (-not $resolvedPython) {
                continue
            }

            if ($resolvedPython -match "\\msys64\\" -or
                $resolvedPython -match "\\cygwin" -or
                $resolvedPython -match "\\Git\\usr\\bin\\") {
                continue
            }

            return $resolvedPython
        }
        catch {
            continue
        }
    }

    return $null
}

Write-Section "Checking required development tools"

$requiredCommands = @(
    @{
        Name    = "PHP"
        Command = "php"
    },
    @{
        Name    = "Composer"
        Command = "composer"
    },
    @{
        Name    = "Node.js"
        Command = "node"
    },
    @{
        Name    = "npm"
        Command = "npm"
    }
)

$missingTools = [System.Collections.Generic.List[string]]::new()

foreach ($tool in $requiredCommands) {
    $command = Get-Command $tool.Command -ErrorAction SilentlyContinue

    if (-not $command) {
        $missingTools.Add($tool.Name)
        Write-Host "[MISSING] $($tool.Name)"
    }
    else {
        Write-Host "[FOUND]   $($tool.Name)"
    }
}

$pythonExe = Find-NativeWindowsPython

if (-not $pythonExe) {
    $missingTools.Add("Native Windows Python")
    Write-Host "[MISSING] Native Windows Python"
}
else {
    Write-Host "[FOUND]   Native Windows Python"
    Write-Host "          $pythonExe"

    & $pythonExe --version
    Confirm-CommandSuccess "Python version check"
}

if ($missingTools.Count -gt 0) {
    Write-Host ""
    Write-Host "Install the following tools before continuing:"

    foreach ($missingTool in $missingTools) {
        Write-Host "- $missingTool"
    }

    Write-Host ""
    Write-Host "The MSYS2 Python installation is not supported by this setup script."
    exit 1
}

Write-Section "Checking required project files"

$requiredFiles = @(
    "composer.json",
    "package.json",
    "python_service\requirements.txt",
    ".env.example",
    "artisan"
)

foreach ($file in $requiredFiles) {
    if (-not (Test-Path -LiteralPath $file)) {
        throw "Required project file is missing: $file"
    }

    Write-Host "[FOUND] $file"
}

Write-Section "Installing Laravel packages"

& composer install

Confirm-CommandSuccess "Composer installation"

Write-Section "Installing Node packages"

if (Test-Path -LiteralPath "package-lock.json") {
    Write-Host "Using package-lock.json with npm ci..."
    & npm ci
}
else {
    Write-Host "package-lock.json was not found. Using npm install..."
    & npm install
}

Confirm-CommandSuccess "Node package installation"

Write-Section "Preparing Python virtual environment"

$venvPath = Join-Path $ProjectRoot ".venv"
$venvPython = Join-Path $venvPath "Scripts\python.exe"
$recreateVenv = $false

if (Test-Path -LiteralPath $venvPython) {
    Write-Host "Checking the existing Python virtual environment..."

    & $venvPython -m pip --version *> $null

    if ($LASTEXITCODE -ne 0) {
        Write-Host "The existing virtual environment is incomplete."
        $recreateVenv = $true
    }
}
elseif (Test-Path -LiteralPath $venvPath) {
    Write-Host "The existing virtual environment is incomplete."
    $recreateVenv = $true
}
else {
    $recreateVenv = $true
}

if ($recreateVenv) {
    if (Test-Path -LiteralPath $venvPath) {
        Write-Host "Removing the incomplete virtual environment..."
        Remove-Item -LiteralPath $venvPath -Recurse -Force
    }

    Write-Host "Creating a new virtual environment..."
    Write-Host "Using: $pythonExe"

    & $pythonExe -m venv $venvPath

    Confirm-CommandSuccess "Python virtual environment creation"
}
else {
    Write-Host "Python virtual environment already exists and is working."
}

if (-not (Test-Path -LiteralPath $venvPython)) {
    throw "Virtual environment Python was not created successfully."
}

Write-Section "Installing Python packages"

& $venvPython -m pip install --upgrade pip

Confirm-CommandSuccess "pip upgrade"

& $venvPython -m pip install `
    -r "python_service\requirements.txt"

Confirm-CommandSuccess "Python requirements installation"

Write-Section "Preparing Laravel environment"

if (-not (Test-Path -LiteralPath ".env")) {
    Write-Host "Creating .env from .env.example..."
    Copy-Item -LiteralPath ".env.example" -Destination ".env"
}
else {
    Write-Host ".env already exists."
}

$envContent = Get-Content -LiteralPath ".env" -Raw

if ($envContent -notmatch "(?m)^APP_KEY=\S+") {
    Write-Host "Generating the Laravel application key..."

    & php artisan key:generate

    Confirm-CommandSuccess "Laravel application key generation"
}
else {
    Write-Host "Laravel application key already exists."
}

Write-Section "Clearing Laravel caches"

& php artisan optimize:clear

Confirm-CommandSuccess "Laravel cache clearing"

Write-Section "Setup completed successfully"

Write-Host "Native Python used:"
Write-Host $pythonExe

Write-Host ""
Write-Host "Virtual environment:"
Write-Host $venvPython

Write-Host ""
Write-Host "Configure the database connection in .env."
Write-Host ""
Write-Host "After configuring the database, run:"
Write-Host "php artisan migrate --seed"

Write-Host ""
Write-Host "Start the complete development stack with:"
Write-Host "npm run dev:all"