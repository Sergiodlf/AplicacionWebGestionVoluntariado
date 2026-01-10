import { Component, EventEmitter, Output, Input, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { VolunteerService } from '../../../services/volunteer.service';

@Component({
  selector: 'app-volunteer-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './volunteer-form.component.html',
  styleUrl: './volunteer-form.component.css'
})
export class VolunteerFormComponent implements OnInit {
  @Input() submitLabel: string = 'Registrarme';
  @Output() onSubmit = new EventEmitter<any>();
  errorMessage: string = '';

  private fb = inject(FormBuilder);
  private volunteerService = inject(VolunteerService);

  ngOnInit() {
    console.log('VolunteerFormComponent initialized');
    this.volunteerService.getCiclos().subscribe({
      next: (data) => this.availableCiclos = data,
      error: (err) => console.error('Error fetching cycles:', err)
    });
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

  availableZones: string[] = [
    'Casco Viejo', 'Ensanche', 'San Juan', 'Iturrama', 'Rochapea',
    'Txantrea', 'Azpiligaña', 'Milagrosa', 'Buztintxuri', 'Mendillorri',
    'Sarriguren', 'Barañáin', 'Burlada', 'Villava', 'Uharte',
    'Berriozar', 'Ansoáin', 'Noáin', 'Zizur Mayor', 'Mutilva'
  ];
  availableCiclos: any[] = []; // Will be populated from DB

  availableSkills: string[] = ['Programación', 'Diseño Gráfico', 'Redes Sociales', 'Gestión de Eventos', 'Docencia', 'Primeros Auxilios', 'Cocina', 'Conducción', 'Música', 'Marketing'];
  availableInterests: string[] = ['Medio Ambiente', 'Educación', 'Salud', 'Animales', 'Cultura', 'Deporte', 'Tecnología', 'Derechos Humanos', 'Mayores', 'Infancia'];
  availableAvailability: string[] = ['Lunes Mañana', 'Lunes Tarde', 'Martes Mañana', 'Martes Tarde', 'Miércoles Mañana', 'Miércoles Tarde', 'Jueves Mañana', 'Jueves Tarde', 'Viernes Mañana', 'Viernes Tarde', 'Fines de Semana'];
  availableLanguages: string[] = ['Español', 'Inglés', 'Francés', 'Alemán', 'Italiano', 'Portugués', 'Euskera', 'Catalán', 'Gallego', 'Chino'];

  addedSkills: string[] = [];
  addedInterests: string[] = [];
  addedAvailability: string[] = [];
  addedIdiomas: string[] = [];

  // Generic toggle method for all lists
  addItem(listName: 'habilidades' | 'intereses' | 'disponibilidad' | 'idiomas', value: string) {
    if (!value) return;

    let targetList: string[] = [];
    if (listName === 'habilidades') targetList = this.addedSkills;
    if (listName === 'intereses') targetList = this.addedInterests;
    if (listName === 'disponibilidad') targetList = this.addedAvailability;
    if (listName === 'idiomas') targetList = this.addedIdiomas;

    if (!targetList.includes(value)) {
      targetList.push(value);
      this.volunteerForm.patchValue({ [listName]: targetList });
    }
  }

  removeItem(listName: 'habilidades' | 'intereses' | 'disponibilidad' | 'idiomas', value: string) {
    let targetList: string[] = [];
    if (listName === 'habilidades') targetList = this.addedSkills;
    if (listName === 'intereses') targetList = this.addedInterests;
    if (listName === 'disponibilidad') targetList = this.addedAvailability;
    if (listName === 'idiomas') targetList = this.addedIdiomas;

    const index = targetList.indexOf(value);
    if (index !== -1) {
      targetList.splice(index, 1);
      this.volunteerForm.patchValue({ [listName]: targetList });
    }
  }

  // Specific getters for template helper (optional, can just use arrays directly)

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
