import { Component, EventEmitter, Output, Input, inject, OnInit, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators, FormControl } from '@angular/forms';
import { VolunteerService } from '../../../services/volunteer.service';
import { CategoryService } from '../../../services/category.service';
import { Category } from '../../../models/Category';

@Component({
  selector: 'app-volunteer-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule],
  templateUrl: './volunteer-form.component.html',
  styleUrl: './volunteer-form.component.css'
})
export class VolunteerFormComponent implements OnInit, OnChanges {
  @Input() submitLabel: string = 'Registrarme';
  @Input() isModal: boolean = true;
  @Input() isEdit: boolean = false;
  @Input() initialData: any = null;
  @Input() hideSubmitButton: boolean = false;
  @Input() disableAutoUpdate: boolean = false; // New Input
  @Output() onSubmit = new EventEmitter<any>();
  errorMessage: string = '';

  private fb = inject(FormBuilder);
  private volunteerService = inject(VolunteerService);
  private categoryService = inject(CategoryService);

  availableCiclos: any[] = [];
  availableSkills: Category[] = [];
  availableInterests: Category[] = [];

  availableDays: string[] = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
  availableSlots: string[] = ['Mañana', 'Tarde', 'Noche', 'Todo el día'];

  selectedDay: string = '';
  selectedSlot: string = '';
  selectedIdioma: string = '';

  availableLanguages: string[] = ['Español', 'Inglés', 'Francés', 'Alemán', 'Italiano', 'Portugués', 'Euskera', 'Catalán', 'Gallego', 'Chino'];
  availableZones: string[] = [
    'Casco Viejo', 'Ensanche', 'San Juan', 'Iturrama', 'Rochapea',
    'Txantrea', 'Azpiligaña', 'Milagrosa', 'Buztintxuri', 'Mendillorri',
    'Sarriguren', 'Barañáin', 'Burlada', 'Villava', 'Uharte',
    'Berriozar', 'Ansoáin', 'Noáin', 'Zizur Mayor', 'Mutilva'
  ];

  volunteerForm!: FormGroup;

  addedSkills: Category[] = [];
  addedInterests: Category[] = [];
  addedAvailability: string[] = [];
  addedIdiomas: string[] = [];

  ngOnInit() {
    this.initForm();
    this.addedIdiomas = [];
    this.addedAvailability = [];

    this.volunteerService.getCiclos().subscribe({
      next: (data) => this.availableCiclos = data,
      error: (err) => console.error('Error fetching cycles:', err)
    });

    this.categoryService.getHabilidades().subscribe(data => {
      this.availableSkills = data;
      this.loadInitialData(); // Try matching skills after they are loaded
    });
    this.categoryService.getIntereses().subscribe(data => {
      this.availableInterests = data;
      this.loadInitialData(); // Try matching interests after they are loaded
    });
  }

  ngOnChanges(changes: SimpleChanges) {
    // React to initialData changes
    if (changes['initialData'] && !changes['initialData'].firstChange) {
      this.loadInitialData();
    }
  }

  private initForm() {
    this.volunteerForm = this.fb.group({
      nombreCompleto: ['', this.isEdit ? [] : Validators.required],
      dni: ['', this.isEdit ? [] : [Validators.required, Validators.maxLength(9)]], // Removed regex pattern
      correo: ['', this.isEdit ? [] : [Validators.required, Validators.email]],
      password: ['', this.isEdit ? [] : [Validators.required]], // Removed minLength(6)
      zona: ['', Validators.required],
      ciclo: ['', Validators.required],
      fechaNacimiento: ['', this.isEdit ? [] : Validators.required],
      experiencia: [''],
      coche: [''],
      idiomas: new FormControl<string[]>([]),
      habilidades: new FormControl<number[]>([]),
      intereses: new FormControl<number[]>([]),
      disponibilidad: new FormControl<string[]>([])
    });
  }

  private loadInitialData() {
    if (!this.isEdit || !this.initialData) return;

    // Determine ciclo value - handle string, object, or null
    let cicloValue = '';
    if (this.initialData.ciclo) {
      if (typeof this.initialData.ciclo === 'string') {
        cicloValue = this.initialData.ciclo;
      } else if (this.initialData.ciclo.nombre) {
        cicloValue = this.initialData.ciclo.nombre;
      }
      
      // Remove the (Xº) suffix if present to match dropdown options
      // Backend returns "Administración y Finanzas (2º)"
      // But dropdown has "Administración y Finanzas"
      cicloValue = cicloValue.replace(/\s*\(\d+º\)\s*$/, '').trim();
    }

    // Direct field mapping
    this.volunteerForm.patchValue({
      nombreCompleto: this.initialData.nombreCompleto || (this.initialData.nombre && this.initialData.apellido1 ? `${this.initialData.nombre} ${this.initialData.apellido1}`.trim() : ''),
      dni: this.initialData.dni,
      correo: this.initialData.correo || this.initialData.email,
      zona: this.initialData.zona,
      ciclo: cicloValue,
      fechaNacimiento: this.initialData.fechaNacimiento,
      experiencia: this.initialData.experiencia || this.initialData.experience,
      coche: this.initialData.coche === true || this.initialData.coche === 'Si' || this.initialData.hasCar === true ? 'Si' : 
             (this.initialData.coche === false || this.initialData.coche === 'No' || this.initialData.hasCar === false ? 'No' : this.initialData.coche)
    });


    // Habilidades mapping
    if (this.initialData.habilidades && this.availableSkills.length > 0) {
      this.addedSkills = this.availableSkills.filter(s =>
        this.initialData.habilidades.some((h: any) => (h.id === s.id || h === s.id || h.nombre === s.nombre))
      );
      this.volunteerForm.patchValue({ habilidades: this.addedSkills.map(s => s.id) });
    }

    // Intereses mapping
    if (this.initialData.intereses && this.availableInterests.length > 0) {
      this.addedInterests = this.availableInterests.filter(i =>
        this.initialData.intereses.some((int: any) => (int.id === i.id || int === i.id || int.nombre === i.nombre))
      );
      this.volunteerForm.patchValue({ intereses: this.addedInterests.map(i => i.id) });
    }

    // Idiomas mapping - always reload
    if (this.initialData.idiomas) {
      this.addedIdiomas = [...this.initialData.idiomas];
      this.volunteerForm.patchValue({ idiomas: this.addedIdiomas });
    }

    // Disponibilidad mapping - always reload
    if (this.initialData.disponibilidad) {
      this.addedAvailability = Array.isArray(this.initialData.disponibilidad)
        ? [...this.initialData.disponibilidad]
        : [this.initialData.disponibilidad];
      this.volunteerForm.patchValue({ disponibilidad: this.addedAvailability });
    }
  }

