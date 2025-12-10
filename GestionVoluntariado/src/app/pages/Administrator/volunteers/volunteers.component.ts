import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../../components/Global-Components/navbar/navbar.component';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { AddButtonComponent } from '../../../components/Global-Components/add-button/add-button.component';
import { VolunteerCardComponent } from '../../../components/Administrator/Volunteers/volunteer-card/volunteer-card.component';
import { VolunteerFormComponent } from '../../../components/Global-Components/volunteer-form/volunteer-form.component';
import { VolunteerService } from '../../../services/volunteer.service';

@Component({
  selector: 'app-volunteers',
  standalone: true,
  imports: [
    CommonModule,
    NavbarComponent,
    SidebarComponent,
    StatusToggleComponent,
    AddButtonComponent,
    VolunteerCardComponent,
    VolunteerFormComponent
  ],
  templateUrl: './volunteers.component.html',
  styleUrl: './volunteers.component.css'
})
export class VolunteersComponent {
  private volunteerService = inject(VolunteerService);
  volunteers = this.volunteerService.getVolunteers();
  activeTab: 'left' | 'right' = 'left';

  showModal = false;

  onTabChange(tab: 'left' | 'right') {
    this.activeTab = tab;
  }

  onAddVolunteer() {
    this.showModal = true;
  }

  closeModal() {
    this.showModal = false;
  }

  onAccept(volunteer: any) {
    console.log('Accepted', volunteer);
  }

  onReject(volunteer: any) {
    console.log('Rejected', volunteer);
  }

  handleCreateVolunteer(volunteerData: any) {
    console.log('Parent received volunteer data:', volunteerData);
    this.volunteerService.createVolunteer(volunteerData).subscribe({
      next: (response) => {
        console.log('Volunteer created successfully', response);
        this.volunteerService.addVolunteerToSignal(volunteerData); // Optimistic update or use response
        this.closeModal();
        alert('Voluntario creado con éxito');
      },
      error: (error) => {
        console.error('Error creating volunteer', error);
        alert('Error al crear voluntario: ' + JSON.stringify(error));
      }
    });
  }
}
