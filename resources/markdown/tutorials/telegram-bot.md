# Configurar cuenta en el bot de Telegram

El bot de Telegram de {{ plataforma }} es tu asistente personal para la gestión del cultivo directamente desde tu celular. Una vez vinculado, te permite monitorear y administrar tu espacio en tiempo real sin necesidad de ingresar a la plataforma web. Podrás consultar el detalle de tus indoors, tus plantas, semillas y el historial de acciones realizadas. Además, cuenta con funciones interactivas que te permiten, por ejemplo, repetir ágilmente el último riego y asociar fotos o álbumes de imágenes a tus cultivos con solo enviarlas al chat.

## 1. Obtener el token de autenticación

1. Ingresa a tu cuenta en {{ plataforma }} y dirígete a la sección **Mi grupo** (`/tenant/tenant-page`).
2. En la parte inferior, en la sección de **Tokens API**, ingresa un texto de referencia para identificar para qué utilizarás este token (ej. "Bot Telegram").
3. Al crearlo, el token se mostrará **una sola vez**. Cópialo en tu portapapeles.

> **Importante:** En caso de perder el token o considerar que se vio comprometido, debes revocarlo y generar uno nuevo inmediatamente de forma preventiva.

## 2. Autenticarte en el bot de Telegram

1. Con el token generado, {{ bot }}.
2. Puedes iniciar la conversación con el comando `/start` para recibir el mensaje de bienvenida, o directamente proceder a la autenticación.
3. Para vincular tu cuenta, envía el comando `/auth {TOKEN_API}`, reemplazando `{TOKEN_API}` por el token que obtuviste previamente en la plataforma.
4. Recibirás un mensaje de confirmación.

¡Listo! Ya puedes utilizar todos los comandos mencionados en `/help` y comenzar a recibir notificaciones directamente en tu chat de Telegram.
