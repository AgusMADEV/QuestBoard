# QA Rapido Metas-Retos-Misiones (15 minutos)

Objetivo: validar que el progreso de metas, retos y misiones se recalcula bien en todos los flujos comunes y no queda en 100% incorrecto.

## 0) Precondiciones (1 min)

- Iniciar sesion con un usuario de pruebas.
- Ir a Metas y borrar datos de prueba anteriores (o usar nombres nuevos).
- Confirmar que Inicio muestra "No hay misiones para hoy" cuando no hay misiones con fecha/creacion de hoy.

Resultado esperado:
- Entorno limpio para pruebas.
- Sin errores fatales ni pantallas en blanco.

## 1) Caso base de jerarquia (2 min)

- Crear Meta A.
- Crear Reto A1 dentro de Meta A.
- Crear Mision A1.1 dentro de Reto A1.
- Completar Mision A1.1.

Resultado esperado:
- Reto A1 sube de progreso de forma coherente.
- Meta A sube de progreso de forma coherente.
- Si solo existe A1.1, Reto A1 y Meta A pueden quedar en 100%.

## 2) Alta de nuevo reto pendiente (2 min)

- Crear Reto A2 dentro de Meta A.
- No completar misiones de A2.

Resultado esperado:
- Meta A deja de estar en 100%.
- El porcentaje baja al valor real (promedio por retos activos/no cancelados).

## 3) Crear mision en reto nuevo (2 min)

- Crear Mision A2.1 en Reto A2 con estado pendiente.

Resultado esperado:
- Progreso de A2 se recalcula.
- Progreso de Meta A se recalcula automaticamente.

## 4) Completar y descompletar via edicion (2 min)

- Marcar A2.1 como completada.
- Editar A2.1 y pasar estado a pendiente o en progreso.

Resultado esperado:
- Al completar, suben A2 y Meta A.
- Al volver a pendiente/en progreso, bajan A2 y Meta A.

## 5) Mover mision entre retos (2 min)

- Crear Reto A3 en Meta A.
- Editar A2.1 y moverla de A2 a A3.

Resultado esperado:
- Se recalcula A2 (origen).
- Se recalcula A3 (destino).
- Se recalcula Meta A.

## 6) Eliminar mision y eliminar reto (2 min)

- Eliminar una mision completada.
- Eliminar un reto con progreso parcial.

Resultado esperado:
- Al eliminar mision, se recalculan reto y meta asociados.
- Al eliminar reto, se recalcula la meta asociada.

## 7) Estados no contables (1 min)

- Poner una mision en estado cancelled.

Resultado esperado:
- La mision cancelada no cuenta en el progreso.
- No deja progresos inflados.

## 8) Verificacion cruzada de vistas (1 min)

- Revisar porcentajes en Metas (section goals).
- Revisar porcentajes en Retos (section projects).
- Revisar bloque "Misiones de hoy" en Inicio.

Resultado esperado:
- Valores consistentes entre vistas.
- Inicio solo muestra misiones de hoy (por due_date o created_at del dia actual).

## 9) Criterio de salida

Aprobado si:
- No hay errores fatales.
- No hay metas en 100% incorrecto tras agregar retos pendientes.
- Progreso se recalcula en create/update/delete de misiones y retos.
- Inicio no muestra misiones fuera del dia actual.

## 10) Si algo falla

- Capturar: paso, accion, porcentaje esperado, porcentaje real, y hora.
- Adjuntar captura de Metas/Retos/Misiones de hoy.
- Revisar si la mision tenia due_date de hoy o solo created_at.
