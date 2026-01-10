import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarVolunteer } from '../../../components/Volunteer/sidebar-volunteer/sidebar-volunteer';
import { VoluntariadoCard } from '../../../components/Volunteer/voluntariado-card/voluntariado-card';
import { VoluntariadoService } from '../../../services/voluntariado-service';
import { StatusToggleVoluntariado } from "../../../components/Volunteer/status-toggle-voluntariado/status-toggle-voluntariado";

@Component({
  selector: 'app-mis-voluntariados',
  imports: [
    CommonModule,
    Navbar,
    SidebarVolunteer,
    VoluntariadoCard,
    StatusToggleVoluntariado
  ],
  templateUrl: './mis-voluntariados.html',
  styleUrl: './mis-voluntariados.css',
})
export class MisVoluntariados {
  activeTab: 'left' | 'second' | 'middle' | 'right' = 'left';

  tabLabel = 'Pendientes';

  // Dynamic counts (mocked for now, or could be fetched)
  countPending = 0;
  countAccepted = 0;
  countOngoing = 0;
  countCompleted = 0;

  private voluntariadoService = inject(VoluntariadoService);
  // Fetch from session or fallback (00000000A is Carlos from fixtures)
  currentDNI = localStorage.getItem('user_id') || '00000000A';

  volunteeringData: any[] = [];

  ngOnInit() {
    this.loadData('PENDIENTE');
    this.loadCounts();
  }

  loadCounts() {
    this.voluntariadoService.getInscripcionesVoluntario(this.currentDNI, 'PENDIENTE').subscribe(d => this.countPending = d.length);
    this.voluntariadoService.getInscripcionesVoluntario(this.currentDNI, 'CONFIRMADO').subscribe(d => this.countAccepted = d.length);
    this.voluntariadoService.getInscripcionesVoluntario(this.currentDNI, 'EN_CURSO').subscribe(d => this.countOngoing = d.length);
    this.voluntariadoService.getInscripcionesVoluntario(this.currentDNI, 'COMPLETADA').subscribe(d => this.countCompleted = d.length);
  }

  loadData(estado: string) {
    this.voluntariadoService.getInscripcionesVoluntario(this.currentDNI, estado).subscribe({
      next: (data) => {
        console.log(`API Response MisVoluntariados (${estado}):`, data);
        this.volunteeringData = data.map((v: any) => ({
          title: v.nombre,
          organization: v.organizacion,
          skills: Array.isArray(v.habilidades) ? v.habilidades : [],
          date: v.fechaInicio,
          status: v.estado_inscripcion,
          ods: Array.isArray(v.ods) ? v.ods.map((o: string) => ({
            id: 0,
            name: o,
            color: this.getOdsColor(o)
          })) : []
        }));
      },
      error: (err) => {
        console.error('Error fetching mis voluntariados', err);
        this.volunteeringData = [];
      }
    });
  }

  getOdsColor(ods: string): string {
    const colors: { [key: string]: string } = {
      'ODS1': '#ff4444',
      'ODS2': '#ffbb33',
      'ODS3': '#00c851',
      'ODS4': '#33b5e5',
    };
    return colors[ods] || '#9e9e9e';
  }

  onTabChange(tab: 'left' | 'second' | 'middle' | 'right') {
    this.activeTab = tab;

    if (tab === 'left') {
      this.tabLabel = 'Pendientes';
      this.loadData('PENDIENTE');
    }
    if (tab === 'second') {
      this.tabLabel = 'Aceptados';
      this.loadData('CONFIRMADO');
    }
    if (tab === 'middle') {
      this.tabLabel = 'En Curso';
      this.loadData('EN_CURSO');
    }
    if (tab === 'right') {
      this.tabLabel = 'Completados';
      this.loadData('COMPLETADA');
    }
  }

  onAction(item: any) {
    console.log('Action in Mis Voluntariados:', item.title);
  }
}
