import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { OrganizationFormComponent } from '../../../components/Global-Components/organization-form/organization-form.component';

@Component({
  selector: 'app-register-organization',
  standalone: true,
  imports: [CommonModule, OrganizationFormComponent],
  templateUrl: './register-organization.component.html',
  styleUrl: './register-organization.component.css'
})
export class RegisterOrganizationComponent {

}
