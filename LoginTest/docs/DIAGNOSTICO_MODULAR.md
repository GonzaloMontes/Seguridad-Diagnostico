# Módulo de Diagnóstico - Arquitectura Modular

**Versión:** 2.0  
**Fecha:** 2025-12-09  
**Ubicación:** `/LoginTest/diagnostico_modular/`

---

## 📋 Descripción

Sistema modular de diagnóstico y gestión de seguridad. Permite visualizar, analizar y gestionar IPs bloqueadas, con análisis forense detallado de intentos de ataque.

---

## 🏗️ Arquitectura

### **Estructura de Carpetas**

```
diagnostico_modular/
├── config/              # Configuración
│   ├── conexion.php     # Conexión MySQLi a BD
│   └── constantes.php   # Políticas y constantes
│
├── core/                # Lógica de negocio
│   ├── funciones_seguridad.php  # Funciones de validación
│   └── funciones_bd.php         # Queries y operaciones BD
│
├── api/                 # Endpoints JSON
│   └── detalle_forense.php      # API de análisis forense
│
├── vistas/              # Presentación
│   └── panel_principal.php      # Vista HTML principal
│
├── assets/              # Recursos estáticos
│   ├── css/
│   │   └── diagnostico.css      # Estilos del módulo
│   └── js/
│       └── diagnostico.js       # Lógica JavaScript
│
└── index.php            # Controlador principal (punto de entrada)
```

---

## 🔧 Componentes

### **1. Configuración (`config/`)**

#### `conexion.php`
- Función: `conectar_bd()` - Retorna conexión MySQLi
- Charset: UTF-8
- Zona horaria: America/Argentina/Buenos_Aires

#### `constantes.php`
- `VENTANA_INTENTOS_MINUTOS` - Ventana de tiempo para contar intentos (5 min)
- `MAX_INTENTOS_IP` - Intentos antes de bloquear IP (3)
- `DURACION_BLOQUEO_PERMANENTE` - Duración bloqueo permanente (5 años)

---

### **2. Core (`core/`)**

#### `funciones_seguridad.php`
- `obtener_ip_cliente()` - Obtiene IP real del cliente
- `verificar_bloqueo($conn, $tipo, $identificador)` - Verifica si entidad está bloqueada
- `sanitizar_input($input)` - Sanitiza input de usuario

#### `funciones_bd.php`
- `vincular_parametros($stmt, $tipos, $parametros)` - Bind dinámico MySQLi
- `obtener_ips_bloqueadas($conn, $buscarIp, $fechaDesde, $fechaHasta)` - Lista IPs bloqueadas
- `desbloquear_ip($conn, $ip)` - Desbloquea IP y mueve a historial
- `obtener_historial_bloqueos($conn, $buscar, $fechaDesde, $fechaHasta, $pagina, $porPagina)` - Historial paginado

---

### **3. API (`api/`)**

#### `detalle_forense.php`
**Endpoint:** `/diagnostico_modular/api/detalle_forense.php`

**Parámetros GET:**
- `ip` (requerido) - IP a analizar
- `desde` (opcional) - Fecha desde (YYYY-MM-DD)
- `hasta` (opcional) - Fecha hasta (YYYY-MM-DD)
- `page` (opcional) - Número de página (default: 1)
- `limit` (opcional) - Registros por página (default: 20, max: 100)

**Respuesta JSON:**
```json
{
  "ip": "192.168.1.1",
  "resumen": {
    "motivo_actual": "Actividad sospechosa",
    "bloqueado_desde": "2025-12-09 10:00:00",
    "expira": "2030-12-09 10:00:00"
  },
  "intentos_fallidos": {
    "total": 5,
    "por_usuario": [...],
    "detalle": [...]
  },
  "alertas": [...],
  "historial_bloqueos": [...],
  "entrada": {...}
}
```

---

### **4. Vista (`vistas/`)**

#### `panel_principal.php`
- Tabla de IPs bloqueadas activas
- Filtros por IP y rango de fechas
- Tabla de historial con paginación
- Modal de detalle forense
- Botones de desbloqueo

---

### **5. Assets (`assets/`)**

#### `css/diagnostico.css`
- Estilos del módulo completo
- Sistema de grid responsive
- Componentes: tablas, botones, modales, alertas

#### `js/diagnostico.js`
- `abrirDetalle(ip, soloMotivo, desde, hasta)` - Abre modal con análisis forense
- `cerrarDetalle()` - Cierra modal
- `confirmUnblock(ip)` - Confirma desbloqueo
- `escapeHtml(str)` - Prevención XSS

---

## 🚀 Uso

### **Acceso al Módulo**
```
http://localhost/diagnostico_modular/
```

### **Flujo de Operación**

1. **Visualizar IPs bloqueadas**
   - Acceder a `index.php`
   - Aplicar filtros opcionales
   - Ver tabla de IPs activas

2. **Analizar bloqueo**
   - Click en "Ver detalle"
   - Modal muestra análisis forense completo
   - Incluye intentos, alertas e historial

3. **Desbloquear IP**
   - Click en "Desbloquear"
   - Confirmar acción
   - IP se mueve a historial

4. **Consultar historial**
   - Scroll a sección "Historial"
   - Aplicar filtros y paginación
   - Ver bloqueos pasados

---

## 🔗 Integración

### **Desde otros módulos**

```php
// Incluir funciones de seguridad
require_once __DIR__ . '/diagnostico_modular/core/funciones_seguridad.php';
require_once __DIR__ . '/diagnostico_modular/config/conexion.php';

$conn = conectar_bd();
$ip = obtener_ip_cliente();
$bloqueado = verificar_bloqueo($conn, 'ip', $ip);

if ($bloqueado) {
    die('Acceso denegado: ' . $bloqueado);
}
```

### **Llamar API desde JavaScript**

```javascript
fetch('/diagnostico_modular/api/detalle_forense.php?ip=192.168.1.1')
    .then(res => res.json())
    .then(data => console.log(data));
```

---

## 📊 Tablas de Base de Datos

### **Utilizadas por el módulo:**

- `tbl_blocked_entities` - Bloqueos activos
- `tbl_block_history` - Historial de bloqueos
- `tbl_security_alerts` - Alertas de seguridad
- `tbl_login_attempts` - Intentos de login

---

## ⚙️ Mantenimiento

### **Agregar nuevo filtro**
1. Modificar `funciones_bd.php` - Agregar parámetro a query
2. Modificar `index.php` - Capturar parámetro GET
3. Modificar `panel_principal.php` - Agregar input en formulario

### **Modificar estilos**
- Editar `assets/css/diagnostico.css`
- No usar CSS inline

### **Agregar funcionalidad JS**
- Editar `assets/js/diagnostico.js`
- Mantener funciones documentadas

---

## 🔒 Seguridad

- ✅ Prepared statements en todas las queries
- ✅ Sanitización de inputs
- ✅ Escape HTML en outputs
- ✅ Validación de parámetros
- ✅ Headers de seguridad

---

## 📝 Notas

- **Sin duplicación de código** - Funciones centralizadas en `core/`
- **Modular** - Cada componente tiene responsabilidad única
- **Reutilizable** - Funciones pueden usarse en otros módulos
- **Mantenible** - Código limpio y documentado
- **Escalable** - Fácil agregar nuevas funcionalidades
