@echo off
setlocal

cd /d "C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins"

title APOLLO - WORDPRESS CODING STANDARDS CHECK

echo.
echo  ██████  APOLLO → WORDPRESS CODING STANDARDS CHECK (PHPCS)
echo  ===========================================================
set XDEBUG_MODE=off

phpcs -d memory_limit=1054G --extensions=php --colors -sp .\apollo-core\ .\apollo-events-manager\ .\apollo-social\

echo.
echo  ╔═══════════════════════════════════════════════════════════╗
echo  ║   SCAN CONCLUÍDO!                                         ║
echo  ║   • Sem letras vermelhas (E) = 100% aprovado para .org    ║
echo  ║   • Só . F S e poucos W = tá lindo e profissional         ║
echo  ╚═══════════════════════════════════════════════════════════╝
echo.

pause

cmd /k
