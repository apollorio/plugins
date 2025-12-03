@echo off
setlocal
cd /d "C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins"

echo.
echo  ██████  APOLLO → AUTO-FIX WORDPRESS CODING STANDARDS (PHPCBF)
echo  ================================================================
echo .
set XDEBUG_MODE=off
phpcbf -d memory_limit=2G --extensions=php -sp .\apollo-core\ .\apollo-events-manager\ .\apollo-social\
echo.
echo  ╔══════════════════════════════════════════════════╗
echo  ║    AUTO-FIX CONCLUÍDO!                           ║
echo  ║    Agora dá dois cliques no check.bat            ║
echo  ╚══════════════════════════════════════════════════╝
echo.
pause
cmd /k
