export interface Voluntario {
  nombreCompleto: string;
  dni: string;
  correo: string;
  password: string;
  zona: string;
  ciclo: string;
  fechaNacimiento: string; // formato YYYY-MM-DD
  experiencia: string;
  coche: string;
  idiomas: string[];
  habilidades: string[];
  intereses: string[];
  disponibilidad: string[];
}
