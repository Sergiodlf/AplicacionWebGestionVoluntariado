import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, BehaviorSubject, of } from 'rxjs';
import { tap } from 'rxjs/operators';

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
  ciclo?: string;
  ods?: any[];
}

@Injectable({
  providedIn: 'root',
})
export class VoluntariadoService {
  private apiUrl = '/api/actividades';
  private inscripcionesUrl = '/api/inscripciones';

  private inscripcionesSubject = new BehaviorSubject<any[] | null>(null);
  inscripciones$ = this.inscripcionesSubject.asObservable();

  private actividadesSubject = new BehaviorSubject<Voluntariado[] | null>(null);
  actividades$ = this.actividadesSubject.asObservable();

  private myInscripcionesSubject = new BehaviorSubject<any[] | null>(null);


  constructor(private http: HttpClient) { }

  getAllVoluntariados(forceReload: boolean = false): Observable<Voluntariado[]> {
    if (this.actividadesSubject.value && !forceReload) {
      return of(this.actividadesSubject.value);
    }
    return this.loadVoluntariados();
  }

  getAllVoluntariadosFiltered(estadoAprobacion?: string): Observable<Voluntariado[]> {
    let params = new HttpParams();
    if (estadoAprobacion) {
      params = params.set('estadoAprobacion', estadoAprobacion);
    }
    return this.http.get<Voluntariado[]>(this.apiUrl, { params });
  }

  loadVoluntariados(): Observable<Voluntariado[]> {
    return this.http.get<Voluntariado[]>(this.apiUrl).pipe(
      tap(data => this.actividadesSubject.next(data))
    );
  }


  getAllInscripciones(forceReload: boolean = false): Observable<any[]> {
    if (this.inscripcionesSubject.value && !forceReload) {
      return of(this.inscripcionesSubject.value);
    }
    return this.loadInscripciones();
  }

  loadInscripciones(): Observable<any[]> {
    return this.http.get<any[]>(this.inscripcionesUrl).pipe(
      tap(data => this.inscripcionesSubject.next(data))
    );
  }

  inscribirVoluntario(dniVoluntario: string, idActividad: number): Observable<any> {
    const url = `${this.apiUrl}/${idActividad}/inscribir`;
    return this.http.post(url, {
      dni: dniVoluntario
    }).pipe(
      tap(() => this.myInscripcionesSubject.next(null)) // Invalidate cache
    );
  }

  updateInscripcionStatus(idInscripcion: number, estado: 'CONFIRMADO' | 'RECHAZADO' | 'PENDIENTE' | 'COMPLETADA'): Observable<any> {
    const url = `${this.inscripcionesUrl}/${idInscripcion}/estado`;
    return this.http.patch(url, { estado: estado });
  }

  getInscripcionesVoluntario(dni: string, estado?: string): Observable<any[]> {
    let params: any = {};
    if (estado) {
      params.estado = estado;
    }
    let fullUrl = `${this.inscripcionesUrl}/voluntario/${dni}/inscripciones`;
    if (estado) {
      // Use the specific endpoint requested by user
      fullUrl = `${fullUrl}/estado`;
      params.estado = estado;
    }
    return this.http.get<any[]>(fullUrl, { params });
  }

  getMyInscripciones(dni: string, forceReload: boolean = false): Observable<any[]> {
    if (this.myInscripcionesSubject.value && !forceReload) {
      return of(this.myInscripcionesSubject.value);
    }
    // Fetch all for user (no status filter to get everything)
    const url = `${this.inscripcionesUrl}/voluntario/${dni}/inscripciones`;
    return this.http.get<any[]>(url).pipe(
      tap(data => this.myInscripcionesSubject.next(data))
    );
  }

  getActivitiesByOrganization(cif: string, estado?: string, estadoAprobacion: string = 'ACEPTADA'): Observable<any[]> {
    console.log(`Requesting activities for CIF: [${cif}], Status: ${estado}, Appr: ${estadoAprobacion}`);
    let params = new HttpParams()
      .set('estadoAprobacion', estadoAprobacion);

    if (estado) {
      params = params.set('estado', estado);
    }
    return this.http.get<any[]>(`${this.apiUrl}/organizacion/${cif}`, { params });
  }

  crearActividad(actividad: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/crear`, actividad);
  }

  actualizarEstadoActividad(id: number, estado: string): Observable<any> {
    return this.http.patch(`${this.apiUrl}/${id}/estado`, { estado });
  }
}
