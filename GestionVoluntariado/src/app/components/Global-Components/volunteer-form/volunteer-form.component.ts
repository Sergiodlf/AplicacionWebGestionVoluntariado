import { Component, EventEmitter, Output, Input, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';

@Component({
  selector: 'app-volunteer-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './volunteer-form.component.html',
  styleUrl: './volunteer-form.component.css'
})
export class VolunteerFormComponent {
  @Input() submitLabel: string = 'Registrarme';
  @Output() onSubmit = new EventEmitter<any>();
  errorMessage: string = '';

  private fb = inject(FormBuilder);

  ngOnInit() {
    console.log('VolunteerFormComponent initialized');
  }

  volunteerForm: FormGroup = this.fb.group({
    nombreCompleto: ['', Validators.required],
    dni: ['', [Validators.required, Validators.maxLength(9)]],
    correo: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
    zona: [''],
    ciclo: [''],
    fechaNacimiento: [''],
    experiencia: [''],
    coche: [''],
    idiomas: [[]],
    habilidades: [[]],
    intereses: [[]],
    disponibilidad: [[]]
  });

  availableSkills: string[] = ['Programación', 'Diseño Gráfico', 'Redes Sociales', 'Gestión de Eventos', 'Docencia', 'Primeros Auxilios', 'Cocina', 'Conducción', 'Idiomas', 'Música'];
  availableInterests: string[] = ['Medio Ambiente', 'Educación', 'Salud', 'Animales', 'Cultura', 'Deporte', 'Tecnología', 'Derechos Humanos', 'Mayores', 'Infancia'];
  availableAvailability: string[] = ['Lunes Mañana', 'Lunes Tarde', 'Martes Mañana', 'Martes Tarde', 'Miércoles Mañana', 'Miércoles Tarde', 'Jueves Mañana', 'Jueves Tarde', 'Viernes Mañana', 'Viernes Tarde', 'Fines de Semana'];

  addedSkills: string[] = [];
  addedInterests: string[] = [];
  addedAvailability: string[] = [];

  toggleSkill(skill: string) {
    const index = this.addedSkills.indexOf(skill);
    if (index === -1) {
      this.addedSkills.push(skill);
    } else {
      this.addedSkills.splice(index, 1);
    }
    this.volunteerForm.patchValue({ habilidades: this.addedSkills });
  }

  toggleInterest(interest: string) {
    const index = this.addedInterests.indexOf(interest);
    if (index === -1) {
      this.addedInterests.push(interest);
    } else {
      this.addedInterests.splice(index, 1);
    }
    this.volunteerForm.patchValue({ intereses: this.addedInterests });
  }

  toggleAvailability(availability: string) {
    const index = this.addedAvailability.indexOf(availability);
    if (index === -1) {
      this.addedAvailability.push(availability);
    } else {
      this.addedAvailability.splice(index, 1);
    }
    this.volunteerForm.patchValue({ disponibilidad: this.addedAvailability });
  }

  isSkillSelected(skill: string): boolean {
    return this.addedSkills.includes(skill);
  }

  isInterestSelected(interest: string): boolean {
    return this.addedInterests.includes(interest);
  }

  isAvailabilitySelected(availability: string): boolean {
    return this.addedAvailability.includes(availability);
  }

  onRegister() {
    this.errorMessage = ''; // Clear previous errors
    try {
      console.log('Register called');

      if (this.volunteerForm.valid) {
        this.onSubmit.emit(this.volunteerForm.value);
      } else {
        console.log('Form is invalid. Controls errors:');
        let errorMsg = 'Por favor, revise los siguientes campos obligatorios:\n';
        Object.keys(this.volunteerForm.controls).forEach(key => {
          const controlErrors = this.volunteerForm.get(key)?.errors;
          if (controlErrors) {
            // Translate key to human readable if possible or just use key
            const errorString = '- ' + key;
            console.log(errorString);
            errorMsg += errorString + '\n';
          }
        });
        this.errorMessage = errorMsg;
        // Scroll to error message if needed, but it's near the button so it should be visible
      }
    } catch (error) {
      console.error('Error in onRegister:', error);
      this.errorMessage = 'Error crítico: ' + error;
    }
  }
}
