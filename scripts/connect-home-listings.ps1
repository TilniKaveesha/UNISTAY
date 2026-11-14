# Run this from your UNISTAY repository root AFTER extracting the ZIP.
# It updates only the public home listing section and related URLs/CSS.
$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$homeFile = Join-Path $repoRoot 'frontend/src/pages/home.php'
if (!(Test-Path $homeFile)) { throw "Missing $homeFile" }
$original = [System.IO.File]::ReadAllText($homeFile)
$text = $original
$featuredPattern = '(?s)<!--\s*FEATURED LISTINGS\s*-->.*?<!--\s*ABOUT UNISTAY\s*-->'
$newSection = @'
<!-- FEATURED LISTINGS -->
<?php include __DIR__ . '/../components/featured_boardings.php'; ?>

<!--ABOUT UNISTAY-->
'@
if ($text -notmatch $featuredPattern) {
    if ($text -notmatch 'featured_boardings\.php') {
        throw 'Could not locate the original FEATURED LISTINGS / ABOUT UNISTAY section. Make the change manually using README.md.'
    }
} else {
    $text = [regex]::Replace($text, $featuredPattern, [System.Text.RegularExpressions.MatchEvaluator]{param($m) $newSection}, [System.Text.RegularExpressions.RegexOptions]::Singleline)
}
$text = $text.Replace('/UNISTAY/frontend/src/styles/style.css', '/src/styles/style.css')
if ($text -notmatch 'public-listings\.css') {
    $cssLine = '    <link rel="stylesheet" href="/src/styles/public-listings.css">' + [Environment]::NewLine
    $text = $text.Replace('</head>', $cssLine + '</head>')
}
$text = $text.Replace('action="listings.php"', 'action="/public/listings.php"')
# The original home university dropdown was disabled; replace it with live universities.
$uniPattern = '(?s)<select\s+name="university"\s+id="university".*?</select>'
if ([regex]::IsMatch($text, $uniPattern)) {
    $text = [regex]::Replace($text, $uniPattern, [System.Text.RegularExpressions.MatchEvaluator]{param($m) "<?php include __DIR__ . '/../components/public_university_select.php'; ?>"})
}
$text = $text.Replace("url('<?php echo `$baseUrl; ?>../src/assets/images/home-background.jpg')", "url('/src/assets/images/home-background.jpg')")
# Fix duplicated header at the bottom if still present.
$headerLine = "<?php include __DIR__ . '/../components/header.php'; ?>"
$footerLine = "<?php include __DIR__ . '/../components/footer.php'; ?>"
$headCount = ([regex]::Matches($text, [regex]::Escape($headerLine))).Count
if ($headCount -ge 2) {
    $last = $text.LastIndexOf($headerLine)
    $text = $text.Substring(0, $last) + $footerLine + $text.Substring($last + $headerLine.Length)
}
if ($text -eq $original) {
    Write-Host 'Homepage already integrated; no changes made.'
    exit 0
}
$backup = "$homeFile.backup-before-public-listings"
if (!(Test-Path $backup)) { [System.IO.File]::WriteAllText($backup, $original, [System.Text.UTF8Encoding]::new($false)) }
[System.IO.File]::WriteAllText($homeFile, $text, [System.Text.UTF8Encoding]::new($false))
Write-Host 'Updated homepage:' $homeFile
Write-Host 'Backup:' $backup
