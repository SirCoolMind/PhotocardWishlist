@echo off
echo Starting Photocard Wishlist Tracker at http://localhost:8080 ...
start http://localhost:8080
"C:\laragon\bin\php\php-8.3.29-Win32-vs16-x64\php.exe" -S localhost:8080 -t "%~dp0"
