:: START WEBDRIVER (SELENIUM)
@ECHO OFF
start "Selenium Server" /MIN java.exe -jar C:\selenium-server-standalone-2.53.1.jar >nul 2>&1
sleep 2 >nul 2>&1
ECHO ON

:: RUN CODECEPTION
cd ../plugin/swifty-page-manager/lib/swifty_plugin/test/
::php codecept.phar run acceptance --steps --env firefox --debug
php codecept.phar run acceptance --steps --env firefox --silent

:: STOP WEBDRIVER (SELENIUM)
@ECHO OFF
taskkill /f /fi "WINDOWTITLE eq Selenium Server" >nul 2>&1
ECHO ON

:: If you need to kill all running Selenium servers (will also kill all java apps?):
:: # taskkill /f /im java.exe
