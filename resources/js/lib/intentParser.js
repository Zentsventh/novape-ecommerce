/**
 * Intent Parser para Jarvis — Motor de comandos 100% local (sin API).
 * Estilo Alexa: reconoce comandos de navegación, acciones, consultas y conversación.
 * Todas las rutas usan el prefijo /admin/ según web.php.
 */

// ─── Mapa de rutas del panel admin ────────────────────────────
const ROUTE_MAP = [
  { keywords: ['dashboard', 'inicio', 'panel', 'panel de control'], route: '/admin', label: 'el panel de control' },
  { keywords: ['gastos'], route: '/admin/gastos', label: 'gastos' },
  { keywords: ['productos'], route: '/admin/products', label: 'productos' },
  { keywords: ['categorias', 'categorías'], route: '/admin/categorias', label: 'categorías' },
  { keywords: ['marcas'], route: '/admin/marcas', label: 'marcas' },
  { keywords: ['pedidos'], route: '/admin/pedidos', label: 'pedidos' },
  { keywords: ['clientes'], route: '/admin/clientes', label: 'clientes' },
  { keywords: ['trabajadores'], route: '/admin/trabajadores', label: 'trabajadores' },
  { keywords: ['proveedores'], route: '/admin/proveedores', label: 'proveedores' },
  { keywords: ['cupones'], route: '/admin/cupones', label: 'cupones' },
  { keywords: ['pos', 'punto de venta', 'ventas', 'caja'], route: '/admin/pos', label: 'punto de venta' },
  { keywords: ['compras'], route: '/admin/compras', label: 'compras' },
  { keywords: ['inventario'], route: '/admin/inventario', label: 'inventario' },
  { keywords: ['almacenes'], route: '/admin/almacenes', label: 'almacenes' },
  { keywords: ['zonas'], route: '/admin/zonas', label: 'zonas' },
  { keywords: ['métodos de pago', 'metodos de pago'], route: '/admin/metodos-pago', label: 'métodos de pago' },
  { keywords: ['roles'], route: '/admin/roles', label: 'roles' },
  { keywords: ['permisos'], route: '/admin/ajustes/permisos', label: 'permisos' },
  { keywords: ['ajustes', 'configuracion', 'configuración'], route: '/admin/ajustes', label: 'ajustes' },
  { keywords: ['banners'], route: '/admin/banners', label: 'banners' },
];

// ─── Verbos de navegación ─────────────────────────────────────
const NAV_VERBS = [
  'abrir', 'abre', 'ir a', 've a', 'vamos a',
  'mostrar', 'muestra', 'muéstrame', 'muestrame',
  'llévame a', 'llevame a', 'navegar a',
  'entra a', 'entrar a', 'entra en',
  'abre la sección', 'abre la seccion',
  'quiero ver', 'necesito ver',
];

// ─── Respuestas conversacionales (tipo Alexa) ─────────────────
const CONVERSATIONS = [
  {
    patterns: ['hola', 'buenos días', 'buenas tardes', 'buenas noches', 'hey', 'qué tal'],
    responses: [
      '¡Hola! Soy Jarvis, tu asistente de voz. ¿En qué puedo ayudarte?',
      '¡Hola! Estoy listo para ayudarte. Puedes pedirme que abra cualquier sección del panel.',
      '¡Qué tal! Dime en qué te puedo ayudar.',
    ],
  },
  {
    patterns: ['cómo estás', 'como estas', 'qué tal estás', 'todo bien'],
    responses: [
      'Muy bien, gracias. Todos los sistemas están operativos.',
      'Funcionando al cien por cien. ¿Qué necesitas?',
      'Excelente, listo para trabajar. ¿En qué te ayudo?',
    ],
  },
  {
    patterns: ['gracias', 'muchas gracias', 'te agradezco'],
    responses: [
      '¡De nada! Estoy aquí para ayudarte.',
      'Con gusto. Si necesitas algo más, solo dime.',
      '¡Para eso estoy! ¿Algo más?',
    ],
  },
  {
    patterns: ['ayuda', 'qué puedes hacer', 'que puedes hacer', 'qué comandos', 'que comandos', 'instrucciones'],
    responses: [
      'Puedo ayudarte a navegar por el panel. Dime por ejemplo: Jarvis abrir gastos, Jarvis mostrar pedidos, Jarvis ir a productos. También puedo recargar la página o cerrar sesión.',
    ],
  },
  {
    patterns: ['quién eres', 'quien eres', 'cuál es tu nombre', 'cual es tu nombre', 'cómo te llamas'],
    responses: [
      'Soy Jarvis, tu asistente de voz para el panel de administración de Novape.',
      'Me llamo Jarvis. Soy tu asistente virtual de voz.',
    ],
  },
  {
    patterns: ['adiós', 'adios', 'chao', 'hasta luego', 'nos vemos'],
    responses: [
      '¡Hasta luego! Estaré aquí cuando me necesites.',
      '¡Nos vemos! Solo di Jarvis cuando quieras hablar conmigo.',
    ],
  },
];

