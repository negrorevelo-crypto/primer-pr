---
name: Revelco · Portal de entrenamiento
description: Portal del corredor para entrenar el lanzamiento de ofertas en la rueda, vivido como el ritual del campanazo de apertura.
colors:
  tinta: "#16140f"
  tinta-2: "#23201a"
  tinta-3: "#3a362e"
  sobre-tinta: "#f3f1ec"
  sobre-tinta-2: "#aaa498"
  suelo: "#f3f1ec"
  papel: "#ffffff"
  papel-2: "#faf9f6"
  propio: "#ece7dc"
  linea: "#e3dfd6"
  linea-fuerte: "#cbc5b8"
  texto: "#1d1b17"
  texto-2: "#5c574d"
  texto-3: "#7a7468"
  rojo: "#d2161e"
  rojo-hondo: "#ad1016"
  rojo-tinte: "#fbeaea"
  laton: "#8a6620"
  laton-vivo: "#b48a32"
  verde: "#1d7039"
  verde-tinte: "#e6f3ea"
  ambar: "#855500"
  ambar-tinte: "#fbf1d9"
  escena: "#0f0e0b"
typography:
  display:
    fontFamily: "\"Segoe UI Variable Display\", \"Segoe UI\", system-ui, -apple-system, Roboto, sans-serif"
    fontSize: "56px"
    fontWeight: 650
    lineHeight: 1
    letterSpacing: "-0.02em"
    fontFeature: "\"tnum\""
  headline:
    fontFamily: "\"Segoe UI Variable Text\", \"Segoe UI\", system-ui, -apple-system, Roboto, \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "26px"
    fontWeight: 650
    lineHeight: 1.2
    letterSpacing: "-0.01em"
  title:
    fontFamily: "\"Segoe UI Variable Text\", \"Segoe UI\", system-ui, -apple-system, Roboto, \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "17px"
    fontWeight: 650
    lineHeight: 1.2
    letterSpacing: "-0.01em"
  body:
    fontFamily: "\"Segoe UI Variable Text\", \"Segoe UI\", system-ui, -apple-system, Roboto, \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "15px"
    fontWeight: 400
    lineHeight: 1.5
    fontFeature: "\"tnum\""
  label:
    fontFamily: "\"Segoe UI Variable Text\", \"Segoe UI\", system-ui, -apple-system, Roboto, \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "13px"
    fontWeight: 600
    lineHeight: 1.3
  fase:
    fontFamily: "\"Segoe UI Variable Text\", \"Segoe UI\", system-ui, -apple-system, Roboto, \"Helvetica Neue\", Arial, sans-serif"
    fontSize: "12.5px"
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: "0.02em"
rounded:
  pista: "3px"
  radio: "6px"
  tarjeta: "8px"
  pastilla: "999px"
spacing:
  xs: "8px"
  sm: "14px"
  md: "18px"
  lg: "22px"
  xl: "28px"
  obra: "36px"
  riel: "232px"
