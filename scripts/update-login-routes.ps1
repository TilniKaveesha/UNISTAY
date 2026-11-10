# Preview old /login.php references by default. Pass -Apply to rewrite to /public/login.php.
# Does not touch existing /public/login.php references, Google API routes, or private configs.
param([switch]$Apply)
$ErrorActionPreference = 'Stop'
$repo = Split-Path -Parent $PSScriptRoot
$pattern = '(?<![a-zA-Z0-9_/-])/login\.php\b'
$replacement = '/public/login.php'
$targets = @()
foreach ($folder in @('frontend','backend')) {
    $dir = Join-Path $repo $folder
    if (Test-Path $dir) {
        $targets += Get-ChildItem -Path $dir -Recurse -File | Where-Object {
            $_.Extension -in @('.php','.js','.html')
        }
    }
}
$changed = 0
foreach ($f in $targets) {
    $source = [System.IO.File]::ReadAllText($f.FullName)
    if ([regex]::IsMatch($source, $pattern)) {
        $relative = $f.FullName.Substring($repo.Length).TrimStart([char[]]@('\', '/'))
        if ($Apply) { Write-Host "Updating $relative" }
        else { Write-Host "Would update $relative" }
        if ($Apply) {
            $updated = [regex]::Replace($source, $pattern, $replacement)
            [System.IO.File]::WriteAllText($f.FullName, $updated, [System.Text.UTF8Encoding]::new($false))
        }
        $changed++
    }
}
if ($changed -eq 0) { Write-Host 'No old /login.php paths found.' }
elseif (-not $Apply) { Write-Host 'Preview only. Run with -Apply to update these references.' }
