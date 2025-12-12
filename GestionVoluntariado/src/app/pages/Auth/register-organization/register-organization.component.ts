import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { OrganizationFormComponent } from '../../../components/Global-Components/organization-form/organization-form.component';
import { Organization } from '../../../models/organizationModel';
import { Router } from '@angular/router';

@Component({
  selector: 'app-register-organization',
  standalone: true,
  imports: [CommonModule, OrganizationFormComponent],
  templateUrl: './register-organization.component.html',
  styleUrl: './register-organization.component.css'
})
export class RegisterOrganizationComponent {
  private router = inject(Router);

  constructor() { }

onRegistrationSuccess(org: Organization): void {
    console.log("Registro exitoso para:", org.nombre);
    
    // 1. Mostrar un mensaje de éxito temporal al usuario (Opcional)
     alert(`¡Registro completado! Su organización (${org.nombre}) está Pendiente de aprobación.`);

    // 2. Redirigir al login
    // Asumo que tu ruta de login es '/login' o similar
    this.router.navigate(['/login']); 
  }
}
