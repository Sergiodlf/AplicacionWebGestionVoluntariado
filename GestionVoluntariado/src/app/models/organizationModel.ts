/**
 * Define la estructura de datos para una Organización tal como la recibe
 * la aplicación Angular desde la API (sin campos sensibles como el password).
 */
export interface Organization {
  cif: string;
  nombre: string;
  email: string;
  sector: string;
  direccion: string;
  localidad: string;
  cp: string;         // Nuevo campo requerido por backend
  contacto: string;   // Nuevo campo requerido por backend
  descripcion: string;
  actividades?: any[];

  // Vista Dashboard: Campo necesario para filtrar
  // Backend devuelve mayúsculas (PENDIENTE, APROBADO, RECHAZADO)
  estado: string;
}

/**
 * Define la estructura de datos que se usa para crear (POST) o registrar una
 * nueva organización. Incluye campos obligatorios para el registro, como el password.
 * Hereda todos los campos de Organization, pero requiere el password.
 */
export interface OrganizationCreateData extends Organization {
  password: string; // Campo necesario para el registro/creación
}
