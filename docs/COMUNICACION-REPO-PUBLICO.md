# Comunicación en el repo público

El repo de la app es **público** (es el producto open source). Eso significa que **todo lo que se
escribe acá queda indexado, atribuido y disponible para siempre**: los archivos, y también el texto
libre de GitHub —el título y el cuerpo de un PR, los mensajes de commit, el nombre de la rama, los
issues y los comentarios.

Esta política define **qué se puede escribir y dónde**. La regla que la resume:

> **El código es público; el razonamiento de ataque, no.**
> Publicamos *qué cambió y cómo se nota desde afuera*. No publicamos *cómo se explota*, *a quién
> afecta* ni *con qué datos*.

## 1. Va al repo público / va al canal privado

| Contenido | Dónde va |
|---|---|
| Qué cambia, por qué, cómo lo probé, qué tests cubren | **PR público** (es la evidencia del cambio) |
| El **efecto** de un fix de seguridad, en términos de comportamiento | **PR público** — «el bot ahora devuelve los datos del grupo del chat y nada más» |
| El **vector** (la ruta exacta, el payload, el id de ejemplo, el paso a paso de reproducción) y el **expediente del hallazgo** (cómo se descubrió, el impacto, qué quedó sin cubrir) | **Canal privado del proyecto.** El PR público dice el efecto |
| Datos de una instalación: IPs, puertos, hosts, rutas de servidor, nombres de usuario, emails | **Ninguno de los dos: no se escriben en un repo** (van a KeePass) |
| Datos de una persona (usuarios, clientes, el mantenedor) | **Canal privado.** Un repo público no es un lugar para el dato de nadie |
| Capturas/logs con datos reales de usuarios | **Canal privado** (y anonimizados antes de mostrarlos) |
| Números de PR, SHAs, "esto está roto", deuda pendiente | **Board** (privado), no el PR público |

**Por qué importa la diferencia:** un issue o un PR público que dice «los datos de un grupo se ven
desde otro» le avisa a cualquiera que la instalación tiene un patrón de autorización débil, y le da
el punto de entrada. Publicado *después* del deploy, en cambio, es una nota de release.

## 2. El momento manda: ventana de exposición

Un fix de seguridad se publica **cuando el servidor ya lo tiene**. Entre el merge (público, con su
diff y su mensaje) y el deploy hay una ventana en la que la vulnerabilidad está documentada y la
instalación todavía no está arreglada.

Orden correcto:

1. Diagnóstico y vector → **privado** (aviso de seguridad / board).
2. Fix y tests → rama + PR, con redacción de **efecto**.
3. **Deploy a producción** y verificación en la instancia.
4. Recién ahí el PR queda como documentación pública. Si el deploy va después del merge, **el merge
   y el deploy van en la misma ventana**: minutos, no días.

Medido en el único hotfix de seguridad de esta serie (2026-09-29): PR publicado 03:08:02Z, fix vivo
en producción 03:14:35Z → **ventana de 6 minutos**. Ése es el estándar; se sostiene así porque el
deploy se dispara enseguida. Si un merge tiene que esperar una ventana de deploy larga, el orden se
invierte: primero la imagen y la ventana, después el merge.

## 3. Redacción: los títulos y los mensajes

- **Título del PR / mensaje de commit**: describen el **efecto** y el **área**, no el vector.
  - ✅ `fix(seguridad): el bot devuelve sólo los datos del grupo del chat`
  - ✅ `fix(seguridad): aislar las consultas por grupo en el panel`
  - ✗ `hotfix(seguridad): IDOR en el callback de riego` — nombra la clase de vulnerabilidad y el
    punto exacto.
- **Prefijo de tipo** (`fix`, `feat`, `chore`…) y el área entre paréntesis: eso sí, ordena el
  historial.
