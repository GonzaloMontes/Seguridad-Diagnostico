# Guía Completa: SFTP en Windsurf

## 📋 Tabla de Contenidos
1. [Instalación Inicial](#instalación-inicial)
2. [Configuración del Proyecto](#configuración-del-proyecto)
3. [Uso Diario](#uso-diario)
4. [Solución de Problemas](#solución-de-problemas)

---

## 1. Instalación Inicial

### **Paso 1.1: Instalar la Extensión SFTP**

1. Abre Windsurf
2. Presiona `Ctrl + Shift + X` (abre el panel de extensiones)
3. Busca: **"SFTP" by Natizyskunk**
4. Haz clic en **Install**
5. Espera a que se instale (verás una notificación)

### **Paso 1.2: Verificar Instalación**

1. Presiona `Ctrl + Shift + P`
2. Escribe: `SFTP`
3. Deberías ver comandos como:
   - `SFTP: Config`
   - `SFTP: Sync Local -> Remote`
   - `SFTP: Upload`

---

## 2. Configuración del Proyecto

### **Paso 2.1: Abrir la Carpeta del Proyecto**

⚠️ **IMPORTANTE**: SFTP solo funciona si tienes una carpeta abierta, no archivos sueltos.

1. Cierra todos los archivos abiertos
2. Ve a `File` → `Open Folder` (o `Ctrl + K, Ctrl + O`)
3. Selecciona: `c:\TURF\Seguridad\AppWebLoginSeguirdad\LoginTest`
4. Haz clic en **"Seleccionar carpeta"**

### **Paso 2.2: Verificar el Archivo de Configuración**

El archivo de configuración debe estar en:
```
LoginTest\.vscode\sftp.json
```

Contenido correcto:
```json
{
    "name": "halcon.turfsoft.net",
    "host": "dns8.grupocreartel.com",
    "protocol": "ftp",
    "port": 21,
    "username": "gonzalo@halcon.turfsoft.net",
    "password": "Gonzalo#123",
    "secure": true,
    "remotePath": "/public_html",
    "uploadOnSave": true,
    "useTempFile": false,
    "openSsh": false,
    "context": ".",
    "syncOption": {
        "delete": false,
        "skipCreate": false,
        "ignoreExisting": false,
        "update": true
    },
    "ignore": [
        ".vscode",
        ".git",
        ".gitignore",
        "docs",
        "*.md",
        "diagnostico_backup.php"
    ]
}
```

⚠️ **Configuración Crítica**:
- `"remotePath": "/public_html"` ← **DEBE ser `/public_html`**, NO `/`
- `"uploadOnSave": true` ← Subida automática al guardar
- `"context": "."` ← Sincroniza desde la raíz del proyecto

---

## 3. Uso Diario

### **Paso 3.1: Al Abrir Windsurf**

1. **Abre Windsurf**
2. **Abre la carpeta del proyecto**: `File` → `Open Folder` → Selecciona `LoginTest`
3. **Verifica el panel SFTP**: Debería aparecer "SFTP" en el panel inferior

### **Paso 3.2: Subida Automática (Recomendado)**

Con `"uploadOnSave": true`, cada vez que guardes un archivo:

1. **Edita un archivo** (ej: `diagnostico.php`)
2. **Guarda** con `Ctrl + S`
3. **Verás en la barra de estado**: `done [nombre_archivo]`
4. **El archivo se subió automáticamente** al servidor

### **Paso 3.3: Sincronización Manual (Primera vez o cambios masivos)**

Si es la primera vez o hiciste muchos cambios:

1. **Presiona** `Ctrl + Shift + P`
2. **Escribe**: `SFTP: Sync Local -> Remote`
3. **Presiona** Enter
4. **Observa el panel SFTP** (abajo): Verás todos los archivos que se están subiendo
5. **Espera** a que termine (verás "Sync completed" o similar)

### **Paso 3.4: Subir un Archivo Específico**

Si quieres subir un archivo manualmente:

1. **Click derecho** en el archivo en el explorador
2. Selecciona **`SFTP: Upload`**
3. El archivo se subirá inmediatamente

### **Paso 3.5: Subir una Carpeta Completa**

1. **Click derecho** en la carpeta en el explorador
2. Selecciona **`SFTP: Upload Folder`**
3. Toda la carpeta se subirá

---

## 4. Solución de Problemas

### **Problema 1: "SFTP expects to work at a folder"**

**Causa**: No tienes una carpeta abierta, solo archivos sueltos.

**Solución**:
1. Cierra Windsurf
2. Abre Windsurf
3. `File` → `Open Folder`
4. Selecciona `c:\TURF\Seguridad\AppWebLoginSeguirdad\LoginTest`

---

### **Problema 2: No veo el panel SFTP abajo**

**Causa**: La extensión no está activa o no hay carpeta abierta.

**Solución**:
1. Verifica que la carpeta `LoginTest` esté abierta
2. Presiona `Ctrl + Shift + P` → `SFTP: List`
3. Deberías ver tu configuración `halcon.turfsoft.net`

---

### **Problema 3: Error 404 al acceder a la URL**

**Causa**: Los archivos no se subieron a `/public_html` correctamente.

**Solución**:
1. Verifica que `"remotePath": "/public_html"` en `sftp.json`
2. Sincroniza: `Ctrl + Shift + P` → `SFTP: Sync Local -> Remote`
3. Ve a cPanel → Administrador de archivos → `/public_html/`
4. Verifica que `diagnostico.php` esté ahí

---

### **Problema 4: Se crea una carpeta `public_html` dentro de `public_html`**

**Causa**: `"remotePath": "/"` en lugar de `"/public_html"`.

**Solución**:
1. Cambia a `"remotePath": "/public_html"` en `sftp.json`
2. Elimina la carpeta incorrecta en cPanel
3. Vuelve a sincronizar

---

### **Problema 5: Los cambios no se ven en el navegador**

**Causa**: Caché del navegador.

**Solución**:
1. **Recarga forzada**: `Ctrl + Shift + R`
2. O abre en **modo incógnito**: `Ctrl + Shift + N`
3. Accede a: `https://halcon.turfsoft.net/diagnostico.php`

---

## 📊 Verificación de Configuración Correcta

### **Estructura Local (Windsurf)**
```
LoginTest/
├── .vscode/
│   └── sftp.json                    ← Configuración SFTP
├── diagnostico.php                  ← Archivo principal
├── configuraciones/
│   └── conexion_bd.php
└── diagnostico_modular/
    ├── core/
    ├── vistas/
    └── assets/
```

### **Estructura Remota (Servidor)**
```
/public_html/
├── diagnostico.php                  ← Debe estar aquí
├── configuraciones/
│   └── conexion_bd.php
└── diagnostico_modular/
    ├── core/
    ├── vistas/
    └── assets/
```

### **URL de Acceso**
```
https://halcon.turfsoft.net/diagnostico.php
```

---

## 🎯 Workflow Recomendado

### **Inicio del Día**
1. Abre Windsurf
2. `File` → `Open Folder` → `LoginTest`
3. Verifica que el panel SFTP esté activo

### **Durante el Desarrollo**
1. Edita archivos
2. Guarda con `Ctrl + S` → Se sube automáticamente
3. Recarga el navegador con `Ctrl + Shift + R`
4. Verifica los cambios

### **Fin del Día**
1. Sincroniza todo: `Ctrl + Shift + P` → `SFTP: Sync Local -> Remote`
2. Verifica que todo funcione en producción

---

## ⚠️ Configuración Crítica - NO CAMBIAR

```json
"remotePath": "/public_html"    ← SIEMPRE debe ser esto
"uploadOnSave": true            ← Para subida automática
"context": "."                  ← Sincroniza desde la raíz
```

---

## 📞 Soporte

Si tienes problemas:
1. Verifica que la carpeta `LoginTest` esté abierta
2. Verifica que `remotePath` sea `/public_html`
3. Sincroniza manualmente: `SFTP: Sync Local -> Remote`
4. Limpia caché del navegador: `Ctrl + Shift + R`

---

**Última actualización**: 9 de diciembre de 2025
