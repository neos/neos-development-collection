@echo off
echo.
echo.

echo Building single file HTML version in build/singlehtml ...
call make.bat singlehtml
echo.
echo.

echo Building HTML version in build/html ...
call make.bat html
echo.
echo.

echo Starting build/html/Index.html in browser ...
start build\html\Index.html
echo.
echo.


echo Finished.
pause