import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';

import { VolunteerFormComponent } from '../../../components/Global-Components/volunteer-form/volunteer-form.component';

@Component({
  selector: 'app-register-volunteer',
  standalone: true,
  imports: [CommonModule, VolunteerFormComponent, RouterLink],
  templateUrl: './register-volunteer.component.html',
  styleUrl: './register-volunteer.component.css'
})
export class RegisterVolunteerComponent {
  // Logic moved to VolunteerFormComponent
}
