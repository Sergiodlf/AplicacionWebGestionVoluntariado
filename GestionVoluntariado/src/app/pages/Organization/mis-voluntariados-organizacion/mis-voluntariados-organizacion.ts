import { Component, OnInit } from '@angular/core';
import { VoluntariadoService } from '../../../services/voluntariado-service';

import { CommonModule } from '@angular/common';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { StatusToggleVoluntariado } from '../../../components/Volunteer/status-toggle-voluntariado/status-toggle-voluntariado';
import { VoluntariadoCard } from '../../../components/Volunteer/voluntariado-card/voluntariado-card';
import { CrearVoluntariadoModal } from '../../../components/organization/crear-voluntariado-modal/crear-voluntariado-modal';

@Component({
  selector: 'app-mis-voluntariados-organizacion',
  imports: [CommonModule, Navbar, StatusToggleVoluntariado, VoluntariadoCard, CrearVoluntariadoModal],
  templateUrl: './mis-voluntariados-organizacion.html',
  styleUrl: './mis-voluntariados-organizacion.css',
})
export class MisVoluntariadosOrganizacion implements OnInit {
  activeTab: 'left' | 'second' | 'middle' | 'right' = 'left';
  tabLabel = 'Pendientes';
  volunteeringData: any[] = [];
  modalOpen = false;

  // CIF de prueba proporcionado
  private readonly TEST_CIF = 'A12345678';

  constructor(private voluntariadoService: VoluntariadoService) { }

  ngOnInit(): void {
    this.loadActivities();
  }

  get filteredVolunteering() {
    return this.volunteeringData;
  }

  loadActivities() {
    let estado: string | undefined = 'PENDIENTE';
    let estadoAprobacion = 'ACEPTADA';

    if (this.activeTab === 'left') {
      // PENDIENTES (Aprobadas pero no empezadas/pendientes de realización)
      estado = 'PENDIENTE';
    } else if (this.activeTab === 'second') {
      // EN REVISIÓN (Solicitadas pero no aceptadas por admin)
      estado = undefined; // No filtramos por estado de actividad
      estadoAprobacion = 'PENDIENTE';
    } else if (this.activeTab === 'middle') {
      // EN CURSO
      estado = 'EN_CURSO';
    } else if (this.activeTab === 'right') {
      // COMPLETADAS
      estado = 'COMPLETADA';
    }

    this.voluntariadoService.getActivitiesByOrganization(this.TEST_CIF, estado, estadoAprobacion)
      .subscribe({
        next: (data) => {
          // Map API response to Component Model
          this.volunteeringData = data.map((item: any) => ({
            title: item.nombre,
            organization: 'Mi Organización', // Placeholder as API doesn't return it
            skills: item.habilidades ? (typeof item.habilidades === 'string' ? item.habilidades.split(',') : item.habilidades) : ['General'],
            date: item.fechaInicio ? new Date(item.fechaInicio).toLocaleDateString() : 'Fecha pendiente',
            status: item.estado,
            ods: item.ods || [],
            ...item // Keep original properties
          }));
          console.log('Actividades cargadas y mapeadas:', this.volunteeringData);
        },
        error: (err) => {
          console.error('Error al cargar actividades:', err);
          // Opcional: manejar error en UI
        }
      });
  }

  openCreateModal() {
    this.modalOpen = true;
  }

  onTabChange(tab: 'left' | 'second' | 'middle' | 'right') {
    this.activeTab = tab;

    if (tab === 'left') this.tabLabel = 'Pendientes';
    if (tab === 'second') this.tabLabel = 'En Revisión';
    if (tab === 'middle') this.tabLabel = 'En Curso';
    if (tab === 'right') this.tabLabel = 'Completados';

    this.loadActivities();
  }

  onAction(item: any) {
    console.log('Action in Mis Voluntariados:', item.title);
  }

  onVoluntariadoCreated(newVoluntariado: any) {
    this.loadActivities(); // Reload list after creation
    this.modalOpen = false;
  }
}
