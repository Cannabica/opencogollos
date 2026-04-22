# Guía Técnica: API y Sistema de Tokens de OpenIndoor (v1.2) `[v1.2-api]`

Este documento describe la arquitectura de la API de OpenIndoor y cómo se gestionan los **ApiTokens** para asegurar la trazabilidad y privacidad de cada usuario.

---

## 🛠️ Autenticación y Seguridad `[tech-auth]`
La plataforma utiliza un sistema de Bearer Tokens asociados a cada **Tenant** (Inquilino), permitiendo un aislamiento total de los datos.

### 1. El Modelo `ApiToken` 
Localización: `app/Models/ApiToken.php`
*   **tenant_id:** Vínculo directo con el espacio de cultivo propietario.
*   **token_hash:** Hash SHA-256 del token plano generado por razones de seguridad.
*   **reference:** Campo descriptivo para identificar el origen del token (ej: "Sensor Humedad Cocina").

### 2. Middleware: `tenant.token` `[tech-security]`
Localización: `app/Http/Middleware/TenantTokenMiddleware.php`
Este componente es el encargado de:
*   Extraer el token del Bearer Header de la petición.
*   Consultar la validez del hash en la base de datos.
*   Inyectar el objeto `tenant` en la petición para uso por los controladores.

---

## 🛤️ Endpoints Implementados (v1.1 Realidad) `[api-endpoints]`
Localización: `routes/api.php`

### 1. Gestión de Plantas `[api-plants]`
*   **GET `/api/plants`**: Lista todas las plantas activas del tenant.
*   **GET `/api/plants/{id}`**: Detalle completo de una planta (historial de riegos, fotos).

### 2. Notificaciones de Automatización `[api-notify]`
*   **POST `/api/notifications`**: Permite que un sistema externo (ej. ESP32, Home Assistant) envíe una alerta al panel del usuario.

### 3. Información Sesión `[api-user]`
*   **GET `/api/user`**: Devuelve los datos básicos del tenant activo y sus habilidades permitidas.

---

## 🤝 Colaboración y Makers `[community-tech]`
Si eres desarrollador, puedes ayudar a mejorar esta API extendiendo el `TenantTokenService` o agregando nuevos controladores para el registro masivo de acciones de cultivo.

> [!TIP]
> Puedes generar tus propios tokens de forma sencilla en la sección **"Mi Grupo"** dentro del panel de la web.
