import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';
import { VolunteerService } from '../../../services/volunteer.service';

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
  private router = inject(Router);

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

    this.volunteerService.createVolunteer(mappedVolunteer).subscribe({
      next: () => {
        alert('Registro completado con éxito. Por favor inicia sesión.');
        this.router.navigate(['/login']);
      },
      error: (error) => {
        console.error('Error during registration:', error);
        alert('Error en el registro: ' + (error.error?.error || 'Inténtalo de nuevo.'));
      }
    });
  }
}
