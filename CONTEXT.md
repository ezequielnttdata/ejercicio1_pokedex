# Contexto Completo del Proyecto - Pokédex Drupal 11

## 📚 Índice

1. [Contexto General del Proyecto](#1-contexto-general-del-proyecto)
2. [Requisitos de la Prueba Técnica](#2-requisitos-de-la-prueba-técnica)
3. [Decisiones Arquitectónicas](#3-decisiones-arquitectónicas)
4. [Implementación Técnica Detallada](#4-implementación-técnica-detallada)
5. [Código Fuente Documentado](#5-código-fuente-documentado)
6. [Flujos de Trabajo](#6-flujos-de-trabajo)
7. [Patrones y Principios Aplicados](#7-patrones-y-principios-aplicados)
8. [Manejo de Errores y Estados](#8-manejo-de-errores-y-estados)
9. [Testing y Validación](#9-testing-y-validación)
10. [Mejoras Futuras](#10-mejoras-futuras)

---

## 1. Contexto General del Proyecto

### 1.1 Objetivo del Proyecto

Desarrollar una aplicación web tipo **Pokédex** en Drupal 11 que:
- ✅ Consuma datos en tiempo real desde la PokéAPI (https://pokeapi.co)
- ✅ Muestre un listado paginado de Pokémon
- ✅ Permita ver detalles individuales de cada Pokémon
- ✅ Implemente manejo robusto de errores
- ✅ Demuestre conocimientos profesionales de Drupal y arquitectura backend

### 1.2 Propósito de la Prueba Técnica

Esta prueba técnica evalúa:
- ✅ Comprensión de la arquitectura de Drupal
- ✅ Capacidad para consumir APIs externas
- ✅ Implementación de patrones de diseño (SOLID, Dependency Injection)
- ✅ Manejo profesional de errores
- ✅ Separación de responsabilidades
- ✅ Conocimiento de Render Arrays y sistema de templates
- ✅ Uso correcto de servicios y controllers
- ✅ Código limpio y mantenible
- ✅ Implementación de Drupal Libraries para assets (CSS/JS)
- ✅ UI/UX mejorada con diseño temático

### 1.3 Stack Tecnológico

```
┌─────────────────────────────────────┐
│       Presentación (Twig)           │
│   - Templates HTML                  │
│   - Lógica de vista                 │
├─────────────────────────────────────┤
│    Controllers (Symfony)            │
│   - Coordinación                    │
│   - Request/Response                │
├─────────────────────────────────────┤
│       Services (PHP 8+)             │
│   - Lógica de negocio               │
│   - Consumo API                     │
├─────────────────────────────────────┤
│    HTTP Client (Guzzle)             │
│   - Peticiones HTTP                 │
├─────────────────────────────────────┤
│       PokéAPI (REST)                │
│   - Datos de Pokémon                │
└─────────────────────────────────────┘
```

**Tecnologías Utilizadas:**
- **Backend**: Drupal 11.x
- **Lenguaje**: PHP 8.1+ (type hints, typed properties)
- **Framework Base**: Symfony Components
- **Template Engine**: Twig 3.x
- **HTTP Client**: GuzzleHttp (Drupal's HTTP client)
- **API Externa**: PokéAPI v2
- **Entorno de Desarrollo**: DDEV
- **CLI Tools**: Drush 12
- **Servidor Web**: Nginx (via DDEV)
- **Base de Datos**: MariaDB 10.6

---

## 2. Requisitos de la Prueba Técnica

### 2.1 Requisitos Funcionales Obligatorios

#### ✅ RF-01: Consumo de API
**Implementación:**
- Endpoint: `GET https://pokeapi.co/api/v2/pokemon?limit={limit}&offset={offset}`
- Parámetros: `limit` (cantidad) y `offset` (desplazamiento)
- Datos reales (no mocks)
- Manejo de respuestas JSON
- Timeout configurado: 10 segundos

#### ✅ RF-02: Listado de Pokémon
**Debe mostrar por cada Pokémon:**
- **Imagen (sprite)**: URL construida desde ID
- **Nombre**: Capitalizado en la vista
- **ID/Número**: Extraído de la URL de la API
- **Layout**: Grid responsive de tarjetas

**URL de imagen:**
```
https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/{id}.png
```

#### ✅ RF-03: Paginación
**Características:**
- Mínimo 3 páginas navegables
- 20 Pokémon por página
- Parámetro `?page=N` en la URL
- Botones "Anterior" y "Siguiente"
- Estados disabled cuando corresponde
- Contador de página actual

**Cálculo:**
```php
$limit = 20;
$page = (int) $request->query->get('page', 1);
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_count / $limit);
```

#### ✅ RF-04: Página de Detalle
**Ruta:** `/pokedex/{pokemon}`
- Acepta nombre (ej: "pikachu") o ID (ej: "25")
- Título dinámico con el nombre del Pokémon

**Información mostrada:**
- ✅ Nombre (capitalizado)
- ✅ ID (#001, #025, etc.)
- ✅ Sprite (imagen grande)
- ✅ Tipos (badges con colores específicos)
- ✅ Altura (convertida a metros)
- ✅ Peso (convertido a kilogramos)

**Conversiones:**
```php
// API devuelve en decímetros, convertir a metros
$height_meters = $height / 10;

// API devuelve en hectogramos, convertir a kg
$weight_kg = $weight / 10;
```

#### ✅ RF-05: Manejo de Estados
**Estados implementados:**
1. **Success**: Datos cargados correctamente
2. **Error**: Fallo en la API con mensaje y botón de reintento
3. **Empty**: Sin resultados (poco probable en PokéAPI)
4. **Loading**: Implícito en SSR (loader del navegador)

#### ✅ RF-06: Error Controlado
**Requisitos:**
- ❌ NO debe romper la aplicación
- ✅ Captura todas las excepciones
- ✅ Mensajes user-friendly
- ✅ Botón de reintento en ambas páginas
- ✅ Logging para debugging

---

## 3. Decisiones Arquitectónicas

### 3.1 Módulo Custom vs. Tema

**✅ DECISIÓN: Módulo Custom**

**Justificación técnica:**

| Característica | Tema | Módulo Custom | Necesario |
|---------------|------|---------------|-----------|
| Rutas propias | ❌ | ✅ | ✅ |
| Controllers | ❌ | ✅ | ✅ |
| Servicios | ❌ | ✅ | ✅ |
| Lógica de negocio | ❌ | ✅ | ✅ |
| Templates | ✅ | ✅ | ✅ |
| CSS/JS | ✅ | ✅ | - |

**Conclusión:** Un tema solo controla presentación. Para esta aplicación necesitamos:
- Rutas personalizadas
- Controllers para manejar requests
- Servicios para consumir APIs
- Inyección de dependencias
- Lógica de negocio

### 3.2 SSR vs. SPA

**✅ DECISIÓN: Server-Side Rendering (SSR)**

**Justificación:**

**Ventajas SSR:**
- ✅ Más simple de implementar
- ✅ Apropiado para prueba técnica backend
- ✅ Mejor SEO
- ✅ No requiere framework JS complejo
- ✅ Funciona sin JavaScript
- ✅ Estándar de Drupal

**Desventajas SPA:**
- ❌ Over-engineering innecesario
- ❌ Requiere React/Vue/Angular
- ❌ Más complejo de testear
- ❌ No demuestra conocimientos de Drupal

**Flujo de request SSR:**
```
1. Usuario hace request → /pokedex?page=2
2. Drupal routing → PokedexController::list()
3. Controller → PokeApiService::getPokemonList()
4. Service → HTTP request a PokéAPI
5. Service ← JSON response
6. Controller ← Array procesado
7. Controller → Render Array
8. Twig → HTML renderizado
9. Usuario ← Página completa HTML
```

### 3.3 Render Arrays vs. HTML Directo

**✅ DECISIÓN: Render Arrays + Twig**

**Por qué Render Arrays:**
```php
// ✅ CORRECTO - Render Array
return [
  '#theme' => 'pokedex_list',
  '#items' => $items,
  '#page' => $page,
  '#has_previous' => $has_previous,
  '#has_next' => $has_next,
];

// ❌ INCORRECTO - HTML directo
return new Response('<html>...</html>');

// ❌ INCORRECTO - Markup simple
return ['#markup' => '<h1>Pokédex</h1>'];
```

**Ventajas Render Arrays:**
- ✅ Estándar de Drupal
- ✅ Alterable via hooks (hook_preprocess)
- ✅ Soporte para cache tags
- ✅ Soporte para attachments (CSS/JS)
- ✅ Demuestra conocimiento profesional

### 3.4 Arquitectura en Capas

**Implementación de 3 capas:**

```
┌───────────────────────────────────────────┐
│  CAPA 1: PRESENTACIÓN (View Layer)       │
│  ├── pokedex-list.html.twig              │
│  └── pokedex-detail.html.twig            │
│                                           │
│  Responsabilidad:                         │
│  - Renderizar HTML                        │
│  - Mostrar datos                          │
│  - Lógica condicional simple (if/for)    │
└─────────────────┬─────────────────────────┘
                  │
┌─────────────────▼─────────────────────────┐
│  CAPA 2: APLICACIÓN (Controller Layer)    │
│  └── PokedexController                    │
│                                           │
│  Responsabilidad:                         │
│  - Recibir requests HTTP                  │
│  - Validar parámetros                     │
│  - Coordinar servicios                    │
│  - Construir Render Arrays                │
│  - Manejo de errores (try/catch)          │
└─────────────────┬─────────────────────────┘
                  │
┌─────────────────▼─────────────────────────┐
│  CAPA 3: DOMINIO (Service Layer)          │
│  └── PokeApiService                       │
│                                           │
│  Responsabilidad:                         │
│  - Lógica de negocio                      │
│  - Comunicación con API externa           │
│  - Transformación de datos                │
│  - Logging de errores                     │
└───────────────────────────────────────────┘
```

**Principio de Responsabilidad Única:**
- Cada capa tiene una responsabilidad clara
- Los cambios en una capa no afectan a otras
- Fácil de testear independientemente
- Facilita el mantenimiento

### 3.5 Inyección de Dependencias

**✅ DECISIÓN: Constructor Injection**

**Implementación completa:**

```php
class PokedexController extends ControllerBase {
  
  // 1. Propiedad tipada (PHP 8+)
  protected PokeApiService $pokeApiService;
  protected RequestStack $requestStack;
  
  // 2. Constructor con type hints
  public function __construct(
    PokeApiService $poke_api_service,
    RequestStack $request_stack
  ) {
    $this->pokeApiService = $poke_api_service;
    $this->requestStack = $request_stack;
  }
  
  // 3. Factory method para DI container
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('pokedex.pokeapi'),
      $container->get('request_stack')
    );
  }
}
```

**Ventajas:**
- ✅ Testeable (inyectar mocks en tests)
- ✅ Desacoplado (no depende de implementación)
- ✅ Sigue SOLID principles
- ✅ Estándar Symfony/Drupal

**Anti-patrones a evitar:**
```php
// ❌ Service Locator (anti-patrón)
$service = \Drupal::service('pokedex.pokeapi');

// ❌ Static factory (no testeable)
$service = PokeApiService::getInstance();
```

---

## 4. Implementación Técnica Detallada

### 4.1 Estructura Completa del Módulo

```
web/modules/custom/pokedex/
│
├── pokedex.info.yml          # Metadata del módulo
├── pokedex.routing.yml       # Definición de rutas
├── pokedex.services.yml      # Registro de servicios en DI container
├── pokedex.libraries.yml     # Definición de libraries (CSS)
├── pokedex.module            # Hooks de Drupal (hook_theme)
│
├── src/
│   ├── Controller/
│   │   └── PokedexController.php    # Maneja requests HTTP
│   │
│   └── Service/
│       └── PokeApiService.php       # Consume PokéAPI
│
├── css/
│   ├── pokedex.css               # Estilos para listado
│   └── pokedex_detail.css        # Estilos para detalle
│
└── templates/
    ├── pokedex-list.html.twig       # Template listado
    └── pokedex-detail.html.twig     # Template detalle
```

### 4.2 Configuración: pokedex.info.yml

```yaml
name: 'Pokédex'
type: module
description: 'Módulo para visualizar Pokémon usando PokéAPI'
package: Custom
core_version_requirement: ^11
dependencies:
  - drupal:node
```

**Explicación campo por campo:**

| Campo | Valor | Propósito |
|-------|-------|-----------|
| `name` | 'Pokédex' | Nombre visible en admin/UI |
| `type` | module | Define que es un módulo |
| `description` | ... | Texto en /admin/modules |
| `package` | Custom | Agrupa módulos similares |
| `core_version_requirement` | ^11 | Compatible Drupal 11.x |

---

## 5. Código Fuente Documentado

### 5.1 PokeApiService (Implementado)
**Métodos principales:**
- ✅ `getPokemonList()`: Consumo de API con HttpClient
- ✅ `getPokemonDetail()`: Obtiene detalles de un Pokémon
- ✅ `searchPokemon()`: Búsqueda por nombre o ID (extra)
- ✅ `getAllTypes()`: Obtiene todos los tipos disponibles (extra)
- ✅ `getPokemonsByType()`: Filtra Pokémon por tipo (extra)

**Características:**
- ✅ Manejo de excepciones
- ✅ Logging de errores
- ✅ Extracción de ID desde URL
- ✅ Validación de respuestas

### 5.2 PokedexController (Implementado)
- ✅ Inyección de dependencias
- ✅ Validación de parámetros
- ✅ Try/catch robusto
- ✅ Construcción de Render Arrays
- ✅ Cálculo de paginación
- ✅ Manejo de búsqueda y filtros (extra)
- ✅ Attachment de libraries CSS

### 5.3 Templates Twig (Implementados)
- ✅ **pokedex-list.html.twig**: 
  - Grid responsive con tarjetas
  - Formularios de búsqueda y filtros
  - Badges de tipos con colores
  - Gradientes dinámicos según tipos
  - Botones diseño Pokéball
  - Estados visuales claros
  
- ✅ **pokedex-detail.html.twig**: 
  - Ficha detallada con tipos
  - Breadcrumbs de navegación
  - Información completa del Pokémon
  - Diseño responsive

### 5.4 CSS Organizado con Libraries (Implementado)
- ✅ **pokedex.libraries.yml**: Define las libraries del módulo
- ✅ **css/pokedex.css**: Estilos del listado
  - Grid responsive
  - Tarjetas con gradientes
  - Badges de tipos
  - Botones Pokéball
  - Estados visuales
  
- ✅ **css/pokedex_detail.css**: Estilos del detalle
  - Layout de ficha
  - Breadcrumbs
  - Badges de tipos
  - Diseño responsive

---

## 6. Flujos de Trabajo

### 6.1 Flujo Listado

**Flujo normal:**
```
Usuario → /pokedex?page=2
    ↓
Controller valida page ≥ 1
    ↓
Calcula offset = (2-1) * 20 = 20
    ↓
Service → GET pokeapi.co/api/v2/pokemon?limit=20&offset=20
    ↓
Procesa respuesta JSON
    ↓
Extrae IDs de URLs
    ↓
Controller construye Render Array con library CSS
    ↓
Twig renderiza grid HTML con estilos
    ↓
Usuario ve 20 Pokémon (41-60) con tarjetas estilizadas
```

**Flujo con búsqueda:**
```
Usuario → /pokedex?search=pikachu
    ↓
Controller valida término de búsqueda
    ↓
Service → searchPokemon('pikachu')
    ↓
Service → GET pokeapi.co/api/v2/pokemon/pikachu
    ↓
Procesa respuesta o captura 404
    ↓
Controller construye Render Array con resultado único o estado 'not_found'
    ↓
Twig muestra resultado o mensaje "Pokémon no encontrado"
```

**Flujo con filtro por tipo:**
```
Usuario → /pokedex?type=fire&page=1
    ↓
Controller detecta parámetro 'type'
    ↓
Service → getPokemonsByType('fire', limit, offset)
    ↓
Service → GET pokeapi.co/api/v2/type/fire
    ↓
Procesa lista de Pokémon de ese tipo
    ↓
Pagina resultados
    ↓
Twig muestra solo Pokémon de tipo fuego
```

### 6.2 Flujo Detalle
```
Usuario → /pokedex/pikachu
    ↓
Service → GET pokeapi.co/api/v2/pokemon/pikachu
    ↓
Obtiene datos completos
    ↓
Extrae tipos, altura, peso
    ↓
Controller pasa a Twig
    ↓
Twig muestra ficha detallada
```

### 6.3 Flujo Error
```
API falla (timeout/404/500)
    ↓
Service lanza Exception
    ↓
Controller captura en catch
    ↓
status = 'error'
    ↓
Twig muestra mensaje + botón reintento
    ↓
Usuario puede reintentar
```

---

## 7. Patrones y Principios Aplicados

### 7.1 SOLID
- **S**ingle Responsibility: Cada clase una responsabilidad
- **O**pen/Closed: Extensible vía servicios
- **L**iskov Substitution: Interfaces consistentes
- **I**nterface Segregation: DI con interfaces específicas
- **D**ependency Inversion: Inyección de dependencias

### 7.2 Separación de Responsabilidades
- **Service**: Lógica + API
- **Controller**: Coordinación
- **Template**: Presentación

### 7.3 Dependency Injection
- Constructor injection
- Container de Symfony
- Código testeable

---

## 8. Manejo de Errores y Estados

### 8.1 Estados Implementados
1. **success**: Datos cargados correctamente
2. **error**: Fallo en la API con mensaje y botón de reintento
3. **empty**: Sin resultados para mostrar
4. **not_found**: Pokémon buscado no existe (búsqueda específica)

### 8.2 Estrategia de Manejo
- Try/catch en controllers
- Excepciones específicas capturadas
- Mensajes user-friendly con iconos visuales
- Logging técnico separado para debugging
- Botones de reintento en estado error
- Botones de navegación en todos los estados
- Estados visuales claros con CSS

---

## 9. Testing y Validación

### 9.1 Checklist Completado
- ✅ Módulo custom creado
- ✅ Servicio API implementado
- ✅ Rutas definidas
- ✅ Controller con DI
- ✅ Listado funcional
- ✅ Paginación (3+ páginas)
- ✅ Detalle completo
- ✅ Manejo errores
- ✅ Estados implementados
- ✅ Código limpio

### 9.2 URLs de Prueba

**Listado básico:**
- `/pokedex` → Página 1 (Pokémon 1-20)
- `/pokedex?page=2` → Página 2 (Pokémon 21-40)
- `/pokedex?page=3` → Página 3 (Pokémon 41-60)

**Búsqueda:**
- `/pokedex?search=pikachu` → Búsqueda por nombre
- `/pokedex?search=25` → Búsqueda por ID
- `/pokedex?search=noexiste` → Estado "not found"

**Filtros:**
- `/pokedex?type=fire` → Solo Pokémon de tipo fuego
- `/pokedex?type=water&page=2` → Página 2 de tipo agua
- `/pokedex?type=electric` → Solo Pokémon eléctricos

**Detalle:**
- `/pokedex/pikachu` → Detalle por nombre
- `/pokedex/25` → Detalle por ID
- `/pokedex/charizard` → Detalle de Charizard

---

## 10. Mejoras Implementadas y Futuras

### 10.1 ✅ Mejoras Implementadas (Funcionalidad Extra)

**Búsqueda y Filtros:**
- ✅ Búsqueda por nombre o ID de Pokémon
- ✅ Filtro por tipo de Pokémon (Fire, Water, Electric, etc.)
- ✅ Botón para limpiar filtros activos
- ✅ Preservación de filtros entre páginas

**UI/UX Mejorada:**
- ✅ Drupal Libraries implementadas (CSS organizado)
- ✅ Tarjetas con gradientes dinámicos según tipos
- ✅ Badges de tipos con colores específicos
- ✅ Botones con diseño Pokéball temático
- ✅ Estados visuales claros con iconos
- ✅ Breadcrumbs de navegación
- ✅ Grid responsive

**Arquitectura:**
- ✅ CSS separado en archivos independientes
- ✅ Libraries configuradas en pokedex.libraries.yml
- ✅ Métodos adicionales en PokeApiService
- ✅ Lógica de búsqueda y filtros en Controller

### 10.2 💡 Mejoras Futuras Pendientes

**Performance:**
- 💡 Cache API para listado y detalle
```php
'#cache' => [
  'max-age' => 300, // 5 minutos
  'contexts' => ['url.query_args:page', 'url.query_args:type'],
  'tags' => ['pokedex:list', 'pokedex:search'],
],
```

**Funcionalidades:**
- 💡 Favoritos del usuario con localStorage/cookies
- 💡 Comparador de Pokémon (lado a lado)
- 💡 Estadísticas visuales (gráficos de stats)
- 💡 Evoluciones del Pokémon
- 💡 Movimientos/ataques disponibles

**Testing:**
- 💡 Tests unitarios con PHPUnit
- 💡 Tests funcionales con Behat
- 💡 Tests de integración de API

**Optimización:**
- 💡 Lazy loading de imágenes

---

## 📌 Conclusión

Este proyecto demuestra:
- ✅ Arquitectura profesional Drupal
- ✅ Patrones de diseño correctos
- ✅ Código limpio y mantenible
- ✅ Manejo robusto de errores
- ✅ Separación de responsabilidades
- ✅ Best practices PHP 8+

**Estado:** ✅ **COMPLETADO Y FUNCIONAL**

---

*Documentación generada para la prueba técnica Pokédex - Drupal 11*
