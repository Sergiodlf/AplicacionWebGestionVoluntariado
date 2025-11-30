import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../../components/Global-Components/navbar/navbar.component';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { VolunteeringCardComponent } from '../../../components/Administrator/Volunteers/volunteering-card/volunteering-card.component';

@Component({
  selector: 'app-volunteers-list',
  standalone: true,
  imports: [
    CommonModule,
    NavbarComponent,
    SidebarComponent,
    StatusToggleComponent,
    VolunteeringCardComponent
  ],
  templateUrl: './volunteers-list.component.html',
  styleUrl: './volunteers-list.component.css'
})
export class VolunteersListComponent {
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
