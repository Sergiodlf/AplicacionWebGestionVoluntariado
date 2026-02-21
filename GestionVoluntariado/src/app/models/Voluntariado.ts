export interface Voluntariado {
  codAct: number;
  nombre: string;
  estado: string;
  direccion: string;
  maxParticipantes: number;
  organizacion: string;
  // Optional fields for UI mapping
  organization?: string;
  habilidades?: string;
  fechaInicio?: string;
  fechaFin?: string;
  descripcion?: string;
  inscritos_count?: number;
  cupo?: number;
  // For UI structure compatibility
  title?: string;
  skills?: string[];
  date?: string;
  ciclo?: string;
  ods?: any[];
  necesidades?: string[];
}