components:
  button-primary:
    backgroundColor: "{colors.rojo}"
    textColor: "{colors.papel}"
    typography: "{typography.label}"
    rounded: "{rounded.radio}"
    padding: "8px 16px"
    height: "38px"
  button-primary-hover:
    backgroundColor: "{colors.rojo-hondo}"
    textColor: "{colors.papel}"
  button-primary-large:
    backgroundColor: "{colors.rojo}"
    textColor: "{colors.papel}"
    rounded: "{rounded.radio}"
    padding: "14px 26px"
    height: "56px"
  button-secondary:
    backgroundColor: "{colors.papel}"
    textColor: "{colors.texto}"
    rounded: "{rounded.radio}"
    padding: "8px 16px"
    height: "38px"
  button-secondary-hover:
    backgroundColor: "{colors.papel-2}"
    textColor: "{colors.texto}"
  button-ink:
    backgroundColor: "{colors.tinta}"
    textColor: "{colors.sobre-tinta}"
    rounded: "{rounded.radio}"
    padding: "8px 16px"
    height: "38px"
  button-ink-hover:
    backgroundColor: "{colors.tinta-3}"
    textColor: "{colors.papel}"
  button-quiet:
    backgroundColor: "transparent"
    textColor: "{colors.texto-2}"
    rounded: "{rounded.radio}"
    padding: "5px 11px"
    height: "30px"
  input:
    backgroundColor: "{colors.papel}"
    textColor: "{colors.texto}"
    typography: "{typography.body}"
    rounded: "{rounded.radio}"
    padding: "8px 11px"
    height: "38px"
  panel:
    backgroundColor: "{colors.papel}"
    textColor: "{colors.texto}"
    rounded: "{rounded.radio}"
    padding: "18px"
  rail:
    backgroundColor: "{colors.tinta}"
    textColor: "{colors.sobre-tinta-2}"
    width: "{spacing.riel}"
    padding: "20px 14px 16px"
  rail-item-active:
    backgroundColor: "{colors.tinta-3}"
    textColor: "{colors.papel}"
    rounded: "{rounded.radio}"
    padding: "9px 10px"
  chip-active:
    backgroundColor: "{colors.verde-tinte}"
    textColor: "{colors.verde}"
    rounded: "{rounded.pastilla}"
    padding: "2px 9px"
  chip-expiring:
    backgroundColor: "{colors.ambar-tinte}"
    textColor: "{colors.ambar}"
    rounded: "{rounded.pastilla}"
    padding: "2px 9px"
  chip-neutral:
    backgroundColor: "{colors.suelo}"
    textColor: "{colors.texto-2}"
    rounded: "{rounded.pastilla}"
    padding: "2px 9px"
  wheel-band:
    backgroundColor: "{colors.papel}"
    textColor: "{colors.texto-3}"
    typography: "{typography.fase}"
    rounded: "{rounded.radio}"
    padding: "12px 16px"
  wheel-band-track:
    backgroundColor: "{colors.suelo}"
    rounded: "{rounded.pista}"
    height: "6px"
  wheel-band-fill-open:
    backgroundColor: "{colors.rojo}"
---

# Design System: Revelco · Portal de entrenamiento

## Overview

**Creative North Star: "El campanazo de apertura"**

Cada pantalla del portal está antes, durante o después del campanazo. El sistema no es un tablero de tarjetas grises con un acento rojo suelto: es el puesto de un corredor en la rueda. Un riel lateral negro cálido sostiene la marca y la navegación; el área de trabajo es un suelo de oficina tibio con paneles blancos de filete fino; el rojo Revelco aparece cuando la rueda está abierta y en la única acción que importa en la pantalla. El latón, en forma de rombo, marca los récords y nada más.

La densidad es de oficina: cifras tabulares grandes donde hay un tiempo que leer, tablas compactas donde hay cuentas que administrar, controles web estándar sin disfraz. La interfaz habla español de Colombia y trata al usuario de "usted", con frases cortas y formales ("Para entrar a la rueda necesita una licencia vigente.").

El movimiento firma es la banda de rueda: PRE-APERTURA · RUEDA ABIERTA · CIERRE. En el juego se inunda de rojo al sonar la campana; en el panel y en la administración mide el periodo de la licencia. Es el único dispositivo visual propio del mundo y se repite en todas las superficies, a tamaño completo o en versión mini dentro de las tablas.

**Key Characteristics:**
- Riel negro cálido de 232px a la izquierda; suelo claro tibio a la derecha.
- Rojo escaso y con significado: rueda abierta, una acción principal por pantalla, foco de teclado.
- Latón solo para récords, acompañado del rombo.
- Cifras tabulares grandes en Segoe UI Variable Display.
- Filetes de 1px en lugar de sombras; esquinas suaves de 6px.
- La banda de rueda de tres fases como firma en todas las superficies.

## Colors

Una paleta de oficina tibia: negros y cremas con un rojo de marca que se reserva y un latón que premia.

