# Qué cambia

<!-- 1-3 líneas: qué problema resuelve y cómo se nota desde afuera. -->

## Verificación

<!-- Qué corriste y qué devolvió. Ej.: php artisan test → 255 passed / 844 assertions -->

## Antes de crear el PR (el repo es PÚBLICO: lo que escribís queda indexado para siempre)

- [ ] El título y el cuerpo dicen **qué cambia**, no **cómo se explota**: sin la ruta exacta, sin el id
      de ejemplo, sin el paso a paso. Si hay un vector, va al aviso privado (`SECURITY.md`).
- [ ] Sin datos de una instalación (IP, puerto, host, rutas del server) ni de personas (nombres,
      emails), ni acá ni en los mensajes de commit.
- [ ] Sin deuda interna ni el mapa de lo que falta: eso vive en el canal privado, no en la vitrina.
- [ ] Si es un fix de seguridad: el deploy va en la **misma ventana** que el merge.
- [ ] El cuerpo entra en ~15 líneas. Lo que sobra, sobra.

## Si tocás datos de grupo o un camino sin sesión

<!-- Recordatorio corto. El detalle técnico completo vive en el canal privado. -->

- [ ] El aislamiento entre grupos está cubierto por tests, y los corrí.
- [ ] Si el camino corre sin usuario logueado: verifiqué que **falla cerrado**, no sólo que no rompe.
