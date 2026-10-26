param([int]$Port=8766)
$ErrorActionPreference='Stop'
Set-Location -LiteralPath $PSScriptRoot
$studioPhp=if(Test-Path -LiteralPath 'C:\php85\php.exe'){'C:\php85\php.exe'}else{(Get-Command php).Source}
$studioExtensionPath=Join-Path (Split-Path $studioPhp) 'ext'
$studioExtensions=@('-d','opcache.enable=0','-d','opcache.enable_cli=0')
if($studioPhp -eq 'C:\php85\php.exe'){$studioExtensions+=@('-d',"extension_dir=$studioExtensionPath",'-d','extension=openssl','-d','extension=fileinfo','-d','extension=pdo_sqlite','-d','extension=gd')}
Write-Host "Adore Interior: http://127.0.0.1:$Port"
& $studioPhp @studioExtensions -S "127.0.0.1:$Port" -t public
exit $LASTEXITCODE
