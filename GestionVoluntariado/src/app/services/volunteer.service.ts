import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Voluntario } from '../models/voluntario';

export interface Volunteer {
  name: string;
  email: string;
  skills: string[];
  availability: string;
  interests: string[];
}

@Injectable({
  providedIn: 'root'
})
export class VolunteerService {

  private apiUrl = '/api/auth/register/voluntario'; // Correct endpoint from debug:router

  constructor(private http: HttpClient) { }

  /** ---------- SIGNALS PARA EJEMPLOS LOCALES (ya existen) ---------- */

  private volunteersSignal = signal<Volunteer[]>([
    {
      name: 'María García',
      email: 'maria.garcia@gmail.com',
      skills: ['Educación', 'Comunicación', 'Inglés'],
      availability: 'Fines de semana',
      interests: ['Educación', 'Niños']
    },
    {
      name: 'Juan Pérez',
      email: 'juan.perez@gmail.com',
      skills: ['Deportes', 'Organización'],
      availability: 'Tardes',
      interests: ['Deportes', 'Jóvenes']
    },
    {
      name: 'Ana López',
      email: 'ana.lopez@gmail.com',
      skills: ['Cocina', 'Logística'],
      availability: 'Mañanas',
      interests: ['Comedores Sociales']
    },
    {
      name: 'Carlos Ruiz',
      email: 'carlos.ruiz@gmail.com',
      skills: ['Informática', 'Diseño'],
      availability: 'Remoto',
      interests: ['Tecnología', 'ONGs']
    }
  ]);

  getVolunteers() {
    return this.volunteersSignal.asReadonly();
  }

  addVolunteerToSignal(volunteer: Volunteer) {
    this.volunteersSignal.update(volunteers => [...volunteers, volunteer]);
  }

  removeVolunteer(email: string) {
    this.volunteersSignal.update(volunteers => volunteers.filter(v => v.email !== email));
  }

  /** ---------- AQUI VIENE LO IMPORTANTE: POST REAL A API ---------- */

  createVolunteer(voluntario: any): Observable<any> {
    return this.http.post(this.apiUrl, voluntario);
  }
}
