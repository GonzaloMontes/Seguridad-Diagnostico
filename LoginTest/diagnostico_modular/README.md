# Módulo de Diagnóstico - Guía Rápida

Sistema modular de gestión de IPs bloqueadas y análisis forense de seguridad.

---

## 🚀 Inicio Rápido

### **Acceso**
```
http://localhost/diagnostico_modular/
```

### **Funcionalidades**
- ✅ Visualizar IPs bloqueadas activas
- ✅ Análisis forense detallado por IP
- ✅ Desbloquear IPs manualmente
- ✅ Consultar historial de bloqueos
- ✅ Filtros por fecha y búsqueda

---

## 📁 Estructura

```
diagnostico_modular/
├── config/          # Configuración (BD, constantes)
├── core/            # Lógica de negocio
├── api/             # Endpoints JSON
├── vistas/          # HTML/PHP
├── assets/          # CSS/JS
└── index.php        # Punto de entrada
```

---

## 🔧 Uso Básico

### **1. Ver IPs bloqueadas**
- Acceder a `index.php`
- Ver tabla con IPs activas
- Aplicar filtros opcionales

### **2. Analizar bloqueo**
- Click "Ver detalle" en cualquier IP
- Modal muestra análisis completo:
  - Motivo del bloqueo
  - Intentos fallidos por usuario
  - Alertas de seguridad
  - Historial de bloqueos

### **3. Desbloquear IP**
- Click "Desbloquear"
- Confirmar acción
- IP se archiva en historial

### **4. Consultar historial**
- Scroll a sección inferior
- Ver bloqueos pasados
- Paginación automática

---

## 🔗 API REST

### **Endpoint de análisis forense**

**URL:** `/diagnostico_modular/api/detalle_forense.php`

**Parámetros:**
- `ip` (requerido) - IP a analizar
- `desde` (opcional) - Fecha desde (YYYY-MM-DD)
- `hasta` (opcional) - Fecha hasta (YYYY-MM-DD)

**Ejemplo:**
```bash
curl "http://localhost/diagnostico_modular/api/detalle_forense.php?ip=192.168.1.1"
```

---

## 📚 Documentación Completa

Ver: `/docs/DIAGNOSTICO_MODULAR.md`

---

## 🛠️ Mantenimiento

### **Configuración BD**
Editar: `config/conexion.php`

### **Políticas de bloqueo**
Editar: `config/constantes.php`

### **Estilos**
Editar: `assets/css/diagnostico.css`

### **JavaScript**
Editar: `assets/js/diagnostico.js`

---

## ⚠️ Importante

- ✅ No modificar archivos fuera de `diagnostico_modular/`
- ✅ Usar funciones de `core/` para reutilización
- ✅ Mantener CSS/JS en archivos externos
- ✅ Documentar cambios significativos
