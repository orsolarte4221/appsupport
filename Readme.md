# Support Assignment System (Symfony 7 - PHP 8.3)

Aplicación web REST en Symfony 7 para registrar solicitudes de soporte técnico y asignarlas automáticamente al trabajador con menor carga diaria.

Estado: estable en entorno local con SQLite.

## Tecnologías
- PHP 8.3
- Symfony 7.3
- Doctrine ORM 3.x
- Doctrine Migrations
- Doctrine Fixtures (dev/test)
- Base de datos: SQLite (var/data.db)
- Servidor: Symfony CLI

## Requisitos
- PHP 8.3 con extensiones típicas de Symfony
- Composer
- Symfony CLI
- SQLite3 (cliente) opcional para inspección

## Estructura del proyecto
```
src/
├── Controller/
│   ├── SupportController.php
│   └── WorkerController.php
├── Entity/
│   ├── Worker.php
│   └── Support.php
├── Repository/
│   ├── WorkerRepository.php
│   └── SupportRepository.php
├── Service/
│   └── SupportAssigner.php
└── DataFixtures/
    └── WorkerFixtures.php
```

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

## Lógica de negocio
- Trabajadores fijos: Carolina, Felipe, Camila (cargados por fixtures).
- Cada soporte tiene descripción, complejidad (10, 20, 30), fecha/hora de asignación y trabajador asignado.
- Asignación automática:
  - Se elige el trabajador con menor carga acumulada del día (suma de complejidades de soportes asignados ese día).
  - Desempate alfabético por nombre del trabajador.
  - El día se calcula en la zona horaria America/Bogota.

## Entidades
- Worker
  - id (int, PK)
  - name (string)
  - supports (OneToMany → Support)
  - dailyLoad(DateTimeInterface $date): int — suma dinamicamente la complejidad en esa fecha (America/Bogota).
- Support
  - id (int, PK)
  - description (text, NotBlank)
  - complexity (int en {10,20,30})
  - assignedAt (datetime_immutable, nullable)
  - worker (ManyToOne → Worker, onDelete SET NULL)

## Servicios
- SupportAssigner
  - Evalúa carga diaria de cada trabajador para hoy (America/Bogota).
  - Desempata alfabéticamente.
  - Asigna worker y assignedAt y persiste.

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
- Respuesta 200:
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

## Desarrollo y mantenimiento
- Generar nueva migración al cambiar entidades:
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate -n
```
- Cargar fixtures de nuevo:
```bash
php bin/console doctrine:fixtures:load -n
```
- Registro y logs: usar el Web Profiler en dev.

## Mejoras futuras
- Agregar pruebas de integración para los endpoints y la lógica de asignación.
- Optimizar dailyLoad con consultas agregadas si el volumen crece.
- Añadir paginación en listado de soportes.

## Licencia
Uso académico/demostrativo.