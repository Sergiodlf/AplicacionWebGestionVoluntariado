import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule, Location } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators, FormsModule } from '@angular/forms';
import { VolunteerService, Volunteer } from '../../../services/volunteer.service';
import { VolunteerFormComponent } from '../../../components/Global-Components/volunteer-form/volunteer-form.component';
import { Router } from '@angular/router';

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
  volunteer: Volunteer | null = null;
  @ViewChild(VolunteerFormComponent) formComponent!: VolunteerFormComponent;

  constructor(
    private fb: FormBuilder,
    private volunteerService: VolunteerService,
    private router: Router,
    private location: Location
  ) {
    this.profileForm = this.fb.group({
      nombre: ['', Validators.required],
      apellido1: ['', Validators.required],
      email: [{ value: '', disabled: true }],
      zona: [''],
      experience: [''],
      hasCar: [false],
      habilidades: [''],
      intereses: [''],
      disponibilidad: ['']
    });
  }

  ngOnInit(): void {
    this.loadProfile();
  }

  toggleEdit(): void {
    this.editMode = !this.editMode;
    if (!this.editMode && this.volunteer) {
      this.profileForm.patchValue({
        nombre: this.volunteer.nombre,
        apellido1: this.volunteer.apellido1,
        zona: this.volunteer.zona,
        experience: this.volunteer.experience,
        hasCar: this.volunteer.hasCar
      });
    }
  }

  submitProfile(): void {
    if (this.formComponent) {
      this.formComponent.onRegister();
    }
  }

  handleFormSubmit(updatedData: any): void {
    this.volunteer = updatedData;
    this.editMode = false;
    this.message = 'Perfil actualizado con éxito';
    this.isError = false;
    setTimeout(() => this.message = '', 3000);

    if (updatedData.nombre) {
      localStorage.setItem('user_name', updatedData.nombre);
    }
  }

  goBack(): void {
    this.location.back();
  }

  loadProfile(): void {
    const email = localStorage.getItem('user_id');
    if (email) {
      this.volunteerService.getVolunteerByEmail(email).subscribe({
        next: (data) => {
          this.volunteer = data;
          this.profileForm.patchValue({
            nombre: data.nombre,
            apellido1: data.apellido1,
            email: data.email,
            zona: data.zona,
            experience: data.experience,
            hasCar: data.hasCar
          });
          this.loading = false;
        },
        error: (err) => {
          this.message = 'Error al cargar el perfil.';
          this.isError = true;
          this.loading = false;
          setTimeout(() => {
            this.message = '';
            this.isError = false;
          }, 3500);
        }
      });
    } else {
      this.message = 'Error al cargar el perfil. No se encontró sesión.';
      this.isError = true;
      this.loading = false;
      setTimeout(() => {
        this.message = '';
        this.isError = false;
      }, 3500);
    }
  }
}
