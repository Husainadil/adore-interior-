param([Parameter(ValueFromRemainingArguments=$true)][string[]]$Arguments)
& C:\php85\php.exe -d extension_dir=C:\php85\ext -d opcache.enable=0 -d opcache.enable_cli=0 -d extension=gd -d extension=openssl -d extension=fileinfo -d extension=pdo_sqlite @Arguments
exit $LASTEXITCODE
