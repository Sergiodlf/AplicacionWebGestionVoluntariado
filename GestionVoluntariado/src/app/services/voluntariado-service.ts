import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, BehaviorSubject, of } from 'rxjs';
import { tap } from 'rxjs/operators';
import { Voluntariado } from '../models/Voluntariado';

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

  clearState() {
    this.inscripcionesSubject.next(null);
    this.actividadesSubject.next(null);
    this.myInscripcionesSubject.next(null);
  }

  getAllVoluntariados(
    forceReload: boolean = false,
    filter?: { estadoAprobacion?: string; history?: boolean; estado?: string },
  ): Observable<Voluntariado[]> {
    if (this.actividadesSubject.value && !forceReload && !filter) {
      return of(this.actividadesSubject.value);
    }
    return this.loadVoluntariados(filter);
  }

  getAllVoluntariadosFiltered(
    estadoAprobacion?: string,
  ): Observable<Voluntariado[]> {
    return this.loadVoluntariados({ estadoAprobacion });
  }

  loadVoluntariados(filter?: {
    estadoAprobacion?: string;
    history?: boolean;
    estado?: string;
  }): Observable<Voluntariado[]> {
    // Cache busting
    const timestamp = new Date().getTime();
    let params = new HttpParams().set('t', timestamp.toString());

    if (filter?.estadoAprobacion) {
      params = params.set('estadoAprobacion', filter.estadoAprobacion);
    }

    if (filter?.history !== undefined) {
      params = params.set('history', filter.history.toString());
    }

    if (filter?.estado) {
      params = params.set('estado', filter.estado);
    }

    return this.http.get<Voluntariado[]>(this.apiUrl, { params }).pipe(
      tap((data) => {
        // Solo cacheamos cuando no hay filtros
        if (
          !filter ||
          (!filter.estadoAprobacion &&
            filter.history === undefined &&
            !filter.estado)
        ) {
          this.actividadesSubject.next(data);
        }
      }),
    );
  }

  getAllInscripciones(forceReload: boolean = false): Observable<any[]> {
    if (this.inscripcionesSubject.value && !forceReload) {
      return of(this.inscripcionesSubject.value);
    }
    return this.loadInscripciones();
  }

  loadInscripciones(): Observable<any[]> {
    return this.http
      .get<any[]>(this.inscripcionesUrl)
      .pipe(tap((data) => this.inscripcionesSubject.next(data)));
  }

  inscribirVoluntario(
    dniVoluntario: string,
    idActividad: number,
  ): Observable<any> {
    const url = `${this.apiUrl}/${idActividad}/inscribir`;
    return this.http
      .post(url, {
        dni: dniVoluntario,
      })
      .pipe(
        tap(() => this.myInscripcionesSubject.next(null)), // Invalidate cache
      );
  }

  updateInscripcionStatus(
    idInscripcion: number,
    estado: 'CONFIRMADO' | 'RECHAZADO' | 'PENDIENTE' | 'COMPLETADA',
  ): Observable<any> {
    const url = `${this.inscripcionesUrl}/${idInscripcion}`;
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

  getMyInscripciones(
    dni?: string,
    forceReload: boolean = false,
  ): Observable<any[]> {
    if (this.myInscripcionesSubject.value && !forceReload) {
      return of(this.myInscripcionesSubject.value);
    }
    // Updated to use the new "smart" endpoint
    const url = `${this.inscripcionesUrl}/me`;
    return this.http
      .get<any[]>(url)
      .pipe(tap((data) => this.myInscripcionesSubject.next(data)));
  }

  getActivitiesByOrganization(
    cif: string,
    estado?: string,
    estadoAprobacion: string = 'ACEPTADA',
    history: boolean = true,
  ): Observable<any[]> {
    let params = new HttpParams()
      .set('estadoAprobacion', estadoAprobacion)
      .set('history', history.toString());

    if (estado) {
      params = params.set('estado', estado);
    }
    return this.http.get<any[]>(`${this.apiUrl}/organizacion/${cif}`, {
      params,
    });
  }

  crearActividad(actividad: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/crear`, actividad);
  }

  actualizarEstadoActividad(id: number, estado: string): Observable<any> {
    return this.http.patch(`${this.apiUrl}/${id}`, { estado });
  }

  updateActivity(id: number, actividad: any): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}/${id}/editar`, actividad);
  }
}
