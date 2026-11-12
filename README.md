# IntegraCheck

IntegraCheck es un panel de monitoreo de **integraciones de patentes vehiculares en Chile**. Reúne en un solo lugar el estado de cada conexión (origen → destino), el último envío, las patentes asociadas y los chequeos de salud. Está pensado para un contexto municipal o intermunicipal: ERP de patentes, tesorería comunal y registro de vehículos, con zona horaria `America/Santiago` e interfaz en español de Chile.

## Qué problema resuelve

En Chile una municipalidad suele enviar patentes a más de un sistema (tesorería, registro nacional, plataformas regionales). Si una de esas integraciones se atrasa o falla, cuesta notarlo a tiempo. IntegraCheck muestra un semáforo, alertas y el historial de envíos para ver qué conexión está sana y cuál no, sin entrar a cada sistema destino.

**Esta versión es una maqueta de demostración:** usa datos ficticios chilenos (patentes tipo BBCL12, titulares y SpA) y **no llama a APIs reales**. El modelo, las pantallas y el servicio de salud quedan listos para conectar endpoints verdaderos más adelante.

## Funciones de esta maqueta

- **Semáforo de salud** (`HealthStatusService`), según el último envío:
  - **Rojo:** no hay envíos, el último falló, el atraso es mayor al doble de `frecuencia_minutos`, o la tasa de error del último envío es mayor a 10 %.
  - **Amarillo:** el último envío fue parcial, el atraso es mayor a una frecuencia, o la tasa de error está entre 2 % y 10 %.
  - **Verde:** el resto.
- **Último envío:** fecha, tiempo relativo en español, estado, código HTTP y registros ok/error.
- **Conteos de patentes por estado:** registradas, enviadas, rechazadas y pendientes.
- **Banner de alertas** en el dashboard: lista las integraciones en amarillo o rojo con su motivo y un enlace al detalle. Si todas están en verde, muestra *Todas las integraciones están operativas*.
- **Verificar ahora:** POST simulado que crea un registro en `chequeos_salud` (tipo `manual`, latencia 120–650 ms). El resultado se deriva del último envío, no de una API: exitoso → ok; parcial → ok con advertencia; fallido o atrasada → falla.
- **Disponibilidad 30 días:** porcentaje de envíos exitosos sobre los finalizados (sin contar `en_curso`), con un decimal. Verde ≥ 99 %, amarillo 95–98,9 %, rojo &lt; 95 %.
- **Consulta de integraciones:** listado en el dashboard y detalle por integración (datos, salud, patentes con buscador, historial de envíos y chequeos).
- **Historial de envíos** (`/envios`): tabla paginada filtrable por integración y por estado.
- **Auto-actualización** solo en el dashboard: recarga cada 60 segundos, con hora de última actualización y cuenta regresiva.

En esta maqueta **no hay** alta/edición/baja de integraciones (CRUD completo), ni ficha individual de un envío (`/envios/{id}`), ni exportación a CSV. Esas piezas de producto quedan fuera del alcance actual.

## Requisitos

- [Laragon](https://laragon.org/) con **PHP 8.2 o superior** y **MySQL**
- Composer

## Instalación

### 1. Ubicar el proyecto

```text
C:\laragon\www\integracheck
```

### 2. Crear la base `integracheck`

En HeidiSQL, phpMyAdmin o consola:

```sql
CREATE DATABASE IF NOT EXISTS integracheck
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

O desde terminal (usuario `root` sin contraseña, típico de Laragon):

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS integracheck CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Configurar el `.env`

```bash
copy .env.example .env
php artisan key:generate
```

Valores mínimos:

```env
APP_NAME=IntegraCheck
APP_TIMEZONE=America/Santiago
APP_URL=http://integracheck.test
APP_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=integracheck
DB_USERNAME=root
DB_PASSWORD=
```

Si usas `php artisan serve`, puedes dejar `APP_URL=http://127.0.0.1:8000`. Si MySQL tiene contraseña, cárgala en `DB_PASSWORD`.

### 4. Instalar dependencias y cargar datos

```bash
composer install
php artisan migrate --seed
```

Para recrear todo desde cero en local:

```bash
php artisan migrate:fresh --seed
```

`migrate:fresh` borra las tablas. En la maqueta local es lo esperado.

El `DemoSeeder` carga 3 integraciones, envíos de los últimos 30 días, 300 patentes y chequeos de salud ficticios.

### 5. Levantar el proyecto

**Opción A — Artisan**

```bash
php artisan serve
```

Abre [http://127.0.0.1:8000](http://127.0.0.1:8000).

**Opción B — Host virtual de Laragon (`integracheck.test`)**

1. Start All en Laragon.
2. Apunta el document root del sitio a la carpeta `public`.
3. Abre [http://integracheck.test](http://integracheck.test).

No hace falta `npm` ni Vite: Bootstrap 5, íconos y Chart.js van por CDN.

## Rutas

| Método | Ruta | Qué hace |
|---|---|---|
| GET | `/` | Dashboard: semáforo, alertas, KPIs y gráficos |
| GET | `/integraciones/{id}` | Detalle de una integración |
| POST | `/integraciones/{id}/verificar` | Chequeo simulado “Verificar ahora” |
| GET | `/envios` | Historial filtrable de envíos |

## Fase 2 (fuera de esta maqueta)

- Conexión a **endpoints reales** de los sistemas destino
- Chequeos automáticos con **Scheduler cada 5 minutos**
- **Aviso por correo** ante caídas
- **Login con roles** (quién ve el panel y quién puede verificar)

Hasta entonces, IntegraCheck es solo una maqueta para demostrar el monitoreo de integraciones de patentes en Chile, no un conector en producción.
