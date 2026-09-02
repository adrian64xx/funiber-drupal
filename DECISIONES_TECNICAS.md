# Documento de Decisiones Técnicas - FUNIBER News Portal (Drupal 11)

**Autor:** Candidato a Desarrollador Web Semi Senior  
**Proyecto:** Portal Educativo y de Noticias Tecnológicas FUNIBER  
**Tecnologías:** Drupal 11, PHP 8.3 FPM, MariaDB 10.11, Nginx, Docker, Twig, Guzzle, PHPUnit, CSS3, JS Vanilla  

---

## 1. Introducción y Propósito

El presente documento resume y justifica las principales decisiones técnicas, arquitectónicas y de diseño adoptadas durante el desarrollo de la prueba técnica para **FUNIBER**. El objetivo fue construir una solución de nivel empresarial, altamente mantenible, desacoplada, resiliente y fiel al diseño visual suministrado en Figma.

---

## 2. Decisiones de Infraestructura y Entorno (Docker)

### 2.1. Arquitectura Multi-Contenedor vs. Imagen Monolítica
* **Decisión:** Se optó por una arquitectura desacoplada basada en tres contenedores especializados:
  1. `webserver` (Nginx 1.25 Alpine): Manejo eficiente de peticiones estáticas, compresión Gzip, micro-caching y reescritura de URLs para Drupal.
  2. `drupal` (PHP 8.3-FPM Alpine): Intérprete PHP optimizado con OPcache habilitado y las extensiones estrictamente requeridas por Drupal 11 (`gd`, `pdo_mysql`, `intl`, `mbstring`, `zip`, `opcache`).
  3. `database` (MariaDB 10.11): Motor de base de datos relacional ligero, robusto y con configuración `utf8mb4_unicode_ci` nativa.
* **Justificación:** Separar el servidor web del motor de ejecución PHP permite aislar responsabilidades, optimizar el uso de memoria RAM, mejorar la seguridad y simular con fidelidad un entorno de producción real en la nube.

### 2.2. Healthchecks y Control de Dependencias
* **Decisión:** Se implementó una directiva `healthcheck` en el servicio `database` (`mariadb-admin ping`) y se configuró `depends_on: { database: { condition: service_healthy } }` en el servicio Drupal.
* **Justificación:** Evita condiciones de carrera durante el arranque en frío del entorno, asegurando que Drupal no intente conectarse antes de que MariaDB esté lista para recibir conexiones.

---

## 3. Decisiones en el Tema Personalizado (`funiber_theme`)

### 3.1. Base Theme: `false` (Standalone) vs. Subtheming
* **Decisión:** Se construyó un tema independiente (`base theme: false`), declarando explícitamente sus propias regiones, librerías y plantillas Twig.
* **Justificación:** Permite un control total sobre el DOM renderizado, evitando arrastrar clases heredadas o sobrecargar estilos de temas base como Olivero o Stable9, lo cual garantiza una correspondencia pixel-perfect con el diseño de Figma y un bundle CSS ultraligero.

### 3.2. Sistema de Diseño con CSS Variables y Tokens
* **Decisión:** Centralización de colores (`--color-primary`, `--color-navy`, `--cat-*`), tipografías (`--font-sans`, `--font-serif`), sombras y radios de borde en `variables.css`.
* **Justificación:** Facilita el mantenimiento, permite futuras implementaciones de modo oscuro (*dark mode*) o temas estacionales con una simple modificación de variables, y asegura coherencia visual en todos los componentes.

### 3.3. JavaScript Vanilla con `Drupal.behaviors` y `core/once`
* **Decisión:** Uso exclusivo de JavaScript nativo moderno encapsulado en `Drupal.behaviors` y procesado con la librería `once()` de Drupal.
* **Justificación:** Elimina dependencias pesadas como jQuery para el frontend del usuario, mejora el rendimiento de carga (Core Web Vitals) y garantiza que los eventos se enlacen una sola vez incluso tras actualizaciones AJAX.

---

## 4. Decisiones en el Módulo Personalizado (`funiber_tech_news`)

### 4.1. Patrón de Capas y Servicio Desacoplado (`TechNewsApiService`)
* **Decisión:** La lógica de consumo, normalización y almacenamiento en caché de la API se encapsuló en un servicio independiente inyectable (`funiber_tech_news.api_service`), en lugar de codificarla directamente en controladores o plugins de bloque.
* **Justificación:**
  - **Principio de Responsabilidad Única (SRP):** El bloque y el controlador solo se encargan de la presentación; el servicio gestiona los datos.
  - **Testabilidad:** Permite realizar pruebas unitarias aisladas en PHPUnit utilizando mocks de `ClientInterface` y `CacheBackendInterface`.
  - **Reutilización:** Cualquier otro módulo, comando Drush o API interna puede consumir el mismo servicio.

### 4.2. Estrategia de Almacenamiento en Caché (Drupal Cache API)
* **Decisión:** Cada llamada a la API externa se almacena en el backend de caché por defecto de Drupal (`cache.default`) con:
  - Cache ID granular: `funiber_tech_news:articles:{tag}:{limit}`
  - TTL (Time-To-Live): 3600 segundos (1 hora)
  - Cache Tag: `['funiber_tech_news:articles']`
* **Justificación:** Reduce drásticamente la latencia de respuesta para el usuario final (<10ms vs ~400ms de llamada HTTP), evita alcanzar los límites de tasa (*rate limits*) de las APIs públicas y permite una invalidación selectiva cuando sea necesario.

### 4.3. Resiliencia y Manejo de Errores (Graceful Degradation)
* **Decisión:** Se implementó un bloque `try-catch` capturando `GuzzleException` y `\Exception`, registrando el error en el canal de logs de Drupal (`logger.factory->get('funiber_tech_news')`) y retornando un conjunto de artículos de *fallback* curados en caso de fallo de red.
* **Justificación:** Garantiza que la página de inicio y el bloque nunca se rompan ni muestren pantallas en blanco ante una indisponibilidad temporal de los servidores de la API externa.

### 4.4. Selección de la API Pública
* **Decisión:** Se seleccionó **Dev.to API** (`https://dev.to/api/articles`) por su alta disponibilidad, contenido técnico especializado, soporte de etiquetas por temática (`technology`, `ai`, `devops`, `webdev`, `security`) y respuesta enriquecida con imágenes y tiempos de lectura.

---

## 5. Calidad, Estándares y Pruebas Automatizadas

### 5.1. PHPUnit y Aislamiento de Pruebas
* **Decisión:** Implementación de suite de pruebas unitarias (`TechNewsApiServiceTest.php`) con cobertura de los tres flujos críticos:
  1. Consulta exitosa a la API externa y parseo de datos.
  2. Respuesta inmediata desde la memoria caché ante un *cache hit*.
  3. Comportamiento y recuperación ante caídas o excepciones de red.

### 5.2. Adherencia a PSR-12 y Drupal Coding Standards
* **Decisión:** Tipado estricto de parámetros y retornos (`void`, `array`, `string`, `int`), nombres de clases en PascalCase, métodos en camelCase, y documentación completa en formato PHPDoc.

---

## 6. Conclusión

La solución desarrollada no solo cumple con los requisitos técnicos obligatorios de la prueba, sino que implementa las mejores prácticas del ecosistema Drupal 11 y desarrollo web moderno: rendimiento optimizado, arquitectura desacoplada, diseño responsivo de alto impacto visual y código estructurado para escalar.
