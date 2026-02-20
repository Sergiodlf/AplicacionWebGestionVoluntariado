import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule, Location } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators, FormsModule } from '@angular/forms';
import { VolunteerService } from '../../../services/volunteer.service';
import { CategoryService } from '../../../services/category.service';
import { VolunteerFormComponent } from '../../../components/Global-Components/volunteer-form/volunteer-form.component';
import { Router } from '@angular/router';
import { AuthService } from '../../../services/auth.service';
import { Volunteer } from '../../../models/Volunteer';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule, VolunteerFormComponent],
  templateUrl: './profile.html',
  styleUrl: './profile.css',
})
export class ProfileComponent implements OnInit {
  profileForm: FormGroup;
  loading = true;
  editMode = false;
  message = '';
  isError = false;
  volunteer: any | null = null;

  // Options for dropdowns
  availableZones: string[] = [
    'Casco Viejo', 'Ensanche', 'San Juan', 'Iturrama', 'Rochapea',
    'Txantrea', 'Azpiligaña', 'Milagrosa', 'Buztintxuri', 'Mendillorri',
    'Sarriguren', 'Barañáin', 'Burlada', 'Villava', 'Uharte',
    'Berriozar', 'Ansoáin', 'Noáin', 'Zizur Mayor', 'Mutilva'
  ];
  availableCiclos: any[] = [];
  availableSkills: any[] = [];
  availableInterests: any[] = [];
  availableAvailability: string[] = [
    'Lunes Mañana', 'Lunes Tarde', 'Lunes Noche',
    'Martes Mañana', 'Martes Tarde', 'Martes Noche',
    'Miércoles Mañana', 'Miércoles Tarde', 'Miércoles Noche',
    'Jueves Mañana', 'Jueves Tarde', 'Jueves Noche',
    'Viernes Mañana', 'Viernes Tarde', 'Viernes Noche',
    'Sábado Mañana', 'Sábado Tarde', 'Sábado Noche',
    'Domingo Mañana', 'Domingo Tarde', 'Domingo Noche'
  ];

  // Temporary state for tags being edited
  tempSkills: any[] = [];
  tempInterests: any[] = [];
  tempAvailability: string[] = [];

  private profileSubscription?: Subscription;

  constructor(
    private fb: FormBuilder,
    private volunteerService: VolunteerService,
    private categoryService: CategoryService,
    private router: Router,
    private location: Location,
    private authService: AuthService
  ) {
    this.profileForm = this.fb.group({
      nombre: ['', Validators.required],
      apellido1: ['', Validators.required],
      correo: [{ value: '', disabled: true }],
      zona: ['', Validators.required],
      experiencia: [''],
      coche: [false],
      ciclo: ['', Validators.required]
    });
  }

  ngOnInit(): void {
    this.initProfileSubscription();
    this.loadCategories();
  }

  ngOnDestroy(): void {
    if (this.profileSubscription) {
      this.profileSubscription.unsubscribe();
    }
  }

  private initProfileSubscription(): void {
    this.loading = true;
    this.profileSubscription = this.authService.userProfile$.subscribe({
      next: (profile) => {
        if (profile && profile.datos) {
          this.mapProfileToForm(profile.datos);
          this.loading = false;
        } else if (this.authService.hasToken() && !profile) {
          // If we have a token but no profile yet, trigger load
          this.authService.loadProfile().subscribe({
            error: () => this.loading = false
          });
        } else if (!this.authService.hasToken()) {
          this.router.navigate(['/login']);
        }
      },
      error: (err) => {
        console.error('Error in profile subscription', err);
        this.loading = false;
      }
    });
  }

  loadCategories(): void {
    this.categoryService.getHabilidades().subscribe(data => this.availableSkills = data);
    this.categoryService.getIntereses().subscribe(data => this.availableInterests = data);
    this.volunteerService.getCiclos().subscribe(data => this.availableCiclos = data);
  }

