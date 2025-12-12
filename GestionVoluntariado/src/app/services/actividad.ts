import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Actividad } from '../models/actividad';

@Injectable({
  providedIn: 'root'
})
export class ActividadService {

  private apiUrl = 'http://localhost:8000/api/actividades';  // AJÚSTALO a tu API

  constructor(private http: HttpClient) {}

  crearActividad(actividad: Actividad): Observable<any> {
    return this.http.post(this.apiUrl, actividad);
  }
}
