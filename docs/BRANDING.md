# Branding / marca blanca

OpenCogollos es **marca blanca**: el producto no impone ninguna marca propia. Una instalación
nueva arranca **neutra** (sin logo, sin links de comunidad, sin footer de registro, mails sin
imagen) y vos le ponés tu identidad por variables de entorno.

> **Cómo se leen estas claves.** Las claves `PLATFORM_*` las lee `config/platform.php`. Si tu
> versión del repo no tiene ese archivo, esas variables **se ignoran** (no rompen nada, pero no
> hacen nada). Verificá con `ls config/platform.php`.

---

## 1. Las dos cosas que definen tu identidad

| Variable | Qué es | Dónde se ve |
|---|---|---|
| `APP_NAME` | Nombre de **esta instalación** | Logos del panel, nombre de la PWA (`/manifest.json`), `alt` del header de los mails, `VITE_APP_NAME` |
| `PLATFORM_*` (9 claves) | Las **superficies de marca**: web, estado, comunidad, contacto, bot | Links del dashboard y de las páginas de registro/activación, textos del bot y de las notificaciones, header de los mails |

`APP_NAME` es bootstrap de Laravel (va en el `.env`); las `PLATFORM_*` son la capa de marca.

---

## 2. Las 9 claves `PLATFORM_*`

**Todas son opcionales.** Su default es `null` y, cuando están vacías, la UI **omite** el bloque
que las usa (no muestra un link roto ni un logo inexistente).

| Clave | Qué controla | Ejemplo (instalación neutra → con marca) |
|---|---|---|
| `PLATFORM_BRAND_NAME` | Nombre de marca visible en textos de bot/notificaciones | *(vacío)* → `Mi Cultivo` |
| `PLATFORM_SITE_URL` | Tu sitio público (link del dashboard) | *(vacío)* → `https://micultivo.example` |
| `PLATFORM_STATUS_PAGE_URL` | Página pública de estado del servicio (uptime) | *(vacío)* → `https://status.micultivo.example` |
| `PLATFORM_PLATFORM_URL` | URL base de la plataforma (textos de bot y mails) | *(vacío)* → `https://app.micultivo.example` |
| `PLATFORM_DISCORD_URL` | Invitación a tu comunidad (Discord u otra) | *(vacío)* → `https://discord.gg/xxxxxxxx` |
| `PLATFORM_FEEDBACK_URL` | Formulario o URL para reportar bugs / dejar feedback | *(vacío)* → `https://forms.gle/xxxxxxxx` |
| `PLATFORM_ADMIN_EMAIL` | Email del superadmin (alta del admin y avisos) | *(vacío)* → `admin@micultivo.example` |
| `PLATFORM_TELEGRAM_BOT_USERNAME` | Username del bot **sin @**, para los textos de ayuda | *(vacío)* → `micultivo_bot` |
| `PLATFORM_MAIL_HEADER_LOGO` | Logo del header de los mails (URL o path público) | *(vacío)* → `/images/mi-logo.png` |

Formato recomendado: URLs **con `https://`** y **sin barra final** en las que son base
(`PLATFORM_STATUS_PAGE_URL`, `PLATFORM_PLATFORM_URL`). `PLATFORM_MAIL_HEADER_LOGO` acepta una URL
absoluta o un path dentro de `public/` (ej. `/images/mi-logo.png`).

### Dejarlas vacías también es una decisión

Si no querés mostrar links de comunidad ni un logo en los mails, dejá esas claves sin definir.
Eso es la instalación neutra: la app funciona completa, simplemente no muestra esos bloques.

---

## 3. El remitente de los mails NO es una de las 9

Es una confusión frecuente. El "de" de los mails sale de **bootstrap de Laravel**, no de la capa
de marca:

```dotenv
MAIL_FROM_ADDRESS=noreply@micultivo.example
# MAIL_FROM_NAME: si lo dejás vacío, el remitente visible usa APP_NAME
MAIL_FROM_NAME=
```

Van con las credenciales SMTP de la instalación. Una instalación neutra que no los defina hereda
el default del repo (`hello@example.com`).

---

## 4. Ejemplo completo: `.env` de una instalación con marca

```dotenv
APP_NAME=Mi Cultivo

PLATFORM_BRAND_NAME=Mi Cultivo
PLATFORM_SITE_URL=https://micultivo.example
PLATFORM_STATUS_PAGE_URL=https://status.micultivo.example
PLATFORM_PLATFORM_URL=https://app.micultivo.example
PLATFORM_DISCORD_URL=https://discord.gg/xxxxxxxx
PLATFORM_FEEDBACK_URL=https://forms.gle/xxxxxxxx
PLATFORM_ADMIN_EMAIL=admin@micultivo.example
PLATFORM_TELEGRAM_BOT_USERNAME=micultivo_bot
PLATFORM_MAIL_HEADER_LOGO=/images/mi-logo.png

MAIL_FROM_ADDRESS=noreply@micultivo.example
```

Y el mismo bloque **neutro** (lo que trae el repo, sin tocar nada):

```dotenv
APP_NAME=OpenCogollos
# las 9 PLATFORM_* sin definir
```

---

## 5. Aplicar los cambios

La app **cachea la configuración** (es lo que la hace rápida en producción), así que después de
editar el `.env` hay que recargarla:

```bash
# instalación nativa
php artisan config:clear

# instalación con Docker
docker compose -f docker-compose.local.yml --env-file .env.local restart php
```

En una instalación con `config:cache` activo (el entrypoint del contenedor lo activa solo) los
valores del `.env` quedan **horneados en el cache**: si editás el `.env` y no recargás, seguís
viendo los valores viejos.

---

## 6. Si sos el mantenedor de una marca que usa OpenCogollos

- La **marca del producto** (OpenCogollos) y la **marca de tu instalación** son cosas distintas:
  la primera es de dónde viene el software; la segunda es tuya.
- No hardcodees tu marca en el código: ponela en tu `.env` (o en tu `.env` de deploy, que no se
  commitea). Así podés actualizar el repo sin conflictos.
- Los archivos de imagen que referencia `PLATFORM_MAIL_HEADER_LOGO` deben existir en
  `public/` de tu instalación (o ser una URL accesible desde los clientes de correo).
