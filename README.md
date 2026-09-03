# FUNIBER - Portal de Noticias e Innovación Tecnológica (Drupal 11)

[![Drupal Version](https://img.shields.io/badge/Drupal-11.x-blue.svg)](https://www.drupal.org)
[![PHP Version](https://img.shields.io/badge/PHP-8.3%20FPM-777BB4.svg)](https://www.php.net)
[![Database](https://img.shields.io/badge/Database-MariaDB%2010.11-003545.svg)](https://mariadb.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED.svg)](https://www.docker.com)
[![PHPUnit](https://img.shields.io/badge/Tests-PHPUnit-3C9CD7.svg)](https://phpunit.de)
[![PSR-12](https://img.shields.io/badge/Standard-PSR--12%20%2F%20Drupal-green.svg)](https://www.php-fig.org/psr/psr-12/)

Solución completa para la **Prueba Técnica de Desarrollador Web Semi Senior** de **FUNIBER**. La plataforma está construida sobre **Drupal 11**, empaquetada con **Docker & Docker Compose**, con un **Tema Personalizado** responsive diseñado con base en el mockup de Figma (*Modern Digital Newspaper*), y un **Módulo Personalizado** (`funiber_tech_news`) que consume una API REST externa con soporte de caché, manejo de errores y pruebas automatizadas en **PHPUnit**.

---

## 📑 Tabla de Contenidos

1. [Requisitos Previos](#-requisitos-previos)
2. [Instalación Rápida (1 Comando)](#-instalación-rápida-con-docker)
3. [Estructura del Proyecto](#-estructura-del-proyecto)
4. [Actividades Desarrolladas](#-actividades-desarrolladas)
   - [Actividad 1: Entorno Docker & Drupal 11](#actividad-1-entorno-docker--drupal-11)
   - [Actividad 2: Tema Personalizado (`funiber_theme`)](#actividad-2-tema-personalizado-funiber_theme)
   - [Actividad 3: Módulo Personalizado (`funiber_tech_news`)](#actividad-3-módulo-personalizado-funiber_tech_news)
   - [Actividad 4: Integración Comunitaria & Configuración](#actividad-4-integración-comunitaria--configuración)
5. [Pruebas Automatizadas (PHPUnit)](#-pruebas-automatizadas-phpunit)
6. [Estándares de Código (PSR-12 / PHPCS)](#-estándares-de-código-psr-12--phpcs)
7. [Comandos Útiles (Makefile & Drush)](#-comandos-útiles-makefile--drush)
8. [Decisiones Técnicas](#-decisiones-técnicas)

---

## 💻 Requisitos Previos

- **Docker** (versión 24.0 o superior)
- **Docker Compose** (versión v2.20 o superior)
- **Git**

---

## ⚡ Instalación Rápida con Docker

### Opción A: Con Makefile (Recomendado)

```bash
# 1. Clonar el repositorio
git clone <URL_DEL_REPOSITORIO>
cd "Prueba tecnica"

# 2. Levantar el entorno de contenedores
make up

# 3. Ejecutar la instalación automática de dependencias y Drupal
make install
```

### Opción B: Con comandos Docker Compose tradicionales

```bash
# 1. Iniciar contenedores en segundo plano
docker compose up -d

# 2. Instalar dependencias con Composer
docker compose exec -u www-data drupal composer install

# 3. Instalar Drupal y activar componentes con Drush
docker compose exec -u www-data drupal drush site:install standard \
  --db-url=mysql://drupal:drupal_secret_pass@database:3306/drupal11 \
  --site-name="FUNIBER News Portal" \
  --account-name=admin \
  --account-pass=admin1234 \
  --account-mail=admin@funiber.org -y

docker compose exec -u www-data drupal drush theme:enable funiber_theme -y
docker compose exec -u www-data drupal drush config:set system.theme default funiber_theme -y
docker compose exec -u www-data drupal drush pm:enable funiber_tech_news -y
docker compose exec -u www-data drupal drush cr
```

### 🌐 Accesos y Credenciales

- **URL del Sitio:** [http://localhost:8080](http://localhost:8080)
- **Página de Tech News API:** [http://localhost:8080/noticias-tech](http://localhost:8080/noticias-tech)
- **Usuario Administrador:** `admin`
- **Contraseña:** `admin1234`
- **Base de Datos:** MariaDB en `localhost:3306` (Usuario: `drupal` / Pass: `drupal_secret_pass` / DB: `drupal11`)

---

## 📁 Estructura del Proyecto

```text
├── docker/
│   ├── nginx/
│   │   └── default.conf            # Servidor web Nginx optimizado para Drupal 11
│   └── php/
│       ├── Dockerfile              # Imagen PHP 8.3 FPM con extensiones gd, opcache, pdo_mysql, intl
│       └── php.ini                 # Configuración de memoria, subidas y OPcache
├── config/sync/                    # Configuraciones exportadas en formato YAML
├── web/
│   ├── modules/custom/
│   │   └── funiber_tech_news/      # MÓDULO PERSONALIZADO (Consumo de API REST, Caché, Bloque, Controller)
│   │       ├── src/
│   │       │   ├── Controller/     # TechNewsController (/noticias-tech)
│   │       │   ├── Plugin/Block/   # TechNewsBlock (Bloque configurable)
│   │       │   └── Service/        # TechNewsApiService (Guzzle + Cache API + Fallbacks)
│   │       ├── templates/          # Plantillas Twig modulares
│   │       ├── css/ & js/          # Estilos y micro-interacciones
│   │       └── tests/src/Unit/     # Pruebas unitarias en PHPUnit
│   ├── themes/custom/
│   │   └── funiber_theme/          # TEMA PERSONALIZADO (Figma Modern Digital Newspaper)
│   │       ├── css/                # Variables CSS, Reset, Grid Layout, Components, Responsive
│   │       ├── js/                 # Menú accesible móvil, búsqueda interactiva
│   │       ├── templates/          # html.html.twig, page.html.twig, block, menu, node
│   │       └── funiber_theme.theme # Preprocess hooks
│   └── sites/default/
│       └── settings.php            # Conexión a BD, hash salt, trusted hosts
├── docker-compose.yml              # Orquestación de servicios (drupal, webserver, database)
├── composer.json                   # Gestión de dependencias Drupal 11
├── phpunit.xml                     # Configuración de suite de pruebas
├── Makefile                        # Atajos de automatización (up, down, install, test, lint)
├── DECISIONES_TECNICAS.md          # Justificación de arquitectura y decisiones
└── README.md                       # Este documento
```

---

## 🎯 Actividades Desarrolladas

### Actividad 1: Entorno Docker & Drupal 11
- Arquitectura desacoplada en 3 contenedores: `webserver` (Nginx), `drupal` (PHP 8.3 FPM) y `database` (MariaDB 10.11).
- Healthcheck activo en la base de datos para garantizar dependencias secuenciales limpias.
- Volumen persistente para datos de la base de datos (`db_data`) y mapeo en vivo de la raíz del proyecto.

### Actividad 2: Tema Personalizado (`funiber_theme`)
Fiel reproducción del diseño de **Figma**:
- **Barra Superior**: Fecha formateada dinámica, enlaces institucionales y redes sociales.
- **Header Principal**: Logotipo "Local News", buscador integrado y botón de suscripción.
- **Navegación Primaria**: Menú responsive con indicador de noticias de última hora (*Breaking News*) y enlace a la API.
- **Hero Banner**: Portada de alto impacto con overlay gradiente y badge de categoría.
- **Top Stories**: Grid de 4 columnas con tarjetas de noticias, imágenes optimizadas y timestamps.
- **Feed Principal (2 Columnas)**: Artículos con extracto + Sidebar con widgets de *Trending Now*, *Most Read* y anuncio institucional de FUNIBER.
- **Programas Académicos**: Componente dedicado para maestrías y especializaciones de FUNIBER.
- **Category Spotlights**: Destacados temáticos (Crimen, Deportes, Negocios).
- **Caja de Suscripción**: Newsletter interactivo integrado.
- **Pie de Página**: 4 columnas con enlaces institucionales, contacto, sedes y copyright.

### Actividad 3: Módulo Personalizado (`funiber_tech_news`)
- **Servicio `TechNewsApiService`**:
  - Consumo de API REST pública mediante **Guzzle HTTP Client**.
  - Almacenamiento en caché mediante el servicio `cache.default` de Drupal con TTL configurable (1 hora) y etiqueta de caché (`funiber_tech_news:articles`) para invalidación granular.
  - Normalización de campos: Título, resumen, imagen destacada, fecha formateada, tiempo de lectura, autor y enlace a la fuente original.
  - Manejo de excepciones con `\GuzzleHttp\Exception\GuzzleException`, registro en el log de Drupal (`logger.channel.funiber_tech_news`) y mecanismo de *fallback* para garantizar alta disponibilidad ante fallos de red.
- **Plugin de Bloque `TechNewsBlock`**:
  - Configurable desde la administración de bloques de Drupal (número de noticias, categoría/tag, título del bloque).
- **Controlador y Ruta `TechNewsController`**:
  - Ruta accesible en `/noticias-tech` con pestañas de filtrado rápido por categoría (*AI, DevOps, WebDev, Ciberseguridad*).

### Actividad 4: Integración Comunitaria & Configuración
- Estructura preparada para módulos comunitarios de carrusel/bloques como **Swiper / Slick / Paragraphs**.
- Exportación completa del estado del sitio en archivos **YAML** dentro de `config/sync/`.

---

## Pruebas Automatizadas (PHPUnit)

Se incluye una suite de pruebas unitarias que evalúa el servicio `TechNewsApiService`:

```bash
# Ejecutar pruebas dentro del contenedor
make test

# O directamente vía Docker Compose:
docker compose exec -u www-data drupal vendor/bin/phpunit web/modules/custom/funiber_tech_news/tests
```

### Casos de prueba cubiertos:
1. `testSuccessfulApiFetchAndNormalization`: Valida la conexión HTTP exitosa, la normalización correcta de los campos y el guardado en la Caché API de Drupal.
2. `testCachedResponseReturned`: Valida que ante un *cache hit*, la información se entrega de inmediato sin realizar llamadas HTTP externas.
3. `testFallbackOnGuzzleException`: Valida que ante caídas o timeouts de la API externa, el servicio captura la excepción, registra el log y devuelve datos de fallback sin interrumpir la experiencia del usuario.

---

## Estándares de Código (PSR-12 / PHPCS)

El código ha sido escrito siguiendo estrictamente los estándares **PSR-12** y las directrices de código de Drupal:

```bash
# Ejecutar verificación de estándares
make lint
```

---

## 🛠️ Comandos Útiles (Makefile & Drush)

| Comando | Descripción |
| :--- | :--- |
| `make up` | Inicia todos los contenedores en segundo plano |
| `make down` | Detiene y remueve los contenedores |
| `make restart` | Reinicia todos los servicios |
| `make logs` | Visualiza los logs en tiempo real |
| `make sh` | Accede a la terminal interactiva de Drupal (`www-data`) |
| `make cr` | Limpia todas las cachés de Drupal (`drush cr`) |
| `make test` | Ejecuta la suite de pruebas unitarias en PHPUnit |
| `make lint` | Ejecuta el análisis de estándares de código (PHPCS) |
| `make drush cmd="..."` | Ejecuta cualquier comando Drush (ej: `make drush cmd="status"`) |

---

## Galería y Demostración Visual

A continuación se muestran capturas reales del portal desarrollado y en ejecución:

### 1. Encabezado Institucional y Banner Principal (Breaking News)
![Encabezado y Hero](docs/screenshots/01_header_hero.png)

### 2. Módulo Personalizado: `funiber_tech_news` (Consumo de API REST en Vivo con Filtros)
![Módulo Tech News](docs/screenshots/02_tech_news_api.png)

### 3. Sección de Programas Académicos FUNIBER
![Programas Académicos](docs/screenshots/03_academic_programs.png)

---

## 📖 Decisiones Técnicas

Para una explicación detallada de los patrones de diseño, decisiones arquitectónicas, estrategias de resiliencia y consideraciones de seguridad aplicadas, consulta el documento [DECISIONES_TECNICAS.md](file:///Users/jacobcastro/Documents/Prueba%20tecnica/DECISIONES_TECNICAS.md).