- El **cuerpo** del PR responde: qué cambia, cómo se nota desde afuera, qué tests lo cubren, qué
  deuda deja. **No** incluye: reproducción paso a paso del ataque, la ruta con el id de ejemplo, ni
  el nombre de la persona que lo reportó.
- **Nombres de rama**: por el efecto, nunca por el vector. `hotfix/bot-aislamiento-tenant` ✅ /
  `hotfix/idor-bot` ✗. La rama se publica al primer push y **el nombre queda** en el remoto y en los
  refs de PR: es de las cosas que no se pueden des-publicar.

## 3bis. La voz: el criterio se publica, el caso no

GitHub es la superficie **más lejana al usuario final y la más cercana a otro que lee código**. Ahí no
se acompaña a una persona: se le describe un cambio a un par. Por eso el registro es impersonal, y el
voseo se queda en el blog.

**Un texto que se lee con criterio hace cuatro cosas:**

- **Enuncia el criterio**, no el arreglo. «Si el proceso no trae su grupo, no lee ni escribe» es una
  decisión que se puede discutir; «los caminos quedan acotados» sólo reporta.
- **Dice el estado real**, incluido el propio error, sin disculparse. El que sabe no necesita
  justificarse.
- **Da el número.** Cada afirmación viaja con su medición.
- **Escribe para quien va a mantener el código**, no para el tablero.

**Y tres que hace de más:**

- **Pide permiso.** «Decisión de <Nombre>» donde va un criterio de producto.
- **Explica el cómo.** La ruta exacta, el id, el paso a paso.
- **Envuelve en ceremonia.** El checklist de seis secciones, el emoji de ánimo, la mayúscula que grita.

| Nuestra voz | Lo que evitamos |
|---|---|
| «0 llamadores, verificado en `app/`, `routes/` y `tests/`» | «se verificó exhaustivamente» |
| «borra X», «reemplaza Y» | «fue realizada la remoción» |
| dice el error: «nunca se había corrido completo» | lo esconde: «se optimizó el script» |
| «cada consulta se acota al grupo del chat» | «con el id de otro grupo devolvía sus fotos» |

### La regla que ordena todo lo demás

**El criterio se publica; el caso va al canal privado.** «Cada consulta se acota al grupo del chat» es
publicable; «con el id de otro grupo devolvía sus fotos» no. Es la misma línea del efecto y el vector,
aplicada a la voz: el diseño es público, el incidente no.

Esa distinción resuelve los casos sin necesidad de listar prohibiciones:

- **En el título y el cuerpo va qué cambia y con qué criterio.** No la reproducción, ni el id de
  ejemplo, ni la ruta exacta, ni la enumeración de dónde fallaba, ni el efecto de un incidente
  descrito como tal. El expediente del hallazgo (vector, reproducción, impacto, cómo se descubrió, qué
  quedó sin cubrir) vive en el canal privado.
- **Tampoco va el mapa de riesgos** —qué modelos no tenían filtro, qué archivo hacía qué—: es,
  literalmente, la lista de dónde mirar. Ni la deuda interna: decir lo que falta le señala al lector
  dónde no hay control.
- **Los códigos del tablero no salen del tablero.** `T3.3`, `WS2`, `criterio 5` no significan nada
  fuera del board, y quien lee no lo tiene abierto.
- **La persona se acredita por rol**, no por nombre: «decisión de producto», «reportado por un
  usuario».
- **La comunicación técnica es impersonal.** El posesivo de producto («tus datos», «tu indoor») es copy
  de la landing.
- **El emoji es un veredicto, no un ánimo.** Ninguno en el título; en el cuerpo, uno por bloque y sólo
  si clasifica (⚠ / ❌), nunca decorativo (🔴 🐛 🚧 ★).
- **La primera persona respalda una verificación** («se probó en un clone limpio: 48 s») y nada más.
- **Todo absoluto lleva número, alcance y fecha.** Sin eso es opinión.
- **Un PR de seguridad no tiene humor.** Ni autoburla, ni ironía, ni «llegué en 48 segundos».

