# Support Assignment System (Symfony 7 - PHP 8.3)

Aplicación web REST en Symfony 7 para registrar solicitudes de soporte técnico y asignarlas automáticamente al trabajador con menor carga diaria.

Estado: estable en entorno local con SQLite.

Nota de migración: la aplicación pasó de una arquitectura MVC tradicional a una Arquitectura Hexagonal (Ports & Adapters).

## Tecnologías
- PHP 8.3
- Symfony 7.3
- Doctrine ORM 3.x
- Doctrine Migrations
- Doctrine Fixtures (dev/test)
- Base de datos: SQLite (var/data.db)
- Servidor: Symfony CLI

## Arquitectura Hexagonal (Ports & Adapters)
La lógica de negocio está aislada en el Dominio; la aplicación orquesta casos de uso; y las dependencias externas (base de datos, reloj del sistema, etc.) se integran a través de puertos y adaptadores.

- Dominio: modelos, servicios de dominio y puertos (interfaces).
- Aplicación: casos de uso (comandos/queries + handlers) que coordinan el flujo.
- Infraestructura: adaptadores concretos (Doctrine, reloj del sistema, mapeadores).
- Entradas (Primary Adapters): controladores HTTP que exponen endpoints REST.

## Estructura del proyecto
```
src/
├── Domain/
│   ├── Model/
│   │   ├── Support.php
│   │   └── Worker.php
│   ├── Port/
│   │   ├── Clock.php
│   │   ├── SupportRepository.php
│   │   └── WorkerRepository.php
│   └── Service/
│       └── SupportAssignmentPolicy.php
├── Application/
│   └── UseCase/
│       ├── CreateSupport/
│       │   ├── CreateSupportCommand.php
│       │   ├── CreateSupportHandler.php
│       │   └── CreateSupportResult.php
│       ├── ListSupports/
│       │   ├── ListSupportsQuery.php
│       │   ├── ListSupportsHandler.php
│       │   └── ListSupportsItem.php
│       └── DailyLoadReport/
│           ├── DailyLoadReportQuery.php
│           ├── DailyLoadReportHandler.php
│           └── DailyLoadReportRow.php
├── Infrastructure/
│   ├── Persistence/
│   │   └── Doctrine/
│   │       ├── Repository/
│   │       │   ├── DoctrineSupportRepository.php
│   │       │   └── DoctrineWorkerRepository.php
│   │       ├── Entity/
│   │       └── Mapper/
│   └── Time/
│       └── SystemClock.php
├── Controller/
│   ├── SupportController.php
│   └── WorkerController.php
└── …
```

Directorios legacy en transición: `src/Entity` y `src/Repository` permanecen presentes, pero la capa de aplicación utiliza los puertos del dominio y los adaptadores Doctrine de `src/Infrastructure`.

## Casos de uso (Application)
- Crear soporte: handler que valida y delega al dominio la política de asignación.
  - Archivos: `src/Application/UseCase/CreateSupport/…`
- Listar soportes: handler que retorna una proyección ligera para lectura.
  - Archivos: `src/Application/UseCase/ListSupports/…`
- Reporte de carga diaria: handler que suma complejidades por trabajador para una fecha dada.
  - Archivos: `src/Application/UseCase/DailyLoadReport/…`

## Puertos (Domain)
- Reloj: `src/Domain/Port/Clock.php`
- Repositorio de Soportes: `src/Domain/Port/SupportRepository.php`
- Repositorio de Trabajadores: `src/Domain/Port/WorkerRepository.php`

## Adaptadores (Infrastructure)
- Persistencia (Doctrine):
  - `src/Infrastructure/Persistence/Doctrine/Repository/DoctrineSupportRepository.php`
  - `src/Infrastructure/Persistence/Doctrine/Repository/DoctrineWorkerRepository.php`
- Tiempo del sistema:
  - `src/Infrastructure/Time/SystemClock.php`