### Primary
- **Rojo Revelco** (`rojo`): el rojo del logo. Rueda abierta (fase activa y relleno de la banda, barra de título de la ventana activa en el simulador), el botón principal de la pantalla, el anillo de foco y la selección de texto.
- **Rojo hondo** (`rojo-hondo`): hover del botón principal, texto de la fase "Rueda abierta" sobre fondo claro, texto de avisos de error y hover de enlaces.
- **Tinte rojo** (`rojo-tinte`): halo de 4px del punto de fase abierta, halo de foco de los campos y fondo de los avisos de error.

### Secondary
- **Latón** (`laton`): texto del indicador "Récord personal" y del primer puesto del ranking de la firma.
- **Latón vivo** (`laton-vivo`): relleno del rombo de récord y del punto de récord en la gráfica de tiempos.

### Tertiary
- **Verde licencia** (`verde`, `verde-tinte`): chip "Activa" y avisos de confirmación.
- **Ámbar vencimiento** (`ambar`, `ambar-tinte`): chip "Vence en N d" / "Por vencer".

### Neutral
- **Tinta** (`tinta`): el riel, el panel oscuro del inicio de sesión, el botón de tinta, el segmento seleccionado, el número de paso y la fase de cierre.
- **Tinta 2 / Tinta 3** (`tinta-2`, `tinta-3`): hover y elemento activo del riel, banda sobre fondo oscuro, filete superior del bloque de usuario.
- **Sobre tinta** (`sobre-tinta`, `sobre-tinta-2`): texto principal y secundario sobre el riel y la escena del juego.
- **Suelo** (`suelo`): fondo de la obra; pista vacía de la banda; chip neutro.
- **Papel** (`papel`, `papel-2`): paneles, campos y botones secundarios; `papel-2` para encabezados de tabla y hover de filas.
- **Propio** (`propio`): fila del usuario actual en tablas y ranking, junto con peso 650.
- **Línea** (`linea`, `linea-fuerte`): filetes de paneles y tablas; bordes de campos y botones secundarios.
- **Texto** (`texto`, `texto-2`, `texto-3`): tinta del cuerpo y de los enlaces; texto secundario; marcas de eje, ayudas y fases inactivas.
- **Escena** (`escena`): fondo del juego a pantalla completa, más oscuro que la tinta para que la ventana simulada flote.

### Named Rules
**La regla de la rueda abierta.** El rojo significa "rueda abierta" o "esta es la acción". Cada pantalla tiene como máximo un botón rojo; las demás acciones son secundarias, de tinta o sutiles. El rojo no se usa para resaltar la fila propia, para estados de navegación ni para decorar.

**La regla del latón.** El latón y el rombo solo marcan récords: la mejor marca personal, el punto más rápido de la gráfica, el primer puesto de la firma. Nunca un aviso, un enlace ni un encabezado.

**La regla de la tinta en los enlaces.** Los enlaces son tinta (`texto`) con subrayado fino en `linea-fuerte`; al pasar el cursor el texto pasa a rojo hondo y el subrayado toma su color.

## Typography

**Display Font:** Segoe UI Variable Display (con Segoe UI, system-ui, Roboto)
**Body Font:** Segoe UI Variable Text (con Segoe UI, system-ui, Helvetica Neue, Arial)
**Label/Mono Font:** ui-monospace / Consolas, solo para claves temporales mostradas y volcados técnicos.

**Character:** La letra de la pantalla del corredor. Los corredores trabajan en Windows con Chrome o Edge; Segoe UI es la letra de su puesto en la rueda, y la versión Display da a las cifras el peso de un marcador. Todo el portal usa cifras tabulares (`font-variant-numeric: tabular-nums` en el `body`) para que los tiempos no bailen.

### Hierarchy
- **Display** (650, 56px; 44px bajo 520px; 64px en la tarjeta de resultado del juego; interlineado 1; -0.02em): la mejor marca y el tiempo de la partida. El reloj del juego usa la misma familia a 26px.
- **Headline** (650, 26px; 22px bajo 860px; 1.2): el título de cada página. El lema del inicio de sesión sube a 40px (28px en móvil) con -0.025em.
- **Title** (650, 17px, 1.2): encabezados de panel ("Tiempo por partida", "Ranking de la firma").
- **Body** (400, 15px, 1.5): texto corriente y campos. Tablas a 14px; textos de apoyo a 13–13.5px.
- **Label** (600, 13px): etiquetas de campo, encabezados de tabla (12.5px) y chips (12.5px).
- **Fase** (600, 12.5px, 0.02em, MAYÚSCULAS; 11px sin espaciado bajo 860px): exclusivamente los nombres de fase de la banda de rueda.

### Named Rules
**La regla de la cifra.** Todo tiempo, conteo o puesto que el usuario vino a ver se escribe grande, en peso 650 y con cifras tabulares; la unidad ("segundos") va al lado en texto secundario, nunca dentro de la cifra.

**La regla de las mayúsculas de fase.** Las mayúsculas espaciadas pertenecen a la banda de rueda. No se usan como rótulo sobre títulos ni como etiqueta de sección. La única excepción es el botón LANZAR OFERTA del simulador, que replica el botón real de la rueda.

## Layout

Rejilla de dos columnas: riel fijo de 232px (sticky, alto completo) y obra de hasta 1240px con relleno de 28px arriba y 36px a los lados. El fondo del `body` pinta la columna del riel en tinta para que el negro llegue al borde inferior. La cabecera de página alinea título y subtítulo a la izquierda y las acciones a la derecha, al pie.

El panel del corredor es una rejilla 1fr / 1.25fr con 18px de separación: a la izquierda el puesto (mejor marca, botón grande, tres datos), a la derecha historial y ranking apilados. Bajo 1000px pasa a una columna.

El ritmo de espacio se mueve en pasos de 8, 14, 18, 22 y 28px: 8px entre botones, 14px en rejillas de formulario, 18px dentro de paneles y entre paneles, 22px antes y después de la cabecera.

Bajo 860px el riel se convierte en una franja superior que se envuelve: marca a la izquierda, acciones de usuario a la derecha (solo iconos), navegación en una segunda fila; la obra usa 16px de margen lateral. El inicio de sesión es una pantalla partida 1.05fr / 1fr (panel de tinta con marca, lema y banda; formulario centrado de 380px) que se apila en móvil. El juego ocupa toda la ventana: barra superior de tinta con la banda y el reloj, y debajo la escena oscura con el lienzo calibrado centrado.

## Elevation & Depth

El sistema es plano. La profundidad se construye con tono (riel de tinta, suelo tibio, paneles blancos) y con filetes de 1px en `linea`. Los paneles no llevan sombra. La sombra aparece solo en lo que flota sobre la escena del juego.

### Shadow Vocabulary
- **Flotante del juego** (`box-shadow: 0 24px 64px -16px rgba(0,0,0,.6)`): la tarjeta de resultado sobre la escena oscurecida.
- **Ventana activa** (`box-shadow: 0 0 0 1px var(--rojo), 0 18px 48px -12px rgba(0,0,0,.7)`): la ventana simulada que se activa con el campanazo.
- **Halo de fase** (`box-shadow: 0 0 0 4px var(--rojo-tinte)`): el punto de la fase "Rueda abierta".
- **Halo de foco** (`box-shadow: 0 0 0 3px var(--rojo-tinte)`): campos enfocados, con borde rojo.

### Named Rules
**La regla del filete.** Los contenedores del portal se separan con un filete de 1px y un cambio de tono, nunca con sombra. Si un panel parece necesitar sombra para destacarse, le sobra un vecino.

## Shapes

Esquinas suaves y uniformes de 6px en paneles, botones, campos, avisos, la banda y los segmentos. Las pistas de la banda usan 3px; la tarjeta de resultado del juego, 8px; los chips y el aviso de salida anticipada son pastillas completas. Los puntos de fase son círculos de 8px y el número de paso de calibración un círculo de 24px. El rombo de récord (un cuadrado girado, relleno) es la única silueta propia además del logo de círculos.

Los iconos son SVG en línea de 24×24 con trazo de 1.75, extremos redondeados, dibujados a 18px (17px dentro de botones) y en `currentColor`. El rombo es el único icono relleno.

La marca Revelco es un SVG en línea (`marca_svg()` en `app/vista.php`) redibujado del PNG oficial: dos círculos grises `#8a8a8a` al fondo, una cápsula diagonal roja `#e31e24` y dos círculos negros `#231f20` con anillo gris, seguido de la palabra "Revelco" en peso 300. Se usa con sus colores originales en cualquier fondo: el anillo gris separa los círculos negros del riel oscuro.

## Components

### Buttons
Controles web estándar, firmes y sin disfraz.
- **Shape:** esquinas de 6px, alto mínimo 38px, texto 600 a 14px, icono opcional a la izquierda con 8px de separación.
- **Principal (rojo):** fondo rojo Revelco, texto blanco. Una por pantalla. Hover a rojo hondo. La variante grande (56px, 17px, relleno 14px 26px) es para "Entrar a la rueda", "Iniciar", "Jugar otra vez" y el acceso.
- **Secundario:** fondo papel, borde `linea-fuerte`, texto tinta; hover a `papel-2` con borde `texto-3`.
- **Tinta:** fondo tinta, texto crema; para "Guardar cambios" cuando la acción principal de la pantalla ya es otra o el formulario edita en vez de crear.
- **Sutil:** sin fondo ni borde, texto secundario; hover con fondo `suelo`. La variante chica (30px, 13px) va en cabeceras de panel ("Ver todo").
- **Estados:** `:active` baja 1px; deshabilitado a 50% de opacidad; foco con contorno rojo de 2px desplazado 2px.

### Chips
- **Estilo:** pastilla, 12.5px 600, relleno 2px 9px, sin borde.
- **Estados de licencia:** verde "Activa", ámbar "Vence en N d", neutro sobre `suelo` para "Sin licencia" y "Vencida".

### Cards / Containers
- **Corner Style:** 6px.
- **Background:** papel sobre suelo.
- **Shadow Strategy:** ninguna (ver Elevation & Depth).
- **Border:** 1px `linea`; cabecera y pie del panel separados por el mismo filete.
- **Internal Padding:** 18px en el cuerpo, 14px 18px en la cabecera; el puesto del corredor usa 26px 24px. Los datos agrupados se presentan en una rejilla con separadores de 1px (fondo `linea` asomando entre celdas de papel).

### Inputs / Fields
- **Style:** papel, borde 1px `linea-fuerte`, 6px, alto 38px, texto 15px. Etiqueta 13px 600 en texto secundario encima; ayuda 12.5px en `texto-3` debajo.
- **Focus:** borde rojo más halo de 3px en tinte rojo.
- **Error:** borde rojo (`aria-invalid`). Casillas y radios toman el rojo como `accent-color`. Las opciones de radio grandes son filas con filete que al marcarse toman borde de tinta y fondo `papel-2`.

### Navigation
- **Riel:** tinta, 232px, marca arriba, navegación con icono y texto a 14.5px en `sobre-tinta-2`, grupo "Administración" como texto de 12px sin mayúsculas. Hover a `tinta-2`; página actual en `tinta-3` con texto e icono crema (sin rojo). Bloque de usuario al pie, separado por un filete de `tinta-3`, con nombre, firma y acciones (Contraseña, Salir).
- **Segmentos:** grupo de enlaces con borde común; el seleccionado en tinta con texto crema.
- **Móvil:** franja superior envolvente; el grupo y los datos del usuario se ocultan.

### Tables
- Texto 14px; encabezados 12.5px 600 en texto secundario sobre `papel-2`; filas separadas por filetes; hover a `papel-2`; columnas numéricas alineadas a la derecha. La fila propia usa fondo `propio` y peso 650. El nombre enlazado de una fila va en tinta 600 y se subraya en rojo hondo al pasar el cursor.

