# 👥 Fullday Users Plugin

Plugin completo para WordPress que gestiona usuarios tipo Cliente y Proveedor con dashboards personalizados, perfiles detallados y sistema de aprobación para proveedores.

## 📋 Características Principales

- ✅ Roles personalizados: Cliente (`fullday_cliente`) y Proveedor (`fullday_proveedor`)
- 🔐 Sistema de registro con validación de documentos para proveedores
- 👤 Dashboards personalizados por tipo de usuario
- 📝 Gestión completa de perfiles con avatar y banner
- ✅ Sistema de aprobación para proveedores
- 🌎 Gestión de ubicación (Estado y Ciudad)
- 🌐 Integración con redes sociales (Facebook, Instagram, WhatsApp)
- 🔒 Sistema de autenticación con WordPress
- 📱 Interfaz responsive y moderna

## 🚀 Instalación

1. Copia la carpeta `fullday-users` a `/wp-content/plugins/`
2. Activa el plugin desde el panel de administración
3. Los roles `fullday_cliente` y `fullday_proveedor` se crean automáticamente
4. Usa los shortcodes para crear páginas de registro, login y dashboard

## 📊 User Meta Fields

### 👤 Campos Comunes (Cliente y Proveedor)

Todos los usuarios (clientes y proveedores) tienen estos meta fields:

```php
// Información de contacto
'phone'           // Teléfono del usuario
'estado'          // Estado/Provincia donde reside
'ciudad'          // Ciudad donde reside

// Media
'avatar'          // ID del attachment del avatar personalizado
'banner'          // ID del attachment del banner (solo visible en proveedores)
```

### 🏢 Campos Específicos de Proveedor

Los usuarios con rol `fullday_proveedor` tienen meta fields adicionales:

```php
// Información empresarial
'empresa'              // Nombre de la empresa/negocio
'descripcion'          // Descripción del proveedor y sus servicios

// Redes sociales y contacto
'facebook_url'         // URL completa del perfil de Facebook
'instagram_url'        // URL completa del perfil de Instagram
'whatsapp'             // Número de WhatsApp (formato: +56 9 1234 5678)

// Sistema de aprobación
'proveedor_approved'   // '1' = aprobado, '0' = pendiente
'documento_id'         // ID del attachment del documento de identidad
```

### 👶 Campos Específicos de Cliente

Los usuarios con rol `fullday_cliente` tienen meta fields adicionales:

```php
'fecha_nacimiento'     // Fecha de nacimiento del cliente
'fullday_favoritos'    // Array de IDs de posts marcados como favoritos
```

## 🎯 Uso de User Meta Fields

### 📖 Obtener datos del usuario

```php
// Obtener ID del usuario actual
$user_id = get_current_user_id();

// Datos comunes
$phone = get_user_meta($user_id, 'phone', true);
$estado = get_user_meta($user_id, 'estado', true);
$ciudad = get_user_meta($user_id, 'ciudad', true);
$avatar_id = get_user_meta($user_id, 'avatar', true);
$avatar_url = wp_get_attachment_url($avatar_id);

// Para proveedores
$empresa = get_user_meta($user_id, 'empresa', true);
$descripcion = get_user_meta($user_id, 'descripcion', true);
$facebook_url = get_user_meta($user_id, 'facebook_url', true);
$instagram_url = get_user_meta($user_id, 'instagram_url', true);
$whatsapp = get_user_meta($user_id, 'whatsapp', true);
$approved = get_user_meta($user_id, 'proveedor_approved', true);
$banner_id = get_user_meta($user_id, 'banner', true);
$banner_url = wp_get_attachment_url($banner_id);

// Para clientes
$fecha_nacimiento = get_user_meta($user_id, 'fecha_nacimiento', true);
$favoritos = get_user_meta($user_id, 'fullday_favoritos', true);
```

### ✏️ Actualizar datos del usuario

```php
// Actualizar datos comunes
update_user_meta($user_id, 'phone', '+56 9 1234 5678');
update_user_meta($user_id, 'estado', 'santiago');
update_user_meta($user_id, 'ciudad', 'Santiago');

// Actualizar datos de proveedor
update_user_meta($user_id, 'empresa', 'Aventuras Extremas SPA');
update_user_meta($user_id, 'descripcion', 'Especialistas en turismo aventura');
update_user_meta($user_id, 'facebook_url', 'https://facebook.com/aventuras');
update_user_meta($user_id, 'instagram_url', 'https://instagram.com/aventuras');
update_user_meta($user_id, 'whatsapp', '+56 9 8765 4321');
update_user_meta($user_id, 'proveedor_approved', '1');
```

## 🔌 Shortcodes Disponibles

### Registro de usuarios
```
[fullday_registration]
```
Muestra el formulario de registro con tabs para Cliente y Proveedor.

### Login de usuarios
```
[fullday_login]
```
Muestra el formulario de inicio de sesión.

### Dashboard
```
[fullday_dashboard]
```
Muestra el dashboard correspondiente según el tipo de usuario:
- **Clientes**: Vista de favoritos y perfil
- **Proveedores**: Gestión de Full Days, perfil y estado de aprobación

### Banner público de proveedor
```
[fullday_proveedor_banner id="123"]
```
Muestra el banner público de un proveedor con su empresa, avatar y redes sociales.

## 📁 Estructura de Archivos

```
fullday-users/
├── assets/
│   ├── css/
│   │   ├── dashboard-*.css
│   │   └── registration.css
│   └── js/
│       ├── dashboard-*.js
│       └── registration.js
├── includes/
│   ├── class-admin.php
│   ├── class-dashboard.php
│   ├── class-locations.php
│   ├── class-registration.php
│   └── class-roles.php
├── templates/
│   └── dashboard/
│       ├── cliente/
│       │   ├── dashboard-cliente.php
│       │   └── perfil.php
│       └── proveedor/
│           ├── dashboard-proveedor.php
│           ├── perfil.php
│           ├── crear-fullday.php
│           └── mis-fulldays.php
├── fullday-users.php
└── README.md
```

## 🔐 Sistema de Roles

### fullday_cliente
Capacidades:
- `read` - Lectura básica
- Ver y gestionar favoritos
- Editar su propio perfil

### fullday_proveedor
Capacidades:
- `read` - Lectura básica
- `edit_posts` - Crear/editar Full Days
- `delete_posts` - Eliminar Full Days
- `upload_files` - Subir imágenes
- Editar su propio perfil
- Requiere aprobación del administrador

## 🔄 AJAX Endpoints

El plugin registra estos endpoints AJAX:

```php
// Actualización de perfil
'fullday_update_profile'
'fullday_update_password'
'fullday_upload_avatar'
'fullday_upload_banner'

// Gestión de Full Days
'fullday_create_fullday'
'fullday_update_fullday'
'fullday_delete_fullday'
'fullday_toggle_status'
'fullday_upload_fullday_image'
'fullday_save_draft'

// Sistema de favoritos
'fullday_toggle_favorite'

// Registro y login
'fullday_register_user'
'fullday_login_user'
'fullday_upload_documento'

// Utilidades
'fullday_get_cities'
```

## 📞 Soporte

Para soporte técnico o reportar bugs:
- Email: team@fullday.com
- Website: https://fullday.com

## 📄 Licencia

GPL v2 o posterior

---

**Desarrollado por FullDay Team** - Sistema completo de gestión de usuarios para plataformas turísticas.
