$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "Checking required development tools..."

$requiredCommands = @{
    "PHP"      = "php"
    "Composer" = "composer"
    "Node.js"  = "node"
    "npm"      = "npm"
    "Python"   = "python"
}

$missingTools = @()

foreach ($tool in $requiredCommands.GetEnumerator()) {
    if (-not (Get-Command $tool.Value -ErrorAction SilentlyContinue)) {
        $missingTools += $tool.Key
        Write-Host "[MISSING] $($tool.Key)"
    }
    else {
        Write-Host "[FOUND] $($tool.Key)"
    }
}

if ($missingTools.Count -gt 0) {
    Write-Host ""
    Write-Host "Install these missing tools first:"
    $missingTools | ForEach-Object {
        Write-Host "- $_"
    }

    exit 1
}

Write-Host ""
Write-Host "Checking required project files..."

$requiredFiles = @(
    "composer.json",
    "package.json",
    "python_service\requirements.txt"
)

foreach ($file in $requiredFiles) {
    if (-not (Test-Path $file)) {
        Write-Host "[MISSING] $file"
        exit 1
    }

    Write-Host "[FOUND] $file"
}

Write-Host ""
Write-Host "Installing Laravel packages..."
composer install

Write-Host ""
Write-Host "Installing Node packages..."

if (Test-Path "package-lock.json") {
    npm ci
}
else {
    npm install
}

Write-Host ""
Write-Host "Preparing Python virtual environment..."

# Use a manually supplied Python path when available.
if ($env:PYTHON_EXE -and (Test-Path -LiteralPath $env:PYTHON_EXE)) {
    $pythonExe = $env:PYTHON_EXE
}
else {
    # Find a normal Windows Python installation.
    $pythonExe = Get-ChildItem `
        "$env:LOCALAPPDATA\Programs\Python" `
        -Recurse `
        -Filter "python.exe" `
        -File `
        -ErrorAction SilentlyContinue |
        Where-Object {
            $_.FullName -notmatch "\\Launcher\\" -and
            $_.FullName -notmatch "\\msys64\\"
        } |
        Select-Object -First 1 -ExpandProperty FullName
}

if (-not $pythonExe) {
    throw "Native Windows Python was not found."
}

if (-not (Test-Path -LiteralPath $pythonExe)) {
    throw "Python executable does not exist: $pythonExe"
}

Write-Host "Using Python:"
Write-Host $pythonExe

& $pythonExe --version

if ($LASTEXITCODE -ne 0) {
    throw "The selected Python installation could not run."
}

$venvPath = Join-Path $PSScriptRoot ".venv"
$venvPython = Join-Path $venvPath "Scripts\python.exe"

if (-not (Test-Path -LiteralPath $venvPython)) {
    if (Test-Path -LiteralPath $venvPath) {
        Write-Host "Removing incomplete virtual environment..."
        Remove-Item -LiteralPath $venvPath -Recurse -Force
    }

    Write-Host "Creating Python virtual environment..."

    & $pythonExe -m venv $venvPath

    if ($LASTEXITCODE -ne 0) {
        throw "Failed to create the Python virtual environment."
    }
}
else {
    Write-Host "Python virtual environment already exists."
}

if (-not (Test-Path -LiteralPath $venvPython)) {
    throw "Virtual environment Python was not created: $venvPython"
}

Write-Host ""
Write-Host "Installing Python packages..."

& $venvPython -m ensurepip --upgrade

if ($LASTEXITCODE -ne 0) {
    throw "Failed to install pip."
}

& $venvPython -m pip install --upgrade pip

if ($LASTEXITCODE -ne 0) {
    throw "Failed to upgrade pip."
}

& $venvPython -m pip install `
    -r (Join-Path $PSScriptRoot "python_service\requirements.txt")

if ($LASTEXITCODE -ne 0) {
    throw "Failed to install Python requirements."
}

Write-Host ""
Write-Host "Preparing Laravel environment..."

if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        php artisan key:generate
    }
    else {
        Write-Host ".env.example was not found. Create .env manually."
    }
}
else {
    Write-Host ".env already exists."
}

Write-Host ""
Write-Host "Clearing Laravel caches..."

php artisan optimize:clear

Write-Host ""
Write-Host "Setup completed successfully."
Write-Host ""
Write-Host "Configure your database in .env if necessary."
Write-Host "Then run:"
Write-Host "php artisan migrate --seed"
Write-Host ""
Write-Host "Start the project with:"
Write-Host "npm run dev:all"