### Avisos
- Bandas de 6px con icono a la izquierda, 14px. Confirmación en verde, error en tinte rojo con texto rojo hondo, información en papel con borde `linea-fuerte` y texto tinta.

### Banda de rueda (componente firma)
La línea de tiempo de tres fases que da nombre al mundo.
- **Estructura:** fila de tres fases (izquierda, centro, derecha) en MAYÚSCULAS 12.5px 600, cada una con un punto de 8px; debajo una pista de 6px sobre `suelo` con un relleno que crece de izquierda a derecha (`transform: scaleX`); opcionalmente un pie con dos datos ("Desde 14 nov 2026" / "Vence 14 dic 2026 · quedan 18 días"), con los valores en tinta 600.
- **Pre-apertura:** punto en texto secundario que late (opacidad a 35%, 1.6s); relleno gris.
- **Rueda abierta:** fase en rojo hondo, punto rojo con halo de 4px en tinte rojo, relleno rojo.
- **Cierre:** punto y relleno en tinta (crema sobre fondo oscuro).
- **Sobre tinta:** en el acceso y en la barra del juego la banda va sobre `tinta-2` con borde `tinta-3` y pista `tinta-3`; en el juego la fase abierta sube a `#ff6b70` para leerse sobre negro.
- **Movimiento:** relleno y color en 220ms con `cubic-bezier(.16, 1, .3, 1)`; con movimiento reducido todo pasa a 1ms.
- **Mini banda:** en tablas de administración, una pista de 72×5px con la fecha de vencimiento al lado; misma lógica de color por fase.

### Récord
- Rombo latón vivo de 13px seguido de "Récord personal" en latón 600 a 13.5px, junto a la cifra de la mejor marca. En la gráfica, el punto de récord se rellena en latón vivo.

### Gráfica de tiempos
- Línea de tinta de 2px con puntos huecos (papel con trazo de tinta), ejes en `linea`, etiquetas de 12px en `texto-3` fuera del dibujo. Más abajo es más rápido. Sin relleno de área ni color de serie adicional.

### Simulador
- Escena `escena` con un lienzo calibrado y ventanas simuladas en tonos de tinta. Al campanazo, la ventana de la oferta se activa: borde y barra de título en rojo, sombra flotante. El botón LANZAR OFERTA ocupa el ancho (110px de alto, 36px). Un anticipo muestra una pastilla roja breve al pie ("Anticipado: espere el campanazo") durante 1.2s.

## Do's and Don'ts

### Do:
- **Do** poner la banda de rueda en la cabecera de cada superficie donde haya un periodo o una sesión que medir: licencia en el panel, sesión en el juego, vencimiento en las tablas.
- **Do** dejar un solo botón rojo por pantalla y resolver las demás acciones con secundario, tinta o sutil.
- **Do** escribir tiempos y conteos con cifras tabulares grandes (650, -0.02em) y la unidad aparte en texto secundario.
- **Do** separar contenedores con filetes de 1px en `linea` y cambios de tono; reservar la sombra para lo que flota sobre la escena del juego.
- **Do** marcar la fila propia con fondo `propio` y peso 650.
- **Do** escribir en español de Colombia, en "usted", con frases cortas y formales.
- **Do** usar iconos SVG en línea de trazo 1.75 en `currentColor`.

### Don't:
- **Don't** usar rojo para la navegación activa, la fila propia, enlaces, rótulos o decoración; el rojo es rueda abierta, acción principal y foco.
- **Don't** usar latón ni el rombo fuera de los récords.
- **Don't** poner rótulos en MAYÚSCULAS espaciadas encima de los títulos; las mayúsculas son de la banda de rueda.
- **Don't** convertir el portal en un tablero SaaS de tarjetas grises con sombra y un acento rojo suelto.
- **Don't** poner sombras en paneles, tablas o botones.
- **Don't** recolorear ni estilizar la marca; se usa siempre con sus colores originales.
- **Don't** agregar animación al juego más allá de la banda y la activación de la ventana; nada debe distraer ni sumar latencia al corredor.
