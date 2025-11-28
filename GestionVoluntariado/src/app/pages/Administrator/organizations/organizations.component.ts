import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../../components/Global-Components/navbar/navbar.component';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { AddButtonComponent } from '../../../components/Global-Components/add-button/add-button.component';
import { OrganizationCardComponent } from '../../../components/Administrator/Organizations/organization-card/organization-card.component';

@Component({
  selector: 'app-organizations',
  standalone: true,
  imports: [
    CommonModule, 
    NavbarComponent, 
    SidebarComponent, 
    StatusToggleComponent, 
    AddButtonComponent,
    OrganizationCardComponent
  ],
  templateUrl: './organizations.component.html',
  styleUrl: './organizations.component.css'
})
export class OrganizationsComponent {
  activeTab: 'left' | 'right' = 'left';

  organizations = [
    {
      name: 'Fundación Ayuda',
      type: 'ONG',
      location: 'Madrid',
      description: 'Ayuda a personas sin hogar',
      tags: ['Cocina', 'Logística']
    },
    {
      name: 'Centro Cultural',
      type: 'Cultural',
      location: 'Valencia',
      description: 'Promoción de la cultura local',
      tags: ['Arte', 'Música']
    }
  ];

  onTabChange(tab: 'left' | 'right') {
    this.activeTab = tab;
  }

  onAddOrganization() {
    console.log('Add organization clicked');
  }

  onAccept(org: any) {
    console.log('Accepted', org);
  }

  onReject(org: any) {
    console.log('Rejected', org);
  }
}
