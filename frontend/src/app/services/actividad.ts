import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Actividad } from '../models/actividad';

@Injectable({
  providedIn: 'root'
})
export class ActividadService {

  private apiUrl = '/api/actividades';  // Corrected for proxy

  constructor(private http: HttpClient) { }

  crearActividad(actividad: Actividad): Observable<any> {
    return this.http.post(this.apiUrl, actividad);
  }

  getActividades(): Observable<any[]> {
    return this.http.get<any[]>(this.apiUrl);
  }

  // Nuevo método para actualizar estado
  actualizarEstado(id: number, estado: string): Observable<any> {
    return this.http.patch(`${this.apiUrl}/${id}/estado`, { estado });
  }
}
