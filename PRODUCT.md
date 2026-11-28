# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

PHP 8 + MySQL/MariaDB en un servidor Plesk de Revelco, en un subdominio propio. Sin frameworks de servidor; páginas PHP con HTML, CSS y JavaScript propios. Elegido por el usuario (opción recomendada) porque Plesk lo trae listo y coincide con el calibrador ya construido.

## Users

- **Corredor de bolsa** de una firma comisionista que participa en la rueda de negocios (primer cliente: Mercado y Bolsa S.A.). Entrena en su propio computador, el mismo que usa en la rueda, para lanzar ofertas más rápido y sin errores. Puede tener uno o varios monitores y distintas escalas de Windows.
- **Administrador (Revelco / Carlos Revelo):** crea usuarios y contraseñas, registra pagos, activa y vence licencias, revisa calibraciones y resultados.
- **Responsable en la firma** (posible): cuando la firma paga un paquete de cupos, quiere ver el avance de sus corredores.

## Product Purpose

Portal de entrenamiento: el corredor inicia sesión, calibra su pantalla, juega la simulación de lanzamiento de ofertas y ve sus resultados. Éxito = el corredor reduce su tiempo de reacción y sus errores, y la firma lo puede comprobar.

## Positioning

La simulación replica la sesión real de oferta pública vista desde el puesto de un corredor, ajustada a la pantalla y la distribución de ventanas de cada corredor gracias a la calibración.

## Operating Context

- El acceso lo entrega el administrador (usuario y contraseña) después del pago; no hay registro abierto.
- Dos formas de venta: la firma paga un paquete de cupos, o el corredor paga individualmente. Pago manual (transferencia o Nequi) que el administrador registra y con eso activa la licencia.
- Licencias por periodo (mensual, trimestral, anual) con fecha de vencimiento.
- Ranking solo dentro de cada firma; no se compite entre firmas.
- Se usa en computadores de escritorio y portátiles con Windows, en Chrome o Edge.

## Capabilities and Constraints

- Calibrador de pantalla existente en `calibrador/` (detección, prueba de ajuste, distribución de ventanas).
- El juego actual sigue el flujo del juego de entrenamiento original: aparición aleatoria del botón LANZAR OFERTA, edición del valor, confirmación, medición de tiempos. La réplica de la grabación real llega después, cuando se entregue el video.
- Pendiente: pasarela de pago en línea, competencia en vivo sincronizada.

## Brand Commitments

Marca Revelco: logo de círculos negros y rojo con la palabra "Revelco". Rojo y negro. Idioma español (Colombia).

## Evidence on Hand

No hay testimonios, cifras ni clientes publicables más allá del primer cliente en negociación. No inventar.

## Product Principles

1. La velocidad del corredor es lo que se mide: nada en la interfaz del juego debe distraer o agregar latencia.
2. Lo que ve el corredor en el simulador debe coincidir con su pantalla real.
3. El acceso es pago y controlado: cada cuenta tiene dueño, firma y fecha de vencimiento claros.
4. Los resultados son privados dentro de la firma.
