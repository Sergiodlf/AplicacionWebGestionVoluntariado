import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { Voluntario } from '../models/voluntario';

export interface Volunteer {
  name: string;
  email: string;
  skills: string[];
  availability: string;
  interests: string[];
  id?: number;
  status: string;
  dni?: string;
  birthDate?: string;
  experience?: string;
  hasCar?: boolean;
  languages?: string;
}

@Injectable({
  providedIn: 'root'
})
export class VolunteerService {

  private apiUrl = '/api/auth/register/voluntario';
  private apiGetUrl = '/api/voluntarios'; // Corrected endpoint per user input

  constructor(private http: HttpClient) { }

  /**
   * Obtiene la lista de voluntarios desde la API.
   * @returns Observable de Volunteer[]
   */
  getVolunteers(): Observable<Volunteer[]> {
    return this.http.get<any[]>(this.apiGetUrl).pipe(
      map((volunteers: any[]) => volunteers.map((v: any) => ({
        name: v.nombre,
        email: v.correo,
        skills: v.habilidades ? (typeof v.habilidades === 'string' ? v.habilidades.split(',') : v.habilidades).map((s: string) => s.trim()) : [],
        availability: v.zona || 'No especificada',
        interests: v.intereses ? (typeof v.intereses === 'string' ? v.intereses.split(',') : v.intereses).map((s: string) => s.trim()) : [],
        id: (v.inscripciones && v.inscripciones.length > 0) ? v.inscripciones[0].id_inscripcion : undefined,
        status: v.estado_voluntario || 'PENDIENTE',
        dni: v.dni,
        birthDate: v.fechaNacimiento,
        experience: v.experiencia,
        hasCar: v.coche,
        languages: v.idiomas
      } as Volunteer)))
    );
  }

  // Helper to maintain compatibility if optimistic updates are needed, 
  // but for now we trust the GET.
  // Implementation of add/remove would need to call API mostly.

  /** ---------- AQUI VIENE LO IMPORTANTE: POST REAL A API ---------- */

  createVolunteer(voluntario: any): Observable<any> {
    return this.http.post(this.apiUrl, voluntario);
  }

  updateStatus(dni: string, status: string): Observable<any> {
    console.log(`Updating status for DNI ${dni} to ${status}. URL: /api/voluntarios/${dni}/estado`);
    return this.http.patch(`/api/voluntarios/${dni}/estado`, { estado: status });
  }
}
