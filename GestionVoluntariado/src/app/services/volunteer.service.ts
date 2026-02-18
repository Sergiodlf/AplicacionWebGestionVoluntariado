import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, of, map, tap } from 'rxjs';
import { Volunteer } from '../models/Volunteer';

@Injectable({
  providedIn: 'root'
})
export class VolunteerService {

  private apiUrl = '/api/auth/register/voluntario';
  private apiGetUrl = '/api/voluntarios';
  private apiCategoriesUrl = '/api/categories';

  private volunteersSubject = new BehaviorSubject<Volunteer[] | null>(null);
  volunteers$ = this.volunteersSubject.asObservable();

  constructor(private http: HttpClient) { }

  getVolunteers(forceReload: boolean = false): Observable<Volunteer[]> {
    if (this.volunteersSubject.value && !forceReload) {
      return of(this.volunteersSubject.value);
    }
    return this.loadVolunteers();
  }

  loadVolunteers(): Observable<Volunteer[]> {
    return this.http.get<any[]>(this.apiGetUrl).pipe(
      map((volunteers: any[]) => volunteers.map((v: any) => this.mapToVolunteer(v))),
      tap((data: Volunteer[]) => this.volunteersSubject.next(data))
    );
  }

  createVolunteer(voluntario: any): Observable<any> {
    return this.http.post(this.apiUrl, voluntario);
  }

  updateStatus(dni: string, status: string): Observable<any> {
    return this.http.patch(`/api/voluntarios/${dni}`, { estado: status });
  }

  updateProfile(dni: string, data: any): Observable<any> {
    return this.http.put(`/api/voluntarios/${dni}`, data);
  }

  getVolunteerByEmail(email: string): Observable<Volunteer> {
    return this.http.get<any[]>(this.apiGetUrl, { params: { email } }).pipe(
      map((volunteers: any[]) => {
        if (volunteers && volunteers.length > 0) {
          return this.mapToVolunteer(volunteers[0]);
        }
        throw new Error('Voluntario no encontrado');
      })
    );
  }

  getProfile(): Observable<Volunteer> {
    return this.http.get<any>('/api/auth/profile').pipe(
      map((response: any) => {
        const v = response.datos;
        return {
          nombre: v.nombre,
          apellido1: v.apellido1,
          email: v.correo,
          habilidades: v.habilidades || [],
          disponibilidad: this.parseJson(v.disponibilidad),
          intereses: v.intereses || [],
          status: v.estado_voluntario || 'PENDIENTE',
          dni: v.dni,
          birthDate: v.fechaNacimiento,
          experience: v.experiencia,
          hasCar: v.coche,
          languages: this.parseJson(v.idiomas),
          zona: v.zona,
          ciclo: v.ciclo
        } as Volunteer;
      })
    );
  }

  getCiclos(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiCategoriesUrl}?type=ciclos`);
  }

  private mapToVolunteer(v: any): Volunteer {
    return {
      nombre: v.nombre,
      apellido1: v.apellido1,
      email: v.correo,
      habilidades: v.habilidades || [],
      disponibilidad: this.parseJson(v.disponibilidad),
      intereses: v.intereses || [],
      id: (v.inscripciones && v.inscripciones.length > 0) ? v.inscripciones[0].id_inscripcion : undefined,
      status: v.estado_voluntario || 'PENDIENTE',
      dni: v.dni,
      birthDate: v.fechaNacimiento,
      experience: v.experiencia,
      hasCar: v.coche,
      languages: this.parseJson(v.idiomas),
      zona: v.zona,
      ciclo: v.ciclo
    } as Volunteer;
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
