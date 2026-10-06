# Política de seguridad

Este es el repositorio del producto open source. Se agradece el reporte de fallas de seguridad: es
la vía por la que se arreglan antes de que alguien las use en contra de una instalación.

## Cómo reportar

**No abras un issue público con el detalle de la falla.** Un issue público queda indexado y describe
el problema a cualquiera que sepa buscar; el issue es el peor lugar para un vector.

Usá el **reporte privado de vulnerabilidades de GitHub**: pestaña **Security** → *Report a
vulnerability*. Es un canal privado entre quien reporta y el mantenedor, y es el que se usa acá.

Si preferís otro canal, abrí un issue **sin detalles técnicos** (por ejemplo: *«tengo un hallazgo de
seguridad, ¿cómo te contacto?»*) y se coordina por ahí.

## Qué incluir

- Qué se puede hacer que no debería poder hacerse (el **efecto**).
- En qué versión o commit lo viste.
- Si es una instalación propia: qué configuración tiene (versión de la imagen, si usa los bots, si
  tiene la marca parametrizada). **No hace falta** mandar credenciales ni datos de tus usuarios.
- Si podés, cómo reproducirlo — **en el canal privado**, no en un issue.

## Qué esperar

- Acuse de recibo y una primera evaluación.
- Si el reporte es válido: el arreglo se publica junto con el deploy de la instalación de
  referencia, y después se documenta el **efecto** (no el vector) en el PR. El crédito del reporte es
  tuyo si lo querés.
- Si el reporte es una configuración insegura y no un bug del producto, se te dice por qué y qué
  cambiar.

## Cómo se escribe acá (resumen)

El repo es público: el código, los PRs y los mensajes de commit quedan para siempre. La regla es que
**el código es público y el razonamiento de ataque no**: se publica qué cambió y cómo se nota desde
afuera, no cómo se explota ni con qué datos. El detalle está en
[`docs/COMUNICACION-REPO-PUBLICO.md`](docs/COMUNICACION-REPO-PUBLICO.md) y lo vigila el job `fugas`
del CI.
