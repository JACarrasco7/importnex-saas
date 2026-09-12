@echo off
setlocal
set "REPO=C:\laragon\www\importnexcore"
set "SKILL=%REPO%\.claude\skills\estudio-mercado"
set "SRC=%~dp0"

echo ===============================================================
echo  estudio-mercado v0.4.0 - fix del IVA fantasma
echo ===============================================================
echo.
echo Copia 3 ficheros corregidos a .claude\skills\estudio-mercado\
echo (el puente remoto de Claude no puede escribir ahi) y hace
echo commit + push.
echo.
echo   - SKILL.md            (0.3.12 -^> 0.4.0)
echo   - informe_mercado.md  (quita el 21%% de IVA inexistente)
echo   - CHANGELOG.md        (entrada 0.4.0 con los 3 hallazgos)
echo.

if not exist "%REPO%\.git" (
  echo ERROR: no encuentro %REPO%\.git - ajusta la variable REPO arriba.
  pause
  exit /b 1
)

copy /Y "%SRC%SKILL.md"           "%SKILL%\SKILL.md"           >nul && echo   OK  SKILL.md (0.4.0)
copy /Y "%SRC%informe_mercado.md" "%SKILL%\informe_mercado.md" >nul && echo   OK  informe_mercado.md
copy /Y "%SRC%CHANGELOG.md"       "%SKILL%\CHANGELOG.md"       >nul && echo   OK  CHANGELOG.md

echo.
echo === git add + commit + push ===
cd /d "%REPO%"
git add ".claude/skills/estudio-mercado/SKILL.md" ".claude/skills/estudio-mercado/informe_mercado.md" ".claude/skills/estudio-mercado/CHANGELOG.md"
git commit -m "fix(estudio-mercado): eliminar el 21%% de IVA inexistente y unificar el modelo de costes (v0.4.0)" -m "AUDITORIA de estudio-mercado contra lo aprendido en importacion-vehiculos v3.7-v3.9.2. Tres hallazgos." -m "CRITICO (IVA fantasma): informe_mercado.md afirmaba '21%% sobre el valor en aduana... como particular se paga'. Falso y invertido: no hay aduana en una compra intracomunitaria, y segun 04-negocio/costes.md el particular sin NIF-IVA NO paga IVA espanol (lo liquida la empresa con NIF-IVA intracomunitario, que luego lo deduce). Tampoco se mencionaba la unica excepcion real (regla 6/6000: <6 meses o <6.000 km). Mismo fallo eliminado de PrecioClienteCalculator.php el 12-sep (alli inflaba el precio ~6.700 EUR en un coche de 32.000). Aqui era peor: un 21%% fantasma hunde el hueco_neto_pct y descarta modelos rentables, que es la decision que esta skill existe para tomar. SKILL.md tambien instruia 'en Flujo A se calcula IVA + IEDMT exacto', lo que habria reintroducido el bug en la skill hermana. datos_mercado.json esta LIMPIO (costes_referencia usa 1129 sin IVA), asi que los veredictos guardados no estan contaminados." -m "COSTES (dos fuentes divergentes): SKILL.md calculaba el hueco con transporte 900 + ausfuhr 114 + ITV 115 = 1.129 (correcto, igual que costes.md y el JSON) mientras el informe usaba 1.500 desglosado como '1.000 transporte + 200 ITV + 300 gestoria', componentes que no existen en ninguna fuente. El total redondo de 1.500 era peticion del usuario (23-ago) y se mantiene, pero ahora se presenta como 1.129 reales + ~371 de colchon, citando costes.md como fuente unica." -m "HORQUILLA: alineado con v3.9.1 de la skill hermana, 'Puesto en Huelva' y 'Ahorro real' se redondean a la centena en vez de al euro, y llevan 'sin IEDMT' cuando el impuesto no esta estimado."
git push

echo.
echo === Hecho ===
echo Revisa "git log -1".
echo.
echo OJO: esto actualiza la copia del REPO. Para que Cowork y Desktop
echo usen la 0.4.0 hay que subir tambien
echo estudio-mercado-v0.4.0.skill.zip donde gestionas las skills de tu
echo cuenta (igual que con importacion-vehiculos).
pause
