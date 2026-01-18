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
        console.log('Registrando organización:', org);

        // 1. Register in Firebase (also sends verification email)
        this.authService.register(org.email, org.password)
            .then(() => {
                const currentUser = this.authService.getCurrentUser();
                if (currentUser) {
                    // 2. Save Role to Firestore (for Android compatibility)
                    this.authService.saveUserRole(currentUser.uid, org.email, 'organizacion')
                        .then(() => {
                            // 3. Register in Backend
                            this.organizationService.createOrganization(org).subscribe({
                                next: () => {
                                    alert('Organización registrada con éxito. Se ha enviado un correo de verificación.');
                                    this.router.navigate(['/login']);
                                },
                                error: (error) => {
                                    console.error('Error during backend registration:', error);
                                    alert('Error en el registro del backend: ' + (error.error?.error || 'Inténtalo de nuevo.'));
                                }
                            });
                        })
                        .catch((error) => {
                            console.error('Error saving role to Firestore:', error);
                            alert('Error guardando datos de usuario: ' + error.message);
                        });
                }
            })
            .catch((error) => {
                console.error('Error during Firebase registration:', error);
                if (error.code === 'auth/email-already-in-use') {
                    alert('El correo electrónico ya está en uso.');
                } else if (error.code === 'auth/weak-password') {
                    alert('La contraseña es muy débil (mínimo 6 caracteres).');
                } else {
                    alert('Error en el registro de Firebase: ' + error.message);
                }
            });
    }
}
