# LifeQuest - cambio de nombre

He actualizado el proyecto para que la identidad visible sea **LifeQuest**.

## Qué se ha cambiado

- Nombre visible de la app: `LifeQuest`.
- Logo/textos principales en las vistas.
- Documentación principal.
- `APP_NAME` en `config/config.php`.
- `APP_URL` a `http://localhost/LifeQuest/public`.
- Nombre de sesión a `lifequest_session`.

## Qué NO se ha cambiado para no romper tu instalación actual

He mantenido la base de datos actual tal como estaba:

```php
define('DB_NAME', 'questboard');
define('DB_USER', 'questboard');
define('DB_PASS', '159159159');
```

Así puedes copiar estos archivos sobre tu proyecto actual sin tener que recrear la base de datos ni perder datos.

## Si quieres renombrar también la base de datos

Más adelante podemos hacer una migración limpia de:

```text
questboard → lifequest
```

pero no es obligatorio para el TFG. El usuario nunca ve el nombre interno de la base de datos.
