import { Injectable, signal } from '@angular/core';

export interface Voluntariado {
  codAct: number;
  nombre: string;
  estado: string;
  direccion: string;
  maxParticipantes: number;
  //fechaInicio: Date;
  //fechaFin: Date;
  organizacion: string;
  //organizacion: Organizacion;
}

@Injectable({
  providedIn: 'root',
})
export class VoluntariadoService {
  private voluntariadosSignal = signal<Voluntariado[]>([
    {
      codAct: 1,
      nombre: 'Ayudar en residencia',
      estado: 'Pendiente',
      direccion: 'San Jorge',
      maxParticipantes: 20,
      organizacion: "organizacion"
    },
    {
      codAct: 2,
      nombre: 'Ayudar en residencia',
      estado: 'En curso',
      direccion: 'San Jorge',
      maxParticipantes: 20,
      organizacion: "organizacion"
    },
    {
      codAct: 3,
      nombre: 'Ayudar en residencia',
      estado: 'Completado',
      direccion: 'San Jorge',
      maxParticipantes: 20,
      organizacion: "organizacion"
    },
    {
      codAct: 4,
      nombre: 'Ayudar en residencia',
      estado: 'Pendiente',
      direccion: 'San Jorge',
      maxParticipantes: 20,
      organizacion: "organizacion"
    },
  ]);

  getVoluntariados() {
    return this.voluntariadosSignal.asReadonly();
  }

  addVoluntariado(vol: Voluntariado) {
    this.voluntariadosSignal.update((voluntariadosSignal) => [...voluntariadosSignal, vol]);
  }

  removeVoluntaiado(cod: number) {
    this.voluntariadosSignal.update((voluntariadosSignal) => voluntariadosSignal.filter((v) => v.codAct !== cod));
  }
}
