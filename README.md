# Modular ERP Core (Multi-Branch Inventory, Sales & Receivables)

Demostración técnica independiente en **PHP 8.3 + Laravel 13** enfocada en arquitectura modular de sistemas ERP: gestión multi-sucursal, permisos granulares por sucursal, conversiones de empaque a unidad base, Kardex inmutable, ventas con listas de precios y cuentas por cobrar con abonos parciales/totales.

> **Nota:** Proyecto demostrativo construido desde cero con datos 100 % sintéticos y sin lógica propietaria ni información de sistemas empresariales reales.

## 1. Problema que resuelve
Muestra cómo estructurar transacciones críticas de un ERP evitando inconsistencias de stock, ventas por encima del inventario disponible, sobrepagos en cuentas por cobrar y fugas de autorización entre sucursales.

## 2. Tecnologías utilizadas
- **Backend:** PHP `^8.3`, Laravel `^13`, Eloquent ORM, SQLite.
- **Pruebas:** PHPUnit `^12.5` (Unit + Feature sobre SQLite en memoria).

## 3. Arquitectura general
- `app/Services/Erp/InventoryConversionService.php`: Conversión de empaques (`pack_factor`) a unidades base.
- `app/Services/Erp/InventoryKardexService.php`: Ajustes y traslados entre sucursales con `lockForUpdate()`, rechazo de saldos negativos y bitácora de movimientos (`stock_movements`).
- `app/Services/Erp/SalesAndReceivablesService.php`: Facturación (`retail` / `wholesale`, contado / crédito) y cobranza controlada por abonos (`open` → `partial` → `paid`).
- `app/Http/Middleware/RequireBranchPermission.php`: Aislamiento de permisos por sucursal y acción (`inventory.transfer`, `sales.create`).

## 4. Funcionalidades implementadas
- `POST /api/v1/erp/transfers`: Traslados multi-sucursal con conversión de unidad y Kardex.
- `POST /api/v1/erp/sales`: Ventas con descuento atómico de stock y apertura automática de cuenta por cobrar a crédito.
- `POST /api/v1/erp/receivables/{receivable}/collect`: Registro de abonos con protección contra sobrepago.
- Panel web `/` con resumen de sucursales, facturas, cuentas abiertas y existencias.

## 5. Instrucciones para ejecutarlo

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## 6. Pruebas disponibles
```bash
php artisan test
```
Cubre conversiones de unidades, traslados con Kardex, aislamiento de permisos por sucursal, ventas con listas de precios y liquidación de cuentas por cobrar.

## 7. Contribución real al desarrollo
Arquitectura de dominio ERP diseñada por Carlos Ariel Villegas Flores como demostración técnica independiente, con asistencia de herramientas de IA para generar el scaffolding Laravel y batería PHPUnit verificada localmente.
