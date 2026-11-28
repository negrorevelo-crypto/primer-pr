---
version: 1
slug: "portal"
primary_target: "portal"
related_targets: []
---

# Portal de entrenamiento de corredores

Scope: portal autenticado completo (inicio de sesión, panel del corredor, calibración, juego, resultados, administración). Visitor mode: Operate.

Audience: corredores de firmas comisionistas que entrenan el lanzamiento de ofertas; administrador Revelco que gestiona usuarios, licencias y pagos.
Task: entrar, calibrar, jugar, revisar tiempos; el administrador crea cuentas y controla vencimientos.
Constraints: PHP + MySQL en Plesk; Windows + Chrome/Edge; ranking solo dentro de la firma; rojo y negro de Revelco.

## Direction contract

THESIS: El portal vive el ritual de apertura de la rueda: cada pantalla está "antes, durante o después del campanazo". Rechaza el tablero SaaS de tarjetas grises con un acento rojo suelto.
OWN-WORLD: Riel lateral negro cálido con la marca; fondo claro de oficina tibio; rojo Revelco solo para "rueda abierta" y la acción principal; latón para récords (rombo ◆). Cifras tabulares grandes en Segoe UI; filetes finos; controles web estándar.
STORY: El corredor ve si su licencia está abierta, juega, ve su tiempo contra su mejor marca y su lugar en la firma, y vuelve a jugar. El administrador ve quién vence y activa cuentas con un pago.
FIRST VIEWPORT: Panel: riel negro a la izquierda (Inicio, Jugar, Resultados, Calibrar); arriba la banda de rueda con el periodo de licencia; debajo, a la izquierda el botón rojo grande "Entrar a la rueda" con el mejor tiempo en cifras grandes y el rombo latón; a la derecha historial con tendencia y ranking de la firma.
FORM: Campanazo de apertura, posición 4 de la lista propia; seed:66dd2747172d96a3. Movimiento firma: la banda de rueda PRE-APERTURA · RUEDA ABIERTA · CIERRE que se inunda de rojo en 200 ms al sonar la campana dentro del juego, y que en el panel mide el periodo de licencia.
FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance

Unresolved: logo real de Revelco en vector (se usa una marca aproximada hasta recibirlo).
