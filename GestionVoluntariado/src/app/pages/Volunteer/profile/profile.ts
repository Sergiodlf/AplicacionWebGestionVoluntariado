import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule, Location } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators, FormsModule } from '@angular/forms';
import { VolunteerService } from '../../../services/volunteer.service';
import { VolunteerFormComponent } from '../../../components/Global-Components/volunteer-form/volunteer-form.component';
import { Router } from '@angular/router';
import { AuthService } from '../../../services/auth.service';
import { Volunteer } from '../../../models/Volunteer';

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
    private location: Location,
    private authService: AuthService
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
    this.loading = true;
    this.authService.updateProfile(updatedData).subscribe({
      next: () => {
        this.message = 'Perfil actualizado con éxito';
        this.isError = false;
        this.editMode = false;

        // Refresh local data from the updated subject/backend
        this.loadProfile();

        // Small delay to let loading spinner show briefly if loadProfile is fast, 
        // or just to ensure UI transitions smoothly. 
        // Actually loadProfile sets loading=false when done.

        setTimeout(() => this.message = '', 3000);
        // Local storage update if name changed
        if (updatedData.nombre) {
          localStorage.setItem('user_name', updatedData.nombre);
        }
      },
      error: (err) => {
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

  loadProfile(): void {
    // Try to get from state first
    const currentProfile = this.authService.getCurrentProfile();

    if (currentProfile && currentProfile.tipo === 'voluntario') {
      this.mapProfileToForm(currentProfile.datos);
      this.loading = false;
    } else {
      // Fallback: load from API via AuthService
      this.authService.loadProfile().subscribe({
        next: (profile) => {
          if (profile.tipo === 'voluntario') {
            this.mapProfileToForm(profile.datos);
          } else {
            this.message = 'El perfil no corresponde a un voluntario.';
            this.isError = true;
          }
          this.loading = false;
        },
        error: (err) => {
          console.error(err);
          this.message = 'Error al cargar el perfil.';
          this.isError = true;
          this.loading = false;
        }
      });
    }
  }

  private mapProfileToForm(data: any) {
    this.volunteer = data; // Assuming data structure matches roughly or we need to map fields
    // ProfileResponse.datos has fields like nombre, apellido1, correo...
    // Volunteer interface has email. ProfileResponse has correo.

    this.profileForm.patchValue({
      nombre: data.nombre,
      apellido1: data.apellido1,
      email: data.correo || data.email,
      zona: data.zona,
      experience: data.experiencia || data.experience,
      hasCar: data.coche || data.hasCar
    });
  }
}
