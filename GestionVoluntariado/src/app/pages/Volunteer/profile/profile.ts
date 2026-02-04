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

    // Transform data to match backend expectations
    const transformedData: any = {};

    // Split nombreCompleto into nombre and apellido1
    if (updatedData.nombreCompleto) {
      const parts = updatedData.nombreCompleto.trim().split(' ');
      transformedData.nombre = parts[0] || '';
      transformedData.apellido1 = parts.slice(1).join(' ') || '';
    }

    // Map other fields directly
    if (updatedData.zona) transformedData.zona = updatedData.zona;
    if (updatedData.experiencia !== undefined) transformedData.experiencia = updatedData.experiencia;
    if (updatedData.coche !== undefined) {
      // Convert "Si"/"No" to boolean if needed
      transformedData.coche = updatedData.coche === 'Si' || updatedData.coche === true;
    }
    
    // Arrays
    if (updatedData.idiomas) transformedData.idiomas = updatedData.idiomas;
    if (updatedData.disponibilidad) transformedData.disponibilidad = updatedData.disponibilidad;
    if (updatedData.habilidades) transformedData.habilidades = updatedData.habilidades;
    if (updatedData.intereses) transformedData.intereses = updatedData.intereses;
    if (updatedData.ciclo) transformedData.ciclo = updatedData.ciclo;

    this.authService.updateProfile(transformedData).subscribe({
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
        if (transformedData.nombre) {
          localStorage.setItem('user_name', transformedData.nombre);
        }
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
        error: (err: any) => {
          console.error(err);
          this.message = 'Error al cargar el perfil.';
          this.isError = true;
          this.loading = false;
        }
      });
    }
  }

  private mapProfileToForm(data: any) {
    // Create a volunteer object that matches what the form component expects
    // Using 'any' to allow flexible field assignment from backend
    this.volunteer = {
      nombre: data.nombre,
      apellido1: data.apellido1,
      apellido2: data.apellido2,
      email: data.correo || data.email,
      correo: data.correo || data.email,
      dni: data.dni,
      zona: data.zona,
      fechaNacimiento: data.fechaNacimiento,
      experiencia: data.experiencia,
      experience: data.experiencia, // Alias
      coche: data.coche,
      hasCar: data.coche, // Alias
      habilidades: data.habilidades || [],
      intereses: data.intereses || [],
      idiomas: data.idiomas || [],
      disponibilidad: data.disponibilidad || [],
      ciclo: data.ciclo
    } as any;
  }
}
