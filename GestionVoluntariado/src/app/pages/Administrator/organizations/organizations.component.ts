import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../../components/Global-Components/navbar/navbar.component';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { AddButtonComponent } from '../../../components/Global-Components/add-button/add-button.component';
import { OrganizationCardComponent } from '../../../components/Administrator/Organizations/organization-card/organization-card.component';
import { OrganizationFormComponent } from '../../../components/Global-Components/organization-form/organization-form.component';
import { OrganizationService } from '../../../services/organization.service';

@Component({
  selector: 'app-organizations',
  standalone: true,
  imports: [
    CommonModule, 
    NavbarComponent, 
    SidebarComponent, 
    StatusToggleComponent, 
    AddButtonComponent,
    OrganizationCardComponent,
    OrganizationFormComponent
  ],
  templateUrl: './organizations.component.html',
  styleUrl: './organizations.component.css'
})
export class OrganizationsComponent {
  private organizationService = inject(OrganizationService);
  organizations = this.organizationService.getOrganizations();
  activeTab: 'left' | 'right' = 'left';

  showModal = false;

  onTabChange(tab: 'left' | 'right') {
    this.activeTab = tab;
  }

  onAddOrganization() {
    this.showModal = true;
  }

  closeModal() {
    this.showModal = false;
  }

  onAccept(org: any) {
    console.log('Accepted', org);
  }

  onReject(org: any) {
    console.log('Rejected', org);
  }
}