### Dos límites que conviene tener escritos

**El diff es público.** Nadie esconde el código escribiendo menos en el cuerpo: los nombres de clases,
los archivos y las migraciones están en el diff igual. Lo que se reduce es la **interpretación** —qué
importa, dónde faltaba algo, cómo se explota—, y eso es justo lo que un cuerpo largo regala.

**El peor lugar para equivocarse es el mensaje de commit.** No se edita: queda indexado y viaja en cada
`git clone`. Medido: un PR que se limpió a tiempo dejó igual todo el mapa en el mensaje de su commit.
Al limpiar un PR, revisar el mensaje es la mitad del trabajo y la única sin vuelta atrás.

### Lo que no se toca (para no acartonar)

El **voseo** de blog/IG/README; los **absolutos medidos** (son la credibilidad); la **primera persona
honesta** de un commit (es autoría, no diario); los **emojis de estado** en tablas y checklists;
`bloqueante`/`P0` (es la severidad real del CI); los **CVE** en bumps de dependencias.

### Cómo se hace cumplir el registro

`scripts/guard-comunicacion.py` reporta el registro como **aviso**, nunca como bloqueo: es voz, no
dato, y un gate que frena por estilo termina desactivado. Señales que avisa:

| Señal | Ejemplo real |
|---|---|
| código del tablero (`T#.#`, `WS#`, `criterio #`, `D#`) | `T10.6`, `criterio 2` |
| referencia al proceso (`board`, `kanban`, `épica`, `sprint`, `backlog`) | `medido en …-flujo-gitflow (board)` |
| atribución a una persona **por la forma**, no por el nombre | `decisión de <Nombre>`, `corregido por <Nombre>` |
| posesivo de producto | `tus datos`, `tu indoor` |
| sólo en títulos y ramas: primera persona y emoji decorativo | `medí`, `agregué`, `🔴` |

`tarjeta` queda fuera del patrón (matchea la tarjeta del dashboard, vocabulario del producto), y la
atribución se detecta por la forma y no por el nombre: así no hay un solo nombre escrito en el guard y
cubre a cualquiera.

Lo que sí **bloquea** es el dato: el par explotable (mecanismo + vector concreto), las IPs, los emails
y los valores de la instalación. Y cuenta también el vector descrito **sin nombrar la clase**: el autor
cuidadoso no escribe la sigla, así que exigirla castigaría al que escribe bien.

## 4. GitHub no olvida: qué es reversible y qué no

| Superficie | ¿Se puede limpiar? |
|---|---|
| Título y cuerpo de un PR | ✅ se editan (`gh pr edit`) → sale de la vista y de los scrapers |
| Un mensaje de commit | ⚠️ se corrige con un **commit de corrección**; el original queda en la historia |
| Un **SHA** ya pusheado | ❌ no: sigue alcanzable por API/UI aunque no esté en ninguna rama |
| Un nombre de rama | ⚠️ se borra la rama, pero el nombre queda en los PRs que la usaron |
| Un secreto commiteado | ❌ hay que **rotarlo**; sacarlo de la historia pide `git-filter-repo` + force push |
| Un dato de una persona en la historia | ❌ ticket de *sensitive data removal* a GitHub Support |

Consecuencia para escribir un PR: **una vez que tocás «Create», el texto ya está publicado.** No hay
"lo edito después". El gate de comunicación bloquea lo que sí se puede arreglar antes de mergear, y
reporta como deuda lo que ya quedó.

## 5. Las 3 formas en que este repo se filtró a sí mismo (2026-09)

Ninguna de las tres fue un archivo con una contraseña. Las tres fueron **texto libre**.

1. **El guard que se publicó a sí mismo.** El primer guard de fugas listaba, en su propio código,
   los valores que protegía: la IP del servidor, el puerto de administración, la ruta home, el host
   del homelab y el usuario y el email del mantenedor. El guard era la fuga. → *Regla actual: los
   valores protegidos van **hasheados** (SHA-256) con su descripción; el valor no se escribe nunca,
   ni en el código ni en el log.*