  get canAddAvailability(): boolean {
    return !!this.selectedDay && !!this.selectedSlot;
  }

  // Generic toggle method for all lists
  addItem(listName: 'habilidades' | 'intereses' | 'disponibilidad' | 'idiomas', value: any) {
    if (!value && listName !== 'disponibilidad') return;

    if (listName === 'habilidades') {
      if (!this.addedSkills.find(s => s.id === value.id)) {
        this.addedSkills.push(value);
        this.volunteerForm.patchValue({ habilidades: this.addedSkills.map(s => s.id) });
      }
    } else if (listName === 'intereses') {
      if (!this.addedInterests.find(i => i.id === value.id)) {
        this.addedInterests.push(value);
        this.volunteerForm.patchValue({ intereses: this.addedInterests.map(i => i.id) });
      }
    } else if (listName === 'disponibilidad') {
      const val = `${this.selectedDay} ${this.selectedSlot}`;
      if (!this.addedAvailability.includes(val)) {
        this.addedAvailability.push(val);
        this.volunteerForm.patchValue({ disponibilidad: [...this.addedAvailability] });
      }
      this.selectedDay = '';
      this.selectedSlot = '';
    } else if (listName === 'idiomas') {
      if (!this.addedIdiomas.includes(value)) {
        this.addedIdiomas.push(value);
        this.volunteerForm.patchValue({ idiomas: [...this.addedIdiomas] });
      }
      this.selectedIdioma = '';
    }
  }

  removeItem(listName: 'habilidades' | 'intereses' | 'disponibilidad' | 'idiomas', value: any) {
    if (listName === 'habilidades') {
      this.addedSkills = this.addedSkills.filter(s => s.id !== value.id);
      this.volunteerForm.patchValue({ habilidades: this.addedSkills.map(s => s.id) });
    } else if (listName === 'intereses') {
      this.addedInterests = this.addedInterests.filter(i => i.id !== value.id);
      this.volunteerForm.patchValue({ intereses: this.addedInterests.map(i => i.id) });
    } else if (listName === 'disponibilidad') {
      this.addedAvailability = this.addedAvailability.filter(v => v !== value);
      this.volunteerForm.patchValue({ disponibilidad: [...this.addedAvailability] });
    } else if (listName === 'idiomas') {
      this.addedIdiomas = this.addedIdiomas.filter(v => v !== value);
      this.volunteerForm.patchValue({ idiomas: [...this.addedIdiomas] });
    }
  }

  isAdded(listName: 'habilidades' | 'intereses', item: Category): boolean {
    if (listName === 'habilidades') return !!this.addedSkills.find(s => s.id === item.id);
    if (listName === 'intereses') return !!this.addedInterests.find(i => i.id === item.id);
    return false;
  }

  onRegister() {
    this.errorMessage = '';

    if (this.volunteerForm.valid) {
      // Create valid payload manually to guarantee arrays are included
      const formValue = this.volunteerForm.value;
      const finalPayload = {
        ...formValue,
        idiomas: [...this.addedIdiomas], // Force local array
        disponibilidad: [...this.addedAvailability], // Force local array
        habilidades: this.addedSkills.map(s => s.id),
        intereses: this.addedInterests.map(i => i.id)
      };

      if (this.isEdit && this.initialData && !this.disableAutoUpdate) {
        // If it's an edit AND auto-update is ENABLED
        // The DNI is usually the identifier
        const identifier = this.initialData.dni || this.initialData.email;
        this.volunteerService.updateProfile(identifier, finalPayload).subscribe({
          next: (response) => {
            this.onSubmit.emit(response);
          },
          error: (err: any) => {
            this.errorMessage = 'Error al actualizar el perfil. Por favor, intente de nuevo.';
            console.error('Error updating profile:', err);
          }
        });
      } else {
        // Registration OR Manual Update (parent handles it)
        this.onSubmit.emit(finalPayload);
      }
    } else {
      let errorMsg = 'Por favor, revise los campos obligatorios:\n';
      Object.keys(this.volunteerForm.controls).forEach(key => {
        if (this.volunteerForm.get(key)?.errors) {
          errorMsg += '- ' + key + '\n';
        }
      });
      this.errorMessage = errorMsg;
    }
  }
  get f() { return this.volunteerForm.controls; }
}
