import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

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
  descripcion?: string;
  // For UI structure compatibility
  title?: string;
  skills?: string[];
  date?: string;
  ods?: any[];
}

@Injectable({
  providedIn: 'root',
})
export class VoluntariadoService {
  private apiUrl = '/api/actividades';
  private inscripcionesUrl = '/api/inscripciones';

  constructor(private http: HttpClient) { }

  getAllVoluntariados(): Observable<Voluntariado[]> {
    return this.http.get<Voluntariado[]>(this.apiUrl);
  }

  getInscripcionesVoluntario(dni: string): Observable<any[]> {
    // Assuming this endpoint returns the list of inscriptions (matches) for the volunteer
    return this.http.get<any[]>(`${this.inscripcionesUrl}/voluntario/${dni}`);
  }

  getInscripcionesPendientes(dni: string): Observable<any[]> {
    return this.http.get<any[]>(`${this.inscripcionesUrl}/voluntario/${dni}/pendientes`);
  }

  getAllInscripciones(): Observable<any[]> {
    return this.http.get<any[]>(this.inscripcionesUrl);
  }

  inscribirVoluntario(dniVoluntario: string, idActividad: number): Observable<any> {
    return this.http.post(this.inscripcionesUrl, {
      dniVoluntario: dniVoluntario,
      codActividad: idActividad
    });
  }

  updateInscripcionStatus(idInscripcion: number, estado: 'CONFIRMADO' | 'RECHAZADO' | 'PENDIENTE'): Observable<any> {
    const url = `${this.inscripcionesUrl}/${idInscripcion}/estado`;
    return this.http.patch(url, { estado: estado });
  }

  getActividadesAceptadas(dni: string, estado: string): Observable<any[]> {
    return this.http.get<any[]>(`${this.inscripcionesUrl}/voluntario/${dni}/actividades-aceptadas`, {
      params: { estado: estado }
    });
  }

  getActivitiesByOrganization(cif: string, estado?: string, estadoAprobacion: string = 'ACEPTADA'): Observable<any[]> {
    let params: any = { estadoAprobacion: estadoAprobacion };
    if (estado) {
      params.estado = estado;
    }
    return this.http.get<any[]>(`${this.apiUrl}/organizacion/${cif}`, { params });
  }
}
