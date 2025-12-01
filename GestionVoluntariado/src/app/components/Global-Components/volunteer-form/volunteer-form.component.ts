import { Component, EventEmitter, Output, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-volunteer-form',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './volunteer-form.component.html',
  styleUrl: './volunteer-form.component.css'
})
export class VolunteerFormComponent {
  @Input() submitLabel: string = 'Registrarme';
  @Output() onSubmit = new EventEmitter<void>();

  addedSkills: string[] = [];

  addSkill() {
    const skill = prompt('Ingrese una habilidad:');
    if (skill) {
      this.addedSkills.push(skill);
    }
  }

  addInterest() {
    const interest = prompt('Ingrese un interés:');
    if (interest) {
      this.addedSkills.push(interest);
    }
  }

  removeSkill(index: number) {
    this.addedSkills.splice(index, 1);
  }

  submit() {
    this.onSubmit.emit();
  }
}