2. **El cuerpo del PR que apuntaba a los datos.** Dos PRs explicaron en su cuerpo qué dato se había
   escapado y en qué commit estaba (con el SHA). Le señalan el tesoro a quien lo vaya a buscar. →
   *Regla actual: el detalle de una fuga vive en el canal privado; el PR público dice el efecto.*
3. **El PR de hotfix que explicaba el vector.** El cuerpo describía la falla de autorización con sus
   rutas concretas y los ids de ejemplo con los que se probó. Publicado en un repo público, eso es
   una guía de explotación — y en el medio de la ventana de deploy. → *Regla actual: efecto sí,
   vector no; y el vector va al aviso privado.*

Lección transversal: **el archivo es la parte fácil de cuidar.** Los gates miraban el árbol
(`guard-fugas.py`), las credenciales (`gitleaks`) y las dependencias (`trivy`); el texto libre de
GitHub no lo miraba nadie. Ahí es donde se escapó.

## 6. Cómo se hace cumplir

| Capa | Qué cubre | Cuándo |
|---|---|---|
| `scripts/guard-comunicacion.py` (job `fugas` del CI) | Título, cuerpo del PR, mensajes de commit, **nombre de la rama** y la **metadata de autoría** del commit: valores de la instalación, IPs, emails, y el **par explotable** (mecanismo + vector concreto). La autoría y el **registro** (§3bis) entran como **aviso** | En cada PR |
| `.github/pull_request_template.md` | Las preguntas que un gate no puede hacer («¿esto explica el cómo?») | Al abrir el PR |
| `scripts/guard-fugas.py` (job `fugas`) | El árbol: los archivos que se commitean | En cada PR |
| `gitleaks` (job `fugas`) | Credenciales en la historia completa | En cada PR |
| Reporte privado de vulnerabilidades + `SECURITY.md` | El canal para que un tercero reporte sin publicar | Siempre |

El gate es **deliberadamente asimétrico**: bloquea lo que se puede arreglar antes de mergear
(título, cuerpo, mensaje de un commit sin pushear) y **avisa** por lo que ya quedó publicado
(nombre de la rama, SHA, commits viejos). Un gate que frena el build por algo que el autor no puede
resolver desde su rama se termina desactivando; uno que frena lo que sí puede arreglar muerde.

El caso que fija esa frontera (medido al escribir el gate, 2026-09-29): un PR de **downmerge**
`main → develop` arrastra en su rango los commits que ya están en `main`. Bloquear por ésos sería
frenar un PR que no hizo nada malo. Por eso la regla es concreta: **si el commit ya es ancestro de
`main` o de `develop`, el hallazgo se degrada a aviso** («ya publicado: corregí con un commit de
corrección»); si el commit sólo vive en la rama del PR, bloquea.

Para revisar un PR **antes** de abrirlo:

```bash
python3 scripts/guard-comunicacion.py --title "…" --body-file /tmp/cuerpo.md --range origin/develop..HEAD
```

## 7. Deuda conocida

- Lo ya publicado en PRs y commits anteriores a esta política (ver el cierre del board): reescribir
  el **cuerpo** de los PRs es posible y barato; los **mensajes de commit** y los **SHAs** no se
  arreglan sin reescribir la historia (decisión tomada: no se reescribe).
- El job `fugas` —y con él este gate— vive hoy sólo en `develop`/`release/**`. Un PR **a `main`**
  (los hotfix, justo los de seguridad) no lo corre: traer el job a `main` es deuda abierta.
- La indexación externa (cachés de buscadores, archivos web, scrapers de GitHub) no está medida:
  editar un cuerpo de PR lo saca de la fuente, no de las copias que ya se hayan hecho.