// ─── Respuestas de información del sistema ────────────────────
const SYSTEM_QUERIES = [
  {
    patterns: ['qué hora es', 'que hora es', 'dime la hora'],
    handler: () => {
      const now = new Date();
      const h = now.getHours();
      const m = now.getMinutes().toString().padStart(2, '0');
      const period = h >= 12 ? 'de la tarde' : 'de la mañana';
      const hour12 = h > 12 ? h - 12 : h === 0 ? 12 : h;
      return `Son las ${hour12} con ${m} minutos ${period}.`;
    },
  },
  {
    patterns: ['qué fecha es', 'que fecha es', 'qué día es', 'que dia es', 'dime la fecha'],
    handler: () => {
      const now = new Date();
      const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      return `Hoy es ${now.toLocaleDateString('es-ES', options)}.`;
    },
  },
  {
    patterns: ['en qué página estoy', 'en que pagina estoy', 'dónde estoy', 'donde estoy'],
    handler: () => {
      const path = window.location.pathname;
      const section = path.replace('/admin/', '').replace('/admin', 'dashboard') || 'dashboard';
      return `Estás en la sección de ${section}.`;
    },
  },
];

/**
 * Parsea la transcripción de voz y devuelve una intención.
 * @param {string} transcript
 * @returns {{ type: string, target?: string, voice?: string }}
 */
export function parseIntent(transcript) {
  const txt = transcript.toLowerCase().trim();

  // ── 1) Navegación — buscar "verbo + destino" ────────────────
  for (const entry of ROUTE_MAP) {
    for (const keyword of entry.keywords) {
      for (const verb of NAV_VERBS) {
        if (txt.includes(verb + ' ' + keyword)) {
          return {
            type: 'navigation',
            target: entry.route,
            voice: `Abriendo ${entry.label}.`,
          };
        }
      }
      // "Jarvis gastos" (sin verbo, comando corto)
      if (txt.includes('jarvis') && txt.includes(keyword) && keyword.length > 3) {
        const words = txt.split(/\s+/).filter(w => w !== 'jarvis');
        if (words.length <= 3) {
          return {
            type: 'navigation',
            target: entry.route,
            voice: `Abriendo ${entry.label}.`,
          };
        }
      }
    }
  }

  // ── 2) Acciones del sistema ─────────────────────────────────
  if (/\b(recargar|recarga|actualizar|actualiza|refresh)\b/.test(txt)) {
    return { type: 'action', target: 'reload', voice: 'Recargando la página.' };
  }
  if (/cerrar sesión|cierra sesión|logout|salir del sistema|cerrar la sesión/.test(txt)) {
    return { type: 'action', target: 'logout', voice: 'Cerrando sesión.' };
  }
  if (/\b(retroceder|atrás|atras|volver|regresar)\b/.test(txt)) {
    return { type: 'action', target: 'back', voice: 'Volviendo atrás.' };
  }

  // ── 3) Consultas del sistema (hora, fecha, etc.) ────────────
  for (const query of SYSTEM_QUERIES) {
    for (const pattern of query.patterns) {
      if (txt.includes(pattern)) {
        return {
          type: 'speak',
          voice: query.handler(),
        };
      }
    }
  }

  // ── 4) Conversación (saludos, ayuda, etc.) ──────────────────
  for (const conv of CONVERSATIONS) {
    for (const pattern of conv.patterns) {
      if (txt.includes(pattern)) {
        const responses = conv.responses;
        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
        return {
          type: 'speak',
          voice: randomResponse,
        };
      }
    }
  }

  // ── 5) No reconocido ───────────────────────────────────────
  // Si contiene "jarvis" pero no se reconoció el comando
  if (txt.includes('jarvis')) {
    return {
      type: 'speak',
      voice: 'No entendí ese comando. Puedes decir cosas como: abrir gastos, mostrar pedidos, o ir a productos.',
    };
  }

  // Sin wake word → ignorar
  return { type: 'ignore' };
}
