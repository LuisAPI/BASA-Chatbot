# Start all services for BASA Chatbot
Write-Host "Starting BASA Chatbot services..." -ForegroundColor Green

# Start Laravel development server
Write-Host "Starting Laravel server..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoExit", "-Command", "php artisan serve --port=8080"

# Start queue worker
Write-Host "Starting queue worker..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoExit", "-Command", "php artisan queue:work"

# Start Vite development server
Write-Host "Starting Vite development server..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoExit", "-Command", "npm run dev"

Write-Host "All services started!" -ForegroundColor Green
Write-Host "Laravel: http://127.0.0.1:8080" -ForegroundColor Cyan
Write-Host "Vite: http://localhost:5173" -ForegroundColor Cyan
Write-Host "File processing status: Polling-based (no WebSocket required)" -ForegroundColor Cyan 