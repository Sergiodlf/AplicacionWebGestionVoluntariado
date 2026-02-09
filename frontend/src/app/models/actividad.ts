export interface Actividad {
  cifOrganizacion: string;
  nombre: string;
  descripcion: string;
  fechaInicio: string;  // ISO date string
  maxParticipantes: number;
  ods: string[];
}
