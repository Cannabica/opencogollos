# Estructura Wiki: GitHub OpenIndoor (v1.3) - "Sin Vueltas"

Propuesta actualizada para la Wiki de [Cannabica/OpenIndoor](https://github.com/Cannabica/OpenIndoor). Esta versión refleja fielmente el Wizard de registro y el Dashboard implementado.

---

## 🏠 Home
* Bienvenido al ecosistema de **Cannabica**.
* Filosofía: "Tu cuaderno de cultivo, ahora en el celu".
* Enlaces rápidos a la App y al Blog.

---

## 📚 Guía de Usuario: "Cómo Empezar"
Esta sección documenta el camino real desde cero.

### 1. Registro (Wizard de 3 Pasos)
* **Paso 1:** Tus datos y segmentación por tipo de usuario (Hogareño, Club, etc.).
* **Paso 2:** Contraseña y definición de uso personal o en equipo.
* **Paso 3:** Setup inicial de cultivos para no entrar a un panel vacío.

### 2. Dashboard de "Primeros Pasos" (6 Tarjetas)
* **Configurar Bot de Telegram:** Vinculación mediante `/auth <TOKEN>`.
* **Configurar tu Indoor:** Medidas, luz y ventilación.
* **Revisar Semillas Globales:** Catálogo pre-cargado.
* **Cargar Semillas Propias:** Digitalizar tu propio inventario.
* **Registrar Plantas:** Crear individuos con semillas y sustratos asociados.
* **Primer Cuidado:** Registro de riegos, podas y seguimientos iniciales.

---

## 🛠️ Documentación Técnica (Devs & Makers) `[tech-docs]`
1. **Bot de Telegram (Casos Reales):**
    * `AuthCommand`: Vinculación con el `@openindoor_bot`.
    * `RepeatLastIrrigationCommand`: Comando para registrar riegos rápidos por chat.
    * `PhotoHandlerCommand`: Lógica para procesar imágenes y guardarlas en la bitácora.
2. **Sistema de API Tokens:** Autenticación mediante Bearer Tokens de tenant.
3. **Endpoints de la API:** Catálogo de rutas (`/plants`, `/notifications`, `/user`).
4. **Colaboración:** Estándares de código Laravel y FilamentPHP.

---

## 🌍 Normativa y Trazabilidad (REPROCANN 2026) `[roadmap-legal]`
> [!NOTE]
> **Hoja de Ruta:** Estrategia digital para cumplir con la **Res 1780/2025** en Argentina, buscando ofrecer un blindaje legal ante inspecciones mediante trazabilidad inalterable.

---

## 🚀 Proyectos Maker (Comunidad)
* **Integraciones:** (Buscamos gente que quiera sumar sus propios ejemplos).
