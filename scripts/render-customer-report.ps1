# Mở lại DOCX bằng Word ở chế độ chỉ đọc và xuất PDF để kiểm tra hình thức.
param([string]$SourcePath, [string]$OutputFolder, [switch]$UpdateFields)
$ErrorActionPreference = 'Stop'
$reportRoot = Split-Path $PSScriptRoot -Parent
$source = Join-Path $reportRoot 'docs\BAO_CAO_THANH_VIEN_2_CUSTOMER.docx'
$renderFolder = Join-Path $reportRoot '.local\docx-render'
if ($SourcePath) { $source = $SourcePath }
if ($OutputFolder) { $renderFolder = $OutputFolder }
New-Item -ItemType Directory -Force -Path $renderFolder | Out-Null
$word = $null
$document = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $word.AutomationSecurity = 3
    $document = $word.Documents.Open($source, $false, (-not $UpdateFields))
    if ($UpdateFields) {
        [void]$document.Fields.Update()
        foreach ($toc in $document.TablesOfContents) { $toc.Update() }
        $document.Repaginate()
        foreach ($toc in $document.TablesOfContents) { $toc.UpdatePageNumbers() }
        $document.Save()
    }
    $document.Repaginate()
    $pageCount = $document.ComputeStatistics(2)
    $document.ExportAsFixedFormat((Join-Path $renderFolder 'report.pdf'), 17)
    [pscustomobject]@{pages=$pageCount; renderer='Microsoft Word'; readOnly=$true; status='PASS'} |
        ConvertTo-Json | Set-Content -Encoding utf8 (Join-Path $renderFolder 'render-result.json')
    Write-Output ('Đã mở lại và xuất ' + $pageCount + ' trang bằng Microsoft Word.')
} finally {
    if ($document) { $document.Close(0); [void][Runtime.InteropServices.Marshal]::ReleaseComObject($document) }
    if ($word) { $word.Quit(); [void][Runtime.InteropServices.Marshal]::ReleaseComObject($word) }
}