## Controladores (entrada HTTP)
- `src/Controller/SupportController.php`
- `src/Controller/WorkerController.php`

## Lógica de negocio
- Trabajadores fijos: Carolina, Felipe, Camila (cargados por fixtures).
- Cada soporte tiene descripción, complejidad (10, 20, 30), fecha/hora de asignación y trabajador asignado.
- Asignación automática:
  - Se elige el trabajador con menor carga acumulada del día (suma de complejidades de soportes asignados ese día).
  - Desempate alfabético por nombre del trabajador.
  - El día se calcula en la zona horaria America/Bogota.
- La política de asignación es un servicio de dominio puro (`src/Domain/Service/SupportAssignmentPolicy.php`) y recibe abstracciones (puertos) para mantener el dominio aislado.

## Modelo de dominio
- Worker (modelo de dominio): `src/Domain/Model/Worker.php`
- Support (modelo de dominio): `src/Domain/Model/Support.php`

## Endpoints REST (JSON)

1) Crear soporte
- Método: POST
- URL: /api/supports
- Body:
```json
{
  "description": "Problema con red",
  "complexity": 20
}
```
- Respuesta 201:
```json
{
  "id": 5,
  "description": "Problema con red",
  "complexity": 20,
  "assignedAt": "2025-10-27T09:30:00",
  "worker": "Felipe"
}
```

2) Listar soportes
- Método: GET
- URL: /api/supports
- Respuesta 200:
```json
[
  {
    "id": 1,
    "description": "Problema con red",
    "complexity": 20,
    "assignedAt": "2025-10-27T09:30:00",
    "worker": "Felipe"
  }
]
```

3) Reporte de carga diaria
- Método: GET
- URL: /api/report/daily-load?date=YYYY-MM-DD
- Notas:
  - Si no se envía `date`, se usa la fecha actual en zona America/Bogota.
  - Si `date` tiene formato inválido, se responde 400 con mensaje de error.
- Respuesta 200 (ejemplo):
```json
[
  {"worker": "Carolina", "total": 30},
  {"worker": "Felipe", "total": 20},
  {"worker": "Camila", "total": 10}
]
```

## Ejemplos con curl
```bash
curl -s -X POST http://127.0.0.1:8000/api/supports \
  -H 'Content-Type: application/json' \
  -d '{"description":"Problema con red","complexity":20}'

curl -s http://127.0.0.1:8000/api/supports

curl -s "http://127.0.0.1:8000/api/report/daily-load?date=$(date +%F)"
```

## Manejo de zona horaria
- El sistema usa America/Bogota para:
  - Calcular el “día” en dailyLoad.
  - Sellos assignedAt.
- Esto garantiza consistencia con la zona solicitada.

## Validaciones y errores
- description: requerido (NotBlank).
- complexity: debe ser uno de 10, 20, 30.
- Respuestas de error usan JSON con código 400 cuando aplica.

## Configuración rápida
1) Instalar dependencias:
```bash
composer install
```

2) Configurar base de datos SQLite en .env.local:
```env
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

3) Generar y ejecutar migraciones:
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate -n
```

4) Cargar datos iniciales (trabajadores):
```bash
php bin/console doctrine:fixtures:load -n
```

5) Levantar servidor:
```bash
symfony serve -d
```

## Configuración de DI
- El cableado de puertos a adaptadores y casos de uso se declara en `config/services.yaml`.

## Desarrollo y mantenimiento
- Generar nueva migración al cambiar modelos persistidos:
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate -n
```
- Recargar fixtures:
```bash
php bin/console doctrine:fixtures:load -n
```
- Registro y logs: usar el Web Profiler en dev.

## Mejoras futuras
- Agregar pruebas de integración para los endpoints y la lógica de asignación.
- Optimizar el reporte con consultas agregadas si el volumen crece.
- Añadir paginación en listado de soportes.

## Licencia
Uso académico/demostrativo.