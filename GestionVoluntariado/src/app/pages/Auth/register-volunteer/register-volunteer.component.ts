import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';
import { VolunteerService } from '../../../services/volunteer.service';
import { AuthService } from '../../../services/auth.service';
import { NotificationService } from '../../../services/notification.service';

import { VolunteerFormComponent } from '../../../components/Global-Components/volunteer-form/volunteer-form.component';

@Component({
  selector: 'app-register-volunteer',
  standalone: true,
  imports: [CommonModule, VolunteerFormComponent, RouterLink],
  templateUrl: './register-volunteer.component.html',
  styleUrl: './register-volunteer.component.css'
})
export class RegisterVolunteerComponent {
  private volunteerService = inject(VolunteerService);
  private authService = inject(AuthService);
  private router = inject(Router);
  private notificationService = inject(NotificationService);

  handleRegister(volunteerData: any) {
    // Map data to match backend expectation if needed (similar to Admin view)
    // The form data already matches DTO structure mostly, but we ensure consistency
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
      idiomas: volunteerData.idiomas || [],
      habilidades: volunteerData.habilidades || [],
      intereses: volunteerData.intereses || [],
      disponibilidad: volunteerData.disponibilidad || []
    };


    this.authService.isRegistrationInProgress = true;

    // DIRECT BACKEND REGISTRATION (Thin Client)
    // The backend handles validation and Firebase creation.
    this.volunteerService.createVolunteer(mappedVolunteer).subscribe({
      next: () => {
        this.notificationService.showSuccessPopup(
          'Registro completado',
          'Tu cuenta ha sido creada con éxito. Ya puedes iniciar sesión.'
        ).then(() => {
          this.authService.isRegistrationInProgress = false;
          this.router.navigate(['/login']);
        });
      },
      error: (error) => {
        console.error('Error during backend registration:', error);
        this.authService.isRegistrationInProgress = false;
        // Display validation error from backend (e.g. "DNI/NIE inválido", "Debes tener al menos 16 años")
        const backendMsg = error.error?.error || 'Inténtalo de nuevo.';
        this.notificationService.showError('Error en el registro: ' + backendMsg);
      }
    });
  }
}
