# CONTEXTO DE DESARROLLO - JARVIS IA (13/09/2026)

## 🎯 Objetivo General Logrado
Implementación exitosa de un Asistente de Inteligencia Artificial (Jarvis) avanzado, proactivo y conversacional, operando 100% en el entorno local (sin APIs de terceros) para garantizar privacidad y velocidad, integrado nativamente en Laravel 11, React e Inertia.js.

## 🏗️ Arquitectura y Tecnologías
- **Backend:** Laravel 11 (Controladores `JarvisAdminController` y `JarvisPublicController`).
- **Frontend:** React (Vite) + Inertia.js.
- **Modelo de IA:** `llama3.2` ejecutándose de forma local mediante **Ollama**.
- **WebSockets / Tiempo Real:** Laravel Reverb + Laravel Echo + Pusher-js (para alertas proactivas sin recargar la página).
- **Voz / Interfaz:** Web Speech API nativa (Reconocimiento de voz y Text-to-Speech) integrado en un módulo dedicado (`jarvisVoice.js`).

## 🧠 Estructura de "Doble Cerebro"

### 1. Jarvis Admin (El Copiloto Ejecutivo)
- **Ubicación:** Panel de Control de Administración (`JarvisHUD.jsx`).
- **Interfaz:** Esfera luminosa (Orb) flotante en la esquina inferior derecha con animaciones de latido y estado.
- **Capacidades:**
  - Análisis de ventas (hoy, semana, mes).
  - Consulta de stock crítico (leyendo la tabla `variante`).
  - Diagnóstico general del sistema.
  - Alertas WebSockets proactivas (ej. te habla en voz alta si ocurre un evento crítico, sin intervención manual).
- **Modo Continuo:** Permite mantener el micrófono abierto en un bucle (estilo manos libres) para conversar fluidamente.

### 2. Jarvis Public (Asistente de Tienda)
- **Ubicación:** Tienda pública (`JarvisChatDrawer.jsx`).
- **Interfaz:** Panel lateral emergente (Drawer) que reemplaza al chatbot tradicional.
- **Capacidades:**
  - Recomendaciones de productos.
  - Búsqueda de productos en el catálogo.
  - Respuestas sobre envíos, políticas de devolución y horarios.
  - Capacidad de agregar productos al carrito interactuando con la interfaz mediante Cards visuales.
- **Modo Continuo:** Incluye un botón para mantener la escucha activa.

## ⚡ Optimizaciones Implementadas

1. **Intenciones Ultra-Rápidas (Fast Intents):**
   - Para evitar el tiempo de carga del LLM en preguntas simples (como "¿Quién eres?" o "¿Cuáles son las ventas de hoy?"), se implementaron expresiones regulares que capturan intenciones comunes y responden en **0.1 segundos**.
2. **Ollama Keep-Alive & Limitación de Tokens:**
   - Se configuró el `OllamaService` con `'keep_alive' => '1h'` para que el modelo pesado no se descargue de la memoria RAM entre preguntas, eliminando el "Cold Start" de 10 segundos.
3. **Voz Masculina Asegurada:**
   - Se programó `jarvisVoice.js` para buscar voces específicas en el sistema que tengan tono masculino (Pablo, Raúl, Diego, etc.), mejorando la inmersión del "Jarvis" tradicional.

## 📂 Archivos Clave Creados / Modificados
- `app/Services/OllamaService.php`: Puente entre Laravel y la API local de Ollama.
- `app/Http/Controllers/JarvisAdminController.php`: Procesador de lógica e intenciones para el administrador.
- `app/Http/Controllers/JarvisPublicController.php`: Procesador de lógica e intenciones para la tienda pública.
- `app/Events/JarvisAlertEvent.php`: Evento de transmisión (Broadcast) en canal privado (`admin.jarvis`).
- `resources/js/Components/JarvisHUD.jsx`: Interfaz de usuario para el panel de control.
- `resources/js/Components/JarvisChatDrawer.jsx`: Interfaz de usuario para la tienda.
- `resources/js/lib/jarvisVoice.js`: Motor de voz y escucha continua.
- `resources/css/jarvis.css`: Estilos visuales de alto nivel (Glassmorphism, animaciones Cyberpunk).
- `config/broadcasting.php` y `.env`: Configuración exitosa de Laravel Reverb.
- `routes/api.php` y `routes/channels.php`: Endpoints de IA y autorización de canales WebSockets.

## 🚀 Estado Actual
- El servidor `Ollama` está activo.
- El servidor de WebSockets `Reverb` está en escucha y conectado correctamente al frontend.
- Los Workers de Laravel (`queue:work`) están corriendo.
- El sistema de voz (Input/Output) opera correctamente.
- **El proyecto está 100% funcional, estable y listo para presentación.**
