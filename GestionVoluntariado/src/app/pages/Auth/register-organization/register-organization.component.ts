import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';

import { OrganizationFormComponent } from '../../../components/Global-Components/organization-form/organization-form.component';

@Component({
    selector: 'app-register-organization',
    standalone: true,
    imports: [CommonModule, RouterLink, OrganizationFormComponent],
    templateUrl: './register-organization.component.html',
    styleUrl: './register-organization.component.css'
})
export class RegisterOrganizationComponent {
    constructor(private router: Router) { }

    onRegistrationSuccess(org: any) {
        console.log('Organización registrada:', org);
        this.router.navigate(['/login']);
    }
}
