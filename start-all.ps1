Start-Process powershell -ArgumentList "npm run dev --verbose"
Start-Process powershell -ArgumentList "php artisan queue:work -vvv"
Start-Process powershell -ArgumentList "php artisan reverb:start -vvv"
Start-Process powershell -ArgumentList "php artisan serve --port=8080" 