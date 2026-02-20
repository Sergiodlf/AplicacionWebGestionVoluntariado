import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';
import { AuthService } from '../../../services/auth.service';
import { OrganizationService } from '../../../services/organization.service';

import { OrganizationFormComponent } from '../../../components/Global-Components/organization-form/organization-form.component';

@Component({
    selector: 'app-register-organization',
    standalone: true,
    imports: [CommonModule, RouterLink, OrganizationFormComponent],
    templateUrl: './register-organization.component.html',
    styleUrl: './register-organization.component.css'
})


export class RegisterOrganizationComponent {
    constructor(
        private router: Router,
        private authService: AuthService,
        private organizationService: OrganizationService
    ) { }

    onRegistrationSuccess(org: any) {

        this.authService.isRegistrationInProgress = true;

        // DIRECT BACKEND REGISTRATION (Thin Client)
        this.organizationService.createOrganization(org).subscribe({
            next: () => {
                alert('Organización registrada con éxito. Ya puedes iniciar sesión.');
                this.authService.isRegistrationInProgress = false;
                this.router.navigate(['/login']);
            },
            error: (error) => {
                console.error('Error during backend registration:', error);
                this.authService.isRegistrationInProgress = false;
                const backendMsg = error.error?.error || 'Inténtalo de nuevo.';
                alert('Error en el registro: ' + backendMsg);
            }
        });
    }
}
