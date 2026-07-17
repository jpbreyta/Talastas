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

if (-not (Test-Path ".venv\Scripts\python.exe")) {
    python -m venv .venv
}
else {
    Write-Host "Python virtual environment already exists."
}

Write-Host ""
Write-Host "Installing Python packages..."

& ".\.venv\Scripts\python.exe" -m pip install --upgrade pip
& ".\.venv\Scripts\python.exe" -m pip install -r "python_service\requirements.txt"

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