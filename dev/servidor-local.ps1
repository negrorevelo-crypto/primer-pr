# Alternativa sin Docker para Windows: necesita PHP 8.2+ en el PATH (con pdo_mysql activado).
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root
$envFile = if (Test-Path ".env") { ".env" } else { "dev/local.env.example" }
Get-Content $envFile | Where-Object { $_ -match '^\s*[A-Z_]+=' } | ForEach-Object {
    $k, $v = $_ -split '=', 2
    [Environment]::SetEnvironmentVariable($k.Trim(), $v.Trim().Trim("'"), 'Process')
}
$port = if ($env:PORT) { $env:PORT } else { 8080 }
Write-Host "Chelart local en http://localhost:$port  (Ctrl+C para detener)"
php -d display_errors=1 -S "localhost:$port" -t chelart/public dev/router.php
