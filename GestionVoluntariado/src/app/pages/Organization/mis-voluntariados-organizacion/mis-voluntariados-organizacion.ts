import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../../components/Global-Components/navbar/navbar.component';
import { StatusToggleVoluntariado } from '../../../components/Volunteer/status-toggle-voluntariado/status-toggle-voluntariado';
import { VoluntariadoCard } from '../../../components/Volunteer/voluntariado-card/voluntariado-card';
import { CrearVoluntariadoModal } from '../../../components/organization/crear-voluntariado-modal/crear-voluntariado-modal';

@Component({
  selector: 'app-mis-voluntariados-organizacion',
  imports: [CommonModule, NavbarComponent, StatusToggleVoluntariado, VoluntariadoCard, CrearVoluntariadoModal],
  templateUrl: './mis-voluntariados-organizacion.html',
  styleUrl: './mis-voluntariados-organizacion.css',
})
export class MisVoluntariadosOrganizacion {
  activeTab: 'left' | 'middle' | 'right' = 'left';

  tabLabel = 'Pendientes';

  volunteeringData = [
    {
      title: 'Ayudar en Residencia',
      organization: 'Fundación Ayuda',
      skills: ['Comunicación', 'Asistencia'],
      date: '14/02/2026',
      status: 'Pendiente',
      ods: [
        { id: 3, name: 'ODS 3', color: '#00c851' },
        { id: 1, name: 'ODS 1', color: '#ff4444' },
      ],
    },
    {
      title: 'Logística de Evento',
      organization: 'Centro Cultural',
      skills: ['Organización'],
      date: '12/03/2026',
      status: 'En Curso',
      ods: [{ id: 4, name: 'ODS 4', color: '#33b5e5' }],
    },
    {
      title: 'Ayuda en Comedor',
      organization: 'Fundación Manos',
      skills: ['Cocina'],
      date: '08/01/2026',
      status: 'Completado',
      ods: [{ id: 2, name: 'ODS 2', color: '#ffbb33' }],
    },
  ];

  get filteredVolunteering() {
    switch (this.activeTab) {
      case 'left':
        return this.volunteeringData.filter((x) => x.status === 'Pendiente');
      case 'middle':
        return this.volunteeringData.filter((x) => x.status === 'En Curso');
      case 'right':
        return this.volunteeringData.filter((x) => x.status === 'Completado');
      default:
        return this.volunteeringData;
    }
  }

  modalOpen = false;

  openCreateModal() {
    this.modalOpen = true;
  }

  onTabChange(tab: 'left' | 'middle' | 'right') {
    this.activeTab = tab;

    if (tab === 'left') this.tabLabel = 'Pendientes';
    if (tab === 'middle') this.tabLabel = 'En Curso';
    if (tab === 'right') this.tabLabel = 'Completados';
  }

  onAction(item: any) {
    console.log('Action in Mis Voluntariados:', item.title);
  }

  onVoluntariadoCreated(newVoluntariado: any) {
    this.volunteeringData.push(newVoluntariado);
    this.modalOpen = false;
  }
}
