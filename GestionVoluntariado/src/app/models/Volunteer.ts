export interface Volunteer {
  nombre: string;
  apellido1: string;
  email: string;
  habilidades: any[];
  disponibilidad: string[];
  intereses: any[];
  id?: number;
  status: string;
  dni?: string;
  birthDate?: string;
  experience?: string;
  experiencia?: string; // Backend mapping
  hasCar?: boolean;
  coche?: boolean; // Backend mapping
  languages?: any[];
  zona?: string;
  ciclo?: string;
}
