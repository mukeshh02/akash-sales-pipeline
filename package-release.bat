@echo off
:: ================================================================
:: AkashSalesPipeline — Release Packager
:: Run this from: modules\AkashSalesPipeline\
:: Usage: package-release.bat 2.0.0
:: ================================================================

set VERSION=%1
if "%VERSION%"=="" (
    echo ERROR: Version required. Usage: package-release.bat 2.0.0
    exit /b 1
)

echo.
echo ============================================
echo  Packaging AkashSalesPipeline v%VERSION%
echo ============================================
echo.

:: Go to Laravel root (2 levels up from module folder)
cd ..\..

echo [1/5] Building frontend assets (npm run build)...
call npm run build
if errorlevel 1 (
    echo ERROR: npm run build failed!
    pause
    exit /b 1
)
echo Done.

echo [2/5] Updating version in module.json...
cd modules\AkashSalesPipeline
powershell -Command "(Get-Content module.json) -replace '\"version\": \"[^\"]+\"', '\"version\": \"%VERSION%\"' | Set-Content module.json"
echo Done.

echo [3/5] Committing version bump...
git add module.json
git commit -m "chore: bump version to v%VERSION%"
git push origin main

echo [4/5] Creating release ZIP (module code + compiled assets)...
cd ..\..

:: Create temp folder for packaging
if exist ".release-tmp" rmdir /s /q ".release-tmp"
mkdir ".release-tmp\AkashSalesPipeline"
mkdir ".release-tmp\public"

:: Copy module files (exclude git, node_modules)
robocopy "modules\AkashSalesPipeline" ".release-tmp\AkashSalesPipeline" /E /XD .git node_modules /XF package-release.bat *.bat > nul

:: Copy compiled assets (the pre-built public/build folder)
robocopy "public\build" ".release-tmp\public\build" /E > nul

:: Create ZIP using PowerShell
set ZIPNAME=AkashSalesPipeline-v%VERSION%.zip
powershell -Command "Compress-Archive -Path '.release-tmp\*' -DestinationPath '%ZIPNAME%' -Force"
echo ZIP created: %ZIPNAME%

:: Cleanup temp
rmdir /s /q ".release-tmp"

echo [5/5] Creating GitHub Release and uploading ZIP...
cd modules\AkashSalesPipeline
git tag -a "v%VERSION%" -m "v%VERSION%"
git push origin "v%VERSION%"

C:\gh-cli\bin\gh.exe release create "v%VERSION%" "..\..\AkashSalesPipeline-v%VERSION%.zip" --title "v%VERSION%" --generate-notes

:: Move ZIP to releases folder for reference
if not exist "..\..\releases" mkdir "..\..\releases"
move "..\..\AkashSalesPipeline-v%VERSION%.zip" "..\..\releases\" > nul

echo.
echo ============================================
echo  DONE! v%VERSION% released on GitHub
echo  ZIP includes: module code + compiled assets
echo ============================================
echo.
pause
