import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../../components/Global-Components/navbar/navbar.component';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { AddButtonComponent } from '../../../components/Global-Components/add-button/add-button.component';
import { VolunteerCardComponent } from '../../../components/Administrator/Volunteers/volunteer-card/volunteer-card.component';

@Component({
  selector: 'app-volunteers',
  standalone: true,
  imports: [
    CommonModule, 
    NavbarComponent, 
    SidebarComponent, 
    StatusToggleComponent, 
    AddButtonComponent,
    VolunteerCardComponent
  ],
  templateUrl: './volunteers.component.html',
  styleUrl: './volunteers.component.css'
})
export class VolunteersComponent {
  activeTab: 'left' | 'right' = 'left';

  volunteers = [
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
  ];

  onTabChange(tab: 'left' | 'right') {
    this.activeTab = tab;
  }

  onAddVolunteer() {
    console.log('Add volunteer clicked');
  }

  onAccept(volunteer: any) {
    console.log('Accepted', volunteer);
  }

  onReject(volunteer: any) {
    console.log('Rejected', volunteer);
  }
}
