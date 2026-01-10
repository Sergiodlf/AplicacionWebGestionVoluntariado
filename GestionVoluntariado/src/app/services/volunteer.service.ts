import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, of } from 'rxjs';
import { map, tap } from 'rxjs/operators';
import { Voluntario } from '../models/voluntario';

export interface Volunteer {
  name: string;
  lastname: string;
  email: string;
  skills: string[];
  availability: string[];
  interests: string[];
  id?: number;
  status: string;
  dni?: string;
  birthDate?: string;
  experience?: string;
  hasCar?: boolean;
  languages?: string;
  zona?: string; // Added zona
}

@Injectable({
  providedIn: 'root'
})
export class VolunteerService {

  private apiUrl = '/api/auth/register/voluntario';
  private apiGetUrl = '/api/voluntarios';
  private apiCiclosUrl = '/api/ciclos';

  private volunteersSubject = new BehaviorSubject<Volunteer[] | null>(null);
  volunteers$ = this.volunteersSubject.asObservable();

  constructor(private http: HttpClient) { }

  getVolunteers(forceReload: boolean = false): Observable<Volunteer[]> {
    if (this.volunteersSubject.value && !forceReload) {
      return this.volunteersSubject.asObservable() as Observable<Volunteer[]>;
    }
    return this.loadVolunteers();
  }

  loadVolunteers(): Observable<Volunteer[]> {
    return this.http.get<any[]>(this.apiGetUrl).pipe(
      map((volunteers: any[]) => volunteers.map((v: any) => ({
        name: v.nombre,
        lastname: v.apellido1,
        email: v.correo,
        skills: this.parseJson(v.habilidades),
        availability: this.parseJson(v.disponibilidad),
        interests: this.parseJson(v.intereses),
        id: (v.inscripciones && v.inscripciones.length > 0) ? v.inscripciones[0].id_inscripcion : undefined,
        status: v.estado_voluntario || 'PENDIENTE',
        dni: v.dni,
        birthDate: v.fechaNacimiento,
        experience: v.experiencia,
        hasCar: v.coche,
        languages: v.idiomas,
        zona: v.zona // Map zona from backend
      } as Volunteer))),
      tap(data => this.volunteersSubject.next(data))
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

  getCiclos(): Observable<any[]> {
    return this.http.get<any[]>(this.apiCiclosUrl);
  }

  private parseJson(value: any): string[] {
    if (!value) return [];
    if (Array.isArray(value)) return value;
    if (typeof value === 'string') {
      const trimmed = value.trim();
      if (trimmed.startsWith('[')) {
        try {
          return JSON.parse(trimmed);
        } catch (e) {
          console.warn('Failed to parse JSON string:', value);
          return [value];
        }
      }
      return trimmed.split(',').map(s => s.trim());
    }
    return [value];
  }
}
