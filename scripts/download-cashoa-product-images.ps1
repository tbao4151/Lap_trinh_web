# Download 55 original product photos from the public Supabase Storage bucket.
# Read-only requests; no authentication, no change to cashoa.vn.
# Run from PowerShell: powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\download-cashoa-product-images.ps1
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$base = 'https://kbfcxsbibrtafkokhfev.supabase.co/storage/v1/object/public/product-images/'
$items = @(
    'a56380a1-3449-432f-8ae6-2a26fa818f8f/034f8716-1414-4649-92fe-67947206ed9b.webp'
    'a56380a1-3449-432f-8ae6-2a26fa818f8f/c6a09ca2-8ce0-4446-803d-cc129f75125d.webp'
    'a56380a1-3449-432f-8ae6-2a26fa818f8f/b62e1848-2867-418a-bd60-44af83e874dd.webp'
    '8547b8ed-851b-496b-a0f7-8b6efb09cff7/5a1093ef-31a4-4314-8e16-7550dedf9962.webp'
    '76a1a576-b953-46cc-917f-35d87cae8b3e/e0bf9a3a-9ca2-417c-9ed2-1b6cb79351a2.webp'
    'a83c8c85-1dab-40e9-9c44-822022586763/a7b99d26-7311-4bcc-bad3-18fa418215da.webp'
    'a83c8c85-1dab-40e9-9c44-822022586763/16be16d6-a79d-492e-98ee-33c5f0e150aa.webp'
    '70fba3a6-debe-4186-9cfc-16da88fe2f79/2f922609-34d4-4a91-a5c4-378efe19aa0f.webp'
    '70fba3a6-debe-4186-9cfc-16da88fe2f79/0639a5bc-83a4-4f19-882d-3e2595dfdc54.webp'
    '7b77d8e5-eab6-4138-914d-f7b0e7fb1139/bbddb1aa-ee32-4035-8f6a-a7ae76e84fd3.webp'
    '7b77d8e5-eab6-4138-914d-f7b0e7fb1139/6a2ab346-669b-41a4-9773-c8ca3224287f.webp'
    '9ea6429d-d2b1-4f6c-9462-5fce763df104/5289c59f-e555-435d-8d70-1b9c287fdbc9.webp'
    '46f107f7-79a4-439c-ba0d-c03ecf613e49/15480b70-7f0b-4f43-8955-10b9874a574c.webp'
    '46f107f7-79a4-439c-ba0d-c03ecf613e49/add6a0dc-ad99-4b62-81fb-a7e8bf0177b4.webp'
    '84ad54f1-6553-418b-8632-aa5150435157/95987b3c-47b4-4038-b977-46f19c5b68b0.webp'
    '1fee298e-1f08-4d29-a421-121ac034a03f/935c8bc5-dbc3-423d-81f8-aee00e9bfbb9.webp'
    '1fee298e-1f08-4d29-a421-121ac034a03f/31ee9431-4d2f-427c-bd72-63337858c82d.webp'
    '304eca36-c60f-44ee-8012-740402b9d1a5/e77c578b-63b2-4d8b-9f75-d143f51d4e3a.webp'
    '304eca36-c60f-44ee-8012-740402b9d1a5/7c545e5b-f688-4acd-9ed7-d2f92be10b76.webp'
    '23f52dd3-3a79-480b-a9cf-1c25f9e2b874/ff6416f9-8bb2-4ad0-9b2e-5a7afed74e43.webp'
    '1b463aec-4caf-4cbc-bf57-ced437436d4a/c0fdda15-ec1d-40fc-b141-007c3ec6b108.webp'
    '1b463aec-4caf-4cbc-bf57-ced437436d4a/a1be7d2d-3d1a-4f99-aac2-b6e02d08a85f.webp'
    '55828f63-af1e-482c-9622-52540f3a9c26/1aef43a5-bcba-4f40-9e6b-882bbb89bbda.jpg'
    '55828f63-af1e-482c-9622-52540f3a9c26/57d0966e-dd36-45dc-a2d9-fd86cbd15f84.jpg'
    '5996f873-720c-4515-a834-498a086feaa9/723b59bb-2c3d-4e51-aa3d-f9b063ec78f8.webp'
    'bd7f4366-960a-4428-8c8b-12c4fe8582a7/2a07fd2c-b35b-49e2-b12c-cd89cc585ed9.webp'
    '0b3f8d1f-0f4e-4c05-9c98-ef5442a5be86/c4f54456-f6b4-49f4-80ba-f08d3e894354.webp'
    '95aa0cf0-9b23-40cb-a1eb-3ab2784e433c/55a1d26d-a34d-4c8f-8d3c-7a5bce477585.webp'
    '6120050e-ff67-4d56-b16d-1aba2d130974/e2482c3e-ad03-4575-b0e8-8281f191d604.webp'
    '6120050e-ff67-4d56-b16d-1aba2d130974/480b671b-db46-4ee9-a876-0ba8df95cb3b.webp'
    'a559bbca-0103-48ff-9a8a-c519806a4c48/ce8f0b33-ee4d-470d-98a4-da57ee5dabb3.jpg'
    'a559bbca-0103-48ff-9a8a-c519806a4c48/b571d3ad-a16e-47be-8e6f-0528f304dd5f.jpg'
    '3022292d-0a2e-42c0-b83b-892cad79ec42/c7a3c373-a0ce-4ffd-8e8e-8c724ae078fd.webp'
    'd529f596-48f6-4be0-af80-b4382d24852e/108f85ac-ea8c-4717-b5c1-94904e3cefd3.webp'
    'd529f596-48f6-4be0-af80-b4382d24852e/68a50b9e-5b18-4901-ba97-69d2aa499a10.webp'
    '8e1c5d66-77a5-427e-a5b3-aca98c56afc9/a9ff4190-f462-4e4f-9727-3bea3a62e8d8.webp'
    '8e1c5d66-77a5-427e-a5b3-aca98c56afc9/df606cf1-b92c-4d4b-9188-88fecf1370b7.webp'
    'dc1b9878-1664-419f-8708-0e1c1b746f82/9b7d64d9-4470-4ce7-8f5f-d99d3d5a1807.webp'
    '5824129c-83a2-40e3-9e8d-e720fc2dd0df/d8ea80df-240c-4c85-98f7-e31f6b0405b3.webp'
    'bb823b36-3d9b-4987-a117-11c38812a561/e432650f-3839-42e3-8406-61c135a2904b.webp'
    'bb823b36-3d9b-4987-a117-11c38812a561/95f47d38-1acd-4a0b-9fca-09e04c52a1c5.webp'
    '7699793d-5757-4549-93b0-b93eaa5696f4/4c85dd4d-9254-44a4-86b2-f2f72425fd76.webp'
    'ff1907a8-5f13-4443-b583-d47b8557baea/8425df69-6b12-4778-a787-f1e9c75654b6.webp'
    '5d4e2d4b-5286-4571-bb77-3b415a604b86/47ca096f-3be8-4a97-ae65-a06878db111c.webp'
    '5d4e2d4b-5286-4571-bb77-3b415a604b86/5f8c9479-3761-4dd5-94d6-e1ea49540aec.webp'
    '87040b29-ec8d-479b-b487-bc787a62341c/caf4fe8b-7eb2-45d0-89b7-1c563ee15521.webp'
    '05c87b60-b7f2-4733-85cd-d4e2ec76f086/9e60d2e4-a8fe-40f1-8875-452a8d5e6326.webp'
    '5f10b491-50b7-4324-b0ff-5baa70b49fcb/6ea30f59-eba8-40e8-a633-39c00e55286c.jpg'
    '5f10b491-50b7-4324-b0ff-5baa70b49fcb/8b2efbbe-2880-4c05-9e3a-5b57fa630203.jpg'
    '581afe7d-fd32-47e4-859b-143bf6d59463/584db5b7-d3be-48c7-89b9-3e4d960d33b0.jpg'
    '581afe7d-fd32-47e4-859b-143bf6d59463/0c9537bb-0fd9-404b-b925-e18f5a4ac45b.jpg'
    '0560586f-ee68-4bcf-95df-0e07ddabf0ab/303e00c1-6e97-44ee-b430-c2a86c2d43a5.webp'
    '0560586f-ee68-4bcf-95df-0e07ddabf0ab/b27c4c8a-257a-49dd-a78b-b8a12b10bc56.webp'
    '276fa986-f1ea-4ac9-ace5-1bce10e3c0db/42b56c7c-f145-44e8-bf97-05423ea612c0.webp'
    '276fa986-f1ea-4ac9-ace5-1bce10e3c0db/a91bb8f3-0fc2-4ac4-bce8-a1e755af9644.webp'
)
$good = 0
$failed = @()
foreach ($item in $items) {
    $relative = ('assets/images/cashoa_catalog/' + $item).Replace('/', [System.IO.Path]::DirectorySeparatorChar)
    $destination = Join-Path $root $relative
    $parent = Split-Path -Parent $destination
    if (-not (Test-Path -LiteralPath $parent)) { New-Item -ItemType Directory -Path $parent -Force | Out-Null }
    if ((Test-Path -LiteralPath $destination) -and (Get-Item -LiteralPath $destination).Length -gt 0) { $good++; continue }
    $uri = $base + $item
    try {
        Invoke-WebRequest -Uri $uri -OutFile $destination -UseBasicParsing -TimeoutSec 45 -ErrorAction Stop
        if ((Get-Item -LiteralPath $destination).Length -eq 0) { throw 'Empty image file' }
        $good++
        Write-Host ("OK {0}/{1}: {2}" -f $good, $items.Count, $item)
    } catch {
        if (Test-Path -LiteralPath $destination) { Remove-Item -LiteralPath $destination -Force }
        $failed += $item
        Write-Warning ("FAILED: {0} ({1})" -f $item, $_.Exception.Message)
    }
}
Write-Host ("Downloaded/verified {0}/{1} images." -f $good, $items.Count)
if ($failed.Count -gt 0) { throw ("Some images failed: " + ($failed -join ', ')) }
