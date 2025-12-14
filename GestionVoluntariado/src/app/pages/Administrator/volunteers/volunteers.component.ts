import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../../components/Global-Components/navbar/navbar.component';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { AddButtonComponent } from '../../../components/Global-Components/add-button/add-button.component';
import { VolunteerCardComponent } from '../../../components/Administrator/Volunteers/volunteer-card/volunteer-card.component';
import { VolunteerFormComponent } from '../../../components/Global-Components/volunteer-form/volunteer-form.component';
import { Observable, BehaviorSubject, of } from 'rxjs';
import { map, switchMap, catchError, tap } from 'rxjs/operators';
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
  private refresh$ = new BehaviorSubject<void>(void 0);

  // Main stream that reacts to refresh$
  volunteers$ = this.refresh$.pipe(
    switchMap(() => this.volunteerService.getVolunteers().pipe(
      tap(data => console.log('Fetched volunteers:', data)),
      catchError(err => {
        console.error('Error fetching volunteers:', err);
        return of([]); // Keep the stream alive with empty list
      })
    ))
  );

  activeTab: 'left' | 'right' = 'left';

  // Derived filtered streams (depend on volunteers$)
  // Derived filtered streams (depend on volunteers$)
  pendingVolunteers$ = this.volunteers$.pipe(map(list => list.filter(v => v.status === 'PENDIENTE')));
  approvedVolunteers$ = this.volunteers$.pipe(map(list => list.filter(v => v.status === 'ACEPTADO')));

  showModal = false;

  onTabChange(tab: 'left' | 'right') {
    this.activeTab = tab;
  }

  // No longer needed as streams are reactive
  // private reinitFilters() {}

  onAddVolunteer() {
    this.showModal = true;
  }

  closeModal() {
    this.showModal = false;
  }

  onAccept(volunteer: any) {
    if (!volunteer.dni) return console.error('Missing DNI');
    this.volunteerService.updateStatus(volunteer.dni, 'ACEPTADO').subscribe({
      next: () => this.refresh$.next(),
      error: (err) => console.error('Error updating status:', err)
    });
  }

  onReject(volunteer: any) {
    if (!volunteer.dni) return console.error('Missing DNI');
    this.volunteerService.updateStatus(volunteer.dni, 'RECHAZADO').subscribe({
      next: () => this.refresh$.next(),
      error: (err) => console.error('Error updating status:', err)
    });
  }

  handleCreateVolunteer(volunteerData: any) {
    console.log('Parent received volunteer data:', volunteerData);
    // Map form data to backend expected structure 
    const mappedVolunteer = {
      nombre: volunteerData.nombreCompleto,
      email: volunteerData.correo,
      dni: volunteerData.dni,
      password: volunteerData.password,
      zona: volunteerData.zona,
      ciclo: volunteerData.ciclo,
      fechaNacimiento: volunteerData.fechaNacimiento,
      experiencia: volunteerData.experiencia,
      coche: volunteerData.coche,
      idiomas: volunteerData.idiomas,
      habilidades: volunteerData.habilidades,
      intereses: volunteerData.intereses,
      // Convert availability array to string if needed, or keep as array if backend supports it. 
      // Based on 500 error history and typical SQL issues, string is safer or backend needs to handle array.
      // Trying String join first as Service mock used String.
      disponibilidad: Array.isArray(volunteerData.disponibilidad) ? volunteerData.disponibilidad.join(', ') : volunteerData.disponibilidad
    };

    console.log('Sending mapped volunteer data:', mappedVolunteer);
    this.volunteerService.createVolunteer(mappedVolunteer).subscribe({
      next: (response) => {
        console.log('Volunteer created successfully', response);
        // this.volunteerService.addVolunteerToSignal(volunteerData); // Optimistic update or use response
        // Reload list or trust optimistic if we reimplement it. For now, simple reload or no-op.
        // To refresh, we can unwrap/rewrap or use a BehaviorSubject in service. 
        // For simplicity: Just keep as is, data will persist on navigation, or we should refresh the list.
        this.refresh$.next();
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
