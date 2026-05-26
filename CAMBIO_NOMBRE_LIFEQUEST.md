# LifeQuest - cambio de nombre

He actualizado el proyecto para que la identidad visible sea **LifeQuest**.

## Qué se ha cambiado

- Nombre visible de la app: `LifeQuest`.
- Logo/textos principales en las vistas.
- Documentación principal.
- `APP_NAME` en `config/config.php`.
- `APP_URL` a `http://localhost/LifeQuest/public`.
- Nombre de sesión a `lifequest_session`.

## Estado actual del repositorio

Actualmente el proyecto está configurado por defecto para usar:

```php
define('DB_NAME', 'lifequest');
```

Esto está alineado con `database/schema.sql`.

Si vienes de una instalación antigua con `questboard`, solo cambia en tu `config/config.php`:

```php
define('DB_NAME', 'questboard');
define('DB_USER', 'tu_usuario_real');
define('DB_PASS', 'tu_contraseña_real');
```

## Si quieres renombrar también la base de datos

Puedes hacer una migración limpia de:

```text
questboard → lifequest
```

pero no es obligatorio para el TFG. El usuario nunca ve el nombre interno de la base de datos.
