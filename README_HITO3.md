# PRO413 — Gestión Escolar (Hito 3)

Guía para ejecutar el proyecto en Windows con Docker Desktop y Visual Studio Code.

## 1. Requisitos

- Windows 10/11.
- Docker Desktop instalado, abierto y en estado **Running**.
- Visual Studio Code.
- Conexión a Internet para descargar las imágenes y dependencias la primera vez.
- Para conectar con la base de datos institucional/docente: acceso a la red o VPN correspondiente y credenciales válidas entregadas por el docente.

> La aplicación local y la base de datos local pueden iniciarse sin conexión al servidor docente. Sin embargo, las funciones que consultan la segunda base no estarán disponibles hasta que ese servidor sea accesible y las credenciales sean correctas.

## 2. Abrir el proyecto

1. Descarga y descomprime `PRO413_HITO3_LISTO_PARA_EJECUTAR.zip`.
2. En Visual Studio Code, selecciona **Archivo → Abrir carpeta**.
3. Abre la carpeta `PRO413_2_2026_HITO3_KERNELHITOS-main`, es decir, la carpeta donde se encuentran `artisan`, `composer.json`, `Dockerfile` y `docker-compose.yml`.
4. Abre **Terminal → Nueva terminal**. Los comandos siguientes están escritos para PowerShell.

## 3. Preparar las variables de entorno

Si aún no existe `.env`, ejecuta en la terminal, desde la carpeta del proyecto:

```powershell
Copy-Item .env.example .env
```

Si `.env` ya existe, no lo sobrescribas.

Abre `.env` en VS Code y cambia `MYSQL_ROOT_PASSWORD` por una contraseña propia. Usa la misma contraseña en `DB_PASSWORD`, ya que la configuración Docker del proyecto usa el usuario `root` para la base local.

Revisa también estas variables de la base de datos docente y reemplaza los valores de ejemplo por los datos reales proporcionados por el docente:

```dotenv
DOCENTE_DB_HOST=10.0.47.204
DOCENTE_DB_PORT=3306
DOCENTE_DB_DATABASE=gestion_docente
DOCENTE_DB_USERNAME=USUARIO_PROPORCIONADO_POR_DOCENTE
DOCENTE_DB_PASSWORD=CLAVE_PROPORCIONADA_POR_DOCENTE
```

No subas ni compartas el archivo `.env`: contiene contraseñas. La dirección `10.0.47.204` es una dirección privada y puede requerir conexión a la red/VPN institucional.

## 4. Validar e iniciar Docker

Primero valida la configuración:

```powershell
docker compose config
```

Si no aparecen errores, construye e inicia los servicios:

```powershell
docker compose up -d --build
```

Comprueba su estado:

```powershell
docker compose ps
```

La configuración del proyecto espera dos servicios principales:

- `gestion_escolar_mysql`: MySQL local, publicado en el puerto **3307** de Windows.
- `gestion_escolar_app`: aplicación Laravel, publicada en el puerto **8000**.

La primera construcción puede tardar varios minutos.

## 5. Configurar la clave de Laravel

Genera una clave con:

```powershell
docker compose run --rm app php artisan key:generate --show
```

Copia el valor completo que imprime (comienza con `base64:`) y pégalo en el archivo `.env`, en la variable `APP_KEY`, por ejemplo:

```dotenv
APP_KEY=base64:PEGA_AQUI_LA_CLAVE_GENERADA
```

No uses el texto de ejemplo literalmente. Guarda `.env` y reinicia los servicios para que tomen la configuración:

```powershell
docker compose up -d
```

## 6. Abrir la aplicación

En el navegador, visita:

**http://localhost:8000**

Si la página no abre, consulta los registros:

```powershell
docker compose ps
docker compose logs --tail=100 app
docker compose logs --tail=100 mysql
```

## 7. Inicializar y comprobar las bases de datos

La base local usa el archivo `sql/01_bd_docker_estudiantes.sql`. Docker lo ejecuta automáticamente al crear por primera vez el volumen de MySQL.

Si la pantalla de la aplicación ofrece la opción **“Inicializar Tablas SQL en Ambos Servidores”**, úsala solo después de revisar la configuración y confirmar que las credenciales de ambos servidores son correctas.

El archivo `sql/02_bd_docente_institucional.sql` corresponde al servidor institucional/docente. No lo ejecutes en una base compartida sin autorización expresa del docente.

Para conectarte a MySQL local desde una herramienta instalada en Windows, los datos son:

- Host: `127.0.0.1`
- Puerto: `3307`
- Base de datos: el valor `DB_DATABASE` de `.env` (por defecto `gestion_escolar`)
- Usuario: `root`
- Contraseña: el valor `MYSQL_ROOT_PASSWORD` de `.env`

**Importante:** una página web abierta no confirma por sí sola que la segunda base de datos esté conectada. Comprueba el estado y los mensajes de la aplicación para verificar cada conexión.

## 8. Comandos útiles

Ver estado de contenedores:

```powershell
docker compose ps
```

Ver registros de la aplicación:

```powershell
docker compose logs -f app
```

Ver registros de MySQL:

```powershell
docker compose logs -f mysql
```

Reiniciar servicios:

```powershell
docker compose restart
```

Detener servicios sin borrar los datos:

```powershell
docker compose down
```

Detener servicios y borrar también los datos de MySQL local:

```powershell
docker compose down -v
```

> **Advertencia:** `docker compose down -v` elimina el volumen de MySQL local y, con él, los datos guardados. No lo ejecutes si necesitas conservarlos.

## 9. Problemas frecuentes

### `docker compose` muestra un error

- Confirma que Docker Desktop esté abierto y en estado **Running**.
- Confirma que la terminal esté en la carpeta donde está `docker-compose.yml`.
- Ejecuta `docker compose config` y revisa el mensaje de error.

### El puerto 8000 o 3307 está ocupado

Otro programa está utilizando el puerto. Cierra ese programa o cambia el mapeo de puertos en `docker-compose.yml` y vuelve a iniciar los servicios.

### La aplicación abre, pero falla la conexión con MySQL local

Comprueba `docker compose ps`, `docker compose logs --tail=100 mysql` y que `DB_DATABASE` y `MYSQL_ROOT_PASSWORD` coincidan con los valores de `.env`. Si modificaste la contraseña después de crear el volumen, la contraseña almacenada en MySQL puede no cambiar automáticamente.

### La conexión al servidor docente falla

Confirma con el docente la IP/host, el nombre de la base, el usuario, la contraseña y si debes conectarte mediante la red o VPN institucional. No publiques credenciales en capturas ni mensajes.

### Cambié el SQL local, pero no se aplicaron los cambios

Los scripts de `/docker-entrypoint-initdb.d` se ejecutan cuando MySQL inicializa un volumen vacío, no en cada reinicio. Aplica el SQL manualmente o, si puedes perder los datos locales, recrea el volumen con `docker compose down -v` y luego `docker compose up -d --build`.

## 10. Alcance de esta guía

Esta guía documenta los pasos de ejecución previstos por los archivos Docker del proyecto. El resultado depende de la configuración de tu PC, de las credenciales reales y de la disponibilidad de la red institucional. Si un comando falla, comparte el mensaje de error completo, ocultando contraseñas y otros datos privados.
