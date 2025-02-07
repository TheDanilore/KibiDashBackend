# Tienda Virtual KibiDash Backend

API REST que permite con todas la gestion de producto, y usuarios.


---

## Componentes

El proyecto se ha desarrollado utilizando las siguientes tecnologías:

- **PHP 8.2.x**
- **Laravel 11.x**
- **Composer**
- **MySQL** (Base de datos)
- **Docker Compose** (Entorno de contenedores)
- **MailHog** (Gestor de correos para desarrollo)

---

## Preparación del Entorno

El proyecto cuenta con una implementación de Docker Compose para facilitar la configuración del entorno de desarrollo.

> ⚠️ Si no estás familiarizado con Docker, puedes optar por otra configuración para preparar tu entorno. Si decides
> hacerlo, omite los pasos 1.

Instrucciones para iniciar el proyecto

1. Levantar los contenedores con Docker Compose:

```bash
docker compose up -d
```

2. Configurar las variables de entorno:

```bash
cp .env.example .env
```

3. Instalar las dependencias del proyecto:

```bash
composer install
```
En caso salga este error:
![image](https://github.com/user-attachments/assets/a655ea1c-de16-46ca-9f62-db22ef835879)

Acceder a esta ruta: C:\xampp\php\php.ini

Y borrar el ; :
![image](https://github.com/user-attachments/assets/3541f117-83b2-46a0-9cd1-4a703ff3d08b)

Si sale este error:
![image](https://github.com/user-attachments/assets/1bb42e17-3541-4260-bd37-2ec12a9d6d95)

Activar esto:
![image](https://github.com/user-attachments/assets/24c4a6bb-d6a7-4ea9-a588-f37c05f0c926)


4. Generar una clave para la aplicación:

```bash
php artisan key:generate
```

5. Ejecutar las migraciones de la base de datos:

```bash
php artisan migrate
```

**¡Y listo!** Ahora puedes empezar a desarrollar.

## Uso

La API estará disponible en: http://localhost:8080/api/

### (Opcional) Verificar correos en MailHog

MailHog está configurado para capturar los correos enviados en desarrollo. Accede a: [http://localhost:8025](http://localhost:8025).

### Endpoints Disponibles

#### 1. Autenticación

- **POST /api/login**: Inicia sesión y devuelve un token JWT.
- **POST /api/register**: Registra un nuevo usuario.
- **POST /api/logout**: Salir Sesión del usuario.

#### 2. GESTION DE PRODUCTOS (productos, proveedores, unidades de medida, ubicaciones, categoria producto)

- **POST /api/productos**: Permite registrar un nuevo producto
- **GET /api/productos**: Permite listar los producto
- **GET /api/productos/{id}**: Permite obtener un producto
- **PUT /api/productos**: Permite actualizar un producto
- **DELETE /api/productos**: Permite eliminar un producto

#### 3. GESTION DE USUARIOS

- **POST /api/usuarios**: Permite registrar un nuevo usuario
- **GET /api/usuarios**: Permite listar los usuarios
- **GET /api/usuarios/{id}**: Permite obtener un usuario
- **PUT /api/usuarios**: Permite actualizar un usuario
- **DELETE /api/usuarios**: Permite eliminar un usuario


#### 4. GESTION DE UNIDADES DE INVENTARIO (colores, longitudes,tamaño, variaciones, inventario)

**Autor**: [TheDanilore].

