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

    console.log('Registering volunteer:', mappedVolunteer);

    // 1. Register in Firebase (also sends verification email)
    this.authService.isRegistrationInProgress = true;
    this.authService.register(mappedVolunteer.email, mappedVolunteer.password)
      .then(() => {
        const currentUser = this.authService.getCurrentUser();
        if (currentUser) {
          // 2. Save Role to Firestore (for Android compatibility)
          // Note: Android LoginFragment checks 'usuarios' collection and 'rol' field.
          this.authService.saveUserRole(currentUser.uid, mappedVolunteer.email, 'voluntario')
            .then(() => {
              // 3. Register in Backend
              this.volunteerService.createVolunteer(mappedVolunteer).subscribe({
                next: () => {
                  this.notificationService.showSuccessPopup(
                    'Registro completado',
                    'Tu cuenta ha sido creada con éxito. Se ha enviado un correo de verificación.'
                  ).then(() => {
                    this.authService.isRegistrationInProgress = false;
                    this.router.navigate(['/login']);
                  });
                },
                error: (error) => {
                  console.error('Error during backend registration:', error);
                  this.authService.isRegistrationInProgress = false;
                  this.notificationService.showError('Error en el registro del backend: ' + (error.error?.error || 'Inténtalo de nuevo.'));
                }
              });
            })
            .catch((error) => {
              console.error('Error saving role to Firestore:', error);
              this.authService.isRegistrationInProgress = false;
              this.notificationService.showError('Error guardando datos de usuario: ' + error.message);
            });
        }
      })
      .catch((error) => {
        console.error('Error during Firebase registration:', error);
        this.authService.isRegistrationInProgress = false;
        if (error.code === 'auth/email-already-in-use') {
          this.notificationService.showError('El correo electrónico ya está en uso.');
        } else if (error.code === 'auth/weak-password') {
          this.notificationService.showError('La contraseña es muy débil (mínimo 6 caracteres).');
        } else {
          this.notificationService.showError('Error en el registro de Firebase: ' + error.message);
        }
      });
  }
}
