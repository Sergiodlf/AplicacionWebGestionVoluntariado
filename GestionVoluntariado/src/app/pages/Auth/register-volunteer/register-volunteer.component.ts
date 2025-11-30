import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-register-volunteer',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './register-volunteer.component.html',
  styleUrl: './register-volunteer.component.css'
})
export class RegisterVolunteerComponent {
  addedSkills = ['Educación', 'Comunicación', 'Inglés', 'Lunes', 'De 7 a 8']; // Mock data for now
}
