param(
    [string]$MySqlData = $env:CAS_MYSQL_DATA,
    [string]$PythonPath = 'python',
    [string]$XamppPath = 'C:/xampp'
)
$ErrorActionPreference = 'Stop'
$previewRoot = Split-Path $PSScriptRoot -Parent
Set-Location -LiteralPath $previewRoot
New-Item -ItemType Directory -Path '.local' -Force | Out-Null
New-Item -ItemType Directory -Path '.local/sessions' -Force | Out-Null
$env:DB_PORT = '3307'
$env:DB_NAME = 'cas_hoa_customer_preview'
$env:CAS_APP_URL = 'http://127.0.0.1:8033'
function Test-PreviewPort([int]$Port) {
    $client = New-Object Net.Sockets.TcpClient
    try { $client.Connect('127.0.0.1', $Port); return $true } catch { return $false } finally { $client.Dispose() }
}
if (-not (Test-PreviewPort 3307)) {
    if (-not $MySqlData -or -not (Test-Path -LiteralPath (Join-Path $MySqlData 'my.ini'))) {
        throw 'Provide -MySqlData with the prepared isolated MariaDB data directory (port 3307). See docs/customer-preview.md.'
    }
    $dbProcess = Start-Process -FilePath "$XamppPath/mysql/bin/mysqld.exe" -ArgumentList @('--defaults-file="'+(Join-Path $MySqlData 'my.ini')+'"','--bind-address=127.0.0.1','--console') -WorkingDirectory $previewRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput '.local/mysql-out.log' -RedirectStandardError '.local/mysql-error.log'
    $dbProcess.Id | Set-Content '.local/mysql.pid'
    for ($attempt=0; $attempt -lt 20 -and -not (Test-PreviewPort 3307); $attempt++) { Start-Sleep -Milliseconds 500 }
    if (-not (Test-PreviewPort 3307)) { throw 'MariaDB did not start; see .local/mysql-error.log.' }
}
if (-not (Test-PreviewPort 1025)) {
    $smtpProcess = Start-Process -FilePath $PythonPath -ArgumentList @('scripts/customer-smtp.py') -WorkingDirectory $previewRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput '.local/smtp-out.log' -RedirectStandardError '.local/smtp-error.log'
    $smtpProcess.Id | Set-Content '.local/smtp.pid'
}
if (-not (Test-PreviewPort 8033)) {
    $phpPreview = Start-Process -FilePath "$XamppPath/php/php.exe" -ArgumentList @('-d','SMTP=127.0.0.1','-d','smtp_port=1025','-d','display_errors=0','-d','log_errors=1','-S','127.0.0.1:8033','scripts/customer-router.php') -WorkingDirectory $previewRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput '.local/php-out.log' -RedirectStandardError '.local/php-error.log'
    $phpPreview.Id | Set-Content '.local/php.pid'
}
Write-Output 'Customer preview: http://127.0.0.1:8033/index.php'

