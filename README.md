# LifeQuest

Aplicación web de productividad gamificada en PHP + MySQL para gestionar áreas de vida, metas, retos y misiones.

## Estado del proyecto

Implementado y funcional:

- Autenticación: registro, login, logout y sesiones protegidas.
- Áreas de vida: CRUD completo.
- Metas: CRUD completo con relación opcional a áreas.
- Retos: CRUD completo con relación opcional a metas y áreas.
- Misiones: CRUD completo, marcado como completada, recompensas XP/LifeCoins y actualización de progreso en metas/retos.
- Dashboard: resumen de progreso, metas, retos y misiones.

Planificado (estructura de datos ya preparada en `database/schema.sql`):

- Hábitos
- Modo Batalla
- Tienda y canje de recompensas
- Estadísticas avanzadas
- Zona Peligrosa

## Arquitectura

- `public/`: páginas/rutas principales de la aplicación.
- `app/Controllers/`: validación de entrada y coordinación de lógica.
- `app/Models/`: acceso a base de datos y reglas de persistencia.
- `app/Database/connection.php`: conexión PDO centralizada.
- `database/schema.sql`: esquema completo de la base de datos.
- `assets/css/styles.css`: estilos visuales de la interfaz.

## Configuración rápida

1. Crea la base de datos `lifequest`.
2. Importa `database/schema.sql`.
3. Configura `config/config.php` con tus credenciales reales.
4. Abre `http://localhost/LifeQuest/public`.

Referencia completa en `INSTALL.md`.

## Nomenclatura de dominio

Para mantener coherencia interna y visual:

- Área = `life_areas`
- Meta = `goals`
- Reto = `projects`
- Misión = `tasks`

Esta nomenclatura se usa en base de datos, backend y vistas.