  toggleEdit(): void {
    if (!this.editMode) {
      // Entering edit mode: Initialize temp tags
      this.tempSkills = this.volunteer?.habilidades ? [...this.volunteer.habilidades] : [];
      this.tempInterests = this.volunteer?.intereses ? [...this.volunteer.intereses] : [];
      this.tempAvailability = this.volunteer?.disponibilidad ? [...this.volunteer.disponibilidad] : [];

      this.profileForm.patchValue({
        nombre: this.volunteer?.nombre,
        apellido1: this.volunteer?.apellido1,
        zona: this.volunteer?.zona,
        experiencia: this.volunteer?.experiencia || this.volunteer?.experience,
        coche: this.volunteer?.coche || this.volunteer?.hasCar,
        ciclo: this.volunteer?.ciclo
      });
    }
    this.editMode = !this.editMode;
  }

  // Tag management
  removeItem(list: any[], item: any): void {
    const index = list.indexOf(item);
    if (index > -1) list.splice(index, 1);
  }

  addSkill(event: any): void {
    const id = event.target.value;
    if (!id) return;
    const skill = this.availableSkills.find(s => s.id == id);
    if (skill && !this.tempSkills.find(s => s.id == skill.id)) {
      this.tempSkills.push(skill);
    }
    event.target.value = '';
  }

  addInterest(event: any): void {
    const id = event.target.value;
    if (!id) return;
    const interest = this.availableInterests.find(i => i.id == id);
    if (interest && !this.tempInterests.find(i => i.id == interest.id)) {
      this.tempInterests.push(interest);
    }
    event.target.value = '';
  }

  addAvailability(event: any): void {
    const value = event.target.value;
    if (!value) return;
    if (!this.tempAvailability.includes(value)) {
      this.tempAvailability.push(value);
    }
    event.target.value = '';
  }

  submitProfile(): void {
    if (this.profileForm.invalid) {
      this.message = 'Por favor, rellene los campos obligatorios.';
      this.isError = true;
      return;
    }

    this.loading = true;
    const formValue = this.profileForm.value;

    // Prepare payload
    const payload = {
      ...formValue,
      habilidades: this.tempSkills.map(s => s.id || s),
      intereses: this.tempInterests.map(i => i.id || i),
      disponibilidad: this.tempAvailability
    };

    this.authService.updateProfile(payload).subscribe({
      next: () => {
        this.message = 'Perfil actualizado con éxito';
        this.isError = false;
        this.editMode = false;
        // The reactive subscription in ngOnInit handles the UI update
        setTimeout(() => this.message = '', 3000);
      },
      error: (err: any) => {
        console.error('Error updating profile', err);
        this.message = 'Error al actualizar el perfil.';
        this.isError = true;
        this.loading = false;
        setTimeout(() => {
          this.message = '';
          this.isError = false;
        }, 3500);
      }
    });
  }

  goBack(): void {
    this.location.back();
  }

  private mapProfileToForm(data: any) {
    // Create a volunteer object
    this.volunteer = {
      nombre: data.nombre,
      apellido1: data.apellido1,
      apellido2: data.apellido2,
      email: data.correo || data.email,
      correo: data.correo || data.email,
      dni: data.dni,
      zona: data.zona,
      fechaNacimiento: data.fechaNacimiento,
      experiencia: data.experiencia || data.experience,
      coche: data.coche === true || data.hasCar === true,
      habilidades: data.habilidades || [],
      intereses: data.intereses || [],
      idiomas: data.idiomas || [],
      disponibilidad: data.disponibilidad || [],
      ciclo: data.ciclo?.nombre ? `${data.ciclo.nombre} (${data.ciclo.curso}º)` : (data.ciclo || '')
    };

    if (this.volunteer) {
      this.profileForm.patchValue({
        nombre: this.volunteer.nombre,
        apellido1: this.volunteer.apellido1,
        correo: this.volunteer.correo,
        zona: this.volunteer.zona,
        experiencia: this.volunteer.experiencia,
        coche: this.volunteer.coche,
        ciclo: this.volunteer.ciclo
      });
    }
  }
}
