import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarVolunteer } from '../../../components/Volunteer/sidebar-volunteer/sidebar-volunteer';
import { VoluntariadoService, Voluntariado } from '../../../services/voluntariado-service';
import { VoluntariadoCard } from '../../../components/Volunteer/voluntariado-card/voluntariado-card';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';

@Component({
  selector: 'app-voluntariados',
  imports: [CommonModule, Navbar, SidebarVolunteer, VoluntariadoCard, StatusToggleComponent],
  templateUrl: './voluntariados.html',
  styleUrl: './voluntariados.css',
})
export class Voluntariados implements OnInit {
  private voluntariadoService = inject(VoluntariadoService);

  activeTab: 'left' | 'middle' | 'right' = 'left';
  // Fetch from session or fallback
  currentDNI = localStorage.getItem('user_id') || '00000000A';

  // Raw data from API
  allVoluntariados: Voluntariado[] = [];
  myInscripciones: any[] = [];

  // Display data
  displayedVoluntariados: Voluntariado[] = [];

  ngOnInit() {
    this.loadData();
  }

  loadData() {
    // 1. Get all available activities
    this.voluntariadoService.getAllVoluntariados().subscribe({
      next: (data) => {
        console.log('API Response /api/actividades:', data);
        this.allVoluntariados = data.map((v: any) => {
          return {
            ...v,
            codAct: v.codActividad, // Map ID
            title: v.nombre,
            organization: v.nombre_organizacion, // Map Organization Name
            skills: Array.isArray(v.habilidades) ? v.habilidades : [], // Handle array directly
            date: v.fechaInicio,
            ods: Array.isArray(v.ods) ? v.ods.map((o: string) => ({
              id: 0,
              name: o,
              color: this.getOdsColor(o)
            })) : []
          } as Voluntariado;
        });
        this.filterData();
      },
      error: (err) => console.error('Error fetching voluntariados', err)
    });

    // 2. Get my inscriptions (pending matches)
    this.voluntariadoService.getInscripcionesVoluntario(this.currentDNI, 'PENDIENTE').subscribe({
      next: (data: any[]) => {
        this.myInscripciones = data;
        this.filterData();
      },
      error: (err: any) => {
        console.error('Error fetching inscripciones', err);
        // Fallback or empty if 404
        this.myInscripciones = [];
        this.filterData();
      }
    });
  }

  onTabChange(tab: 'left' | 'middle' | 'right') {
    this.activeTab = tab;
    this.filterData();
  }

  filterData() {
    if (this.activeTab === 'left') {
      // "Todos" / "Available"
      // Show all, or maybe exclude those already signed up? 
      // User requirement: "Todos" -> "todas las actividades"
      this.displayedVoluntariados = this.allVoluntariados;
    } else {
      // "Pendientes"
      // Filter activities that match the inscription IDs
      // Assuming inscription endpoint returns list of Activities (or Objects with codActividad)
      const signedUpIds = this.myInscripciones.map(i => i.codActividad || i.codAct || i.id_actividad);
      this.displayedVoluntariados = this.allVoluntariados.filter(v => signedUpIds.includes(v.codAct));
    }
  }

  onAction(item: any) {
    if (this.activeTab === 'left') { // If in "Todos", action is "Apuntarse"
      this.inscribirse(item);
    }
  }

  inscribirse(item: Voluntariado) {
    this.voluntariadoService.inscribirVoluntario(this.currentDNI, item.codAct).subscribe({
      next: (res) => {
        console.log('Inscripción exitosa', res);
        alert('Te has apuntado correctamente!');
        // Refresh data to update "Pendientes"
        this.loadData();
      },
      error: (err) => {
        console.error('Error al inscribirse', err);
        alert('Error al apuntarse. Verifica que no estés ya inscrito.');
      }
    });
  }

  getOdsColor(ods: string): string {
    // Simple mock mapping
    const colors: { [key: string]: string } = {
      'ODS1': '#ff4444',
      'ODS2': '#ffbb33',
      'ODS3': '#00c851',
      'ODS4': '#33b5e5',
      // Add defaults or hash function
    };
    return colors[ods] || '#9e9e9e'; // Default grey
  }
}
