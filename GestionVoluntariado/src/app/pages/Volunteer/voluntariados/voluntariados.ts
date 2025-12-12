import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../../components/Global-Components/navbar/navbar.component';
import { SidebarVolunteer } from '../../../components/Volunteer/sidebar-volunteer/sidebar-volunteer';
import { VoluntariadoService } from '../../../services/voluntariado-service';
import { VoluntariadoCard } from '../../../components/Volunteer/voluntariado-card/voluntariado-card';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';

@Component({
  selector: 'app-voluntariados',
  imports: [CommonModule, NavbarComponent, SidebarVolunteer, VoluntariadoCard, StatusToggleComponent],
  templateUrl: './voluntariados.html',
  styleUrl: './voluntariados.css',
})
export class Voluntariados {
  //private voluntariadoService = inject(VoluntariadoService);
  //voluntariados = this.voluntariadoService.getVoluntariados();


  activeTab: 'left' | 'right' = 'left';

  volunteeringOpportunities = [
    {
      title: 'Cocina',
      organization: 'Fundación Ayuda',
      skills: ['Educación', 'Comunicación'],
      date: '24/11/2025',
      ods: [
        { id: 4, name: 'ODS 4', color: '#00c851' }, // Green
        { id: 7, name: 'ODS 7', color: '#ffbb33' }  // Orange
      ]
    },
    {
      title: 'Logística',
      organization: 'Fundación Ayuda',
      skills: ['Educación', 'Comunicación'],
      date: '24/11/2025',
      ods: [
        { id: 4, name: 'ODS 4', color: '#00c851' },
        { id: 7, name: 'ODS 7', color: '#ffbb33' }
      ]
    },
    {
      title: 'Arte',
      organization: 'Centro Cultural',
      skills: ['Educación', 'Comunicación'],
      date: '24/11/2025',
      ods: [
        { id: 4, name: 'ODS 4', color: '#00c851' },
        { id: 7, name: 'ODS 7', color: '#ffbb33' }
      ]
    },
    {
      title: 'Música',
      organization: 'Centro Cultural',
      skills: ['Educación', 'Comunicación'],
      date: '24/11/2025',
      ods: [
        { id: 4, name: 'ODS 4', color: '#00c851' },
        { id: 7, name: 'ODS 7', color: '#ffbb33' }
      ]
    }
  ];

  onTabChange(tab: 'left' | 'right') {
    this.activeTab = tab;
  }

  onAction(item: any) {
    console.log('Action clicked for:', item.title);
  }
}
