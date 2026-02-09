import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule, Location } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators, FormsModule } from '@angular/forms';
import { OrganizationService } from '../../../services/organization.service';
import { AuthService } from '../../../services/auth.service';
import { Organization } from '../../../models/organizationModel';
import { ProfileResponse } from '../../../models/profile.model';
import { OrganizationFormComponent } from '../../../components/Global-Components/organization-form/organization-form.component';

@Component({
  selector: 'app-profile-organization',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule, OrganizationFormComponent],
  templateUrl: './profile.html',
  styleUrl: './profile.css',
})
export class ProfileOrganizationComponent implements OnInit {
  profileForm: FormGroup;
  loading = true;
  editMode = false;
  message = '';
  isError = false;
  organization: Organization | null = null;
  @ViewChild(OrganizationFormComponent) formComponent!: OrganizationFormComponent;

  constructor(
    private fb: FormBuilder,
    private organizationService: OrganizationService,
    private authService: AuthService,
    private location: Location
  ) {
    this.profileForm = this.fb.group({
      nombre: ['', Validators.required],
      email: [{ value: '', disabled: true }],
      sector: [''],
      direccion: [''],
      localidad: [''],
      descripcion: [''],
      contacto: ['']
    });
  }

  ngOnInit(): void {
    this.loadProfile();
  }

  toggleEdit(): void {
    this.editMode = !this.editMode;
    if (!this.editMode && this.organization) {
      this.profileForm.patchValue({
        nombre: this.organization.nombre,
        email: this.organization.email,
        sector: this.organization.sector,
        direccion: this.organization.direccion,
        localidad: this.organization.localidad,
        descripcion: this.organization.descripcion,
        contacto: this.organization.contacto || ''
      });
    }
  }

  submitProfile(): void {
    if (this.formComponent) {
      this.formComponent.submit();
    }
  }

  handleFormSubmit(updatedOrg: Organization): void {
    this.organization = updatedOrg;
    this.editMode = false;
    this.message = 'Perfil actualizado con éxito';
    this.isError = false;
    setTimeout(() => this.message = '', 3000);

    if (updatedOrg.nombre) {
      localStorage.setItem('user_name', updatedOrg.nombre);
    }
  }

  goBack(): void {
    this.location.back();
  }

  loadProfile(): void {
    // Try to get from state first, then load from API
    const currentProfile = this.authService.getCurrentProfile();

    if (currentProfile && currentProfile.tipo === 'organizacion') {
      this.mapProfileToForm(currentProfile);
      this.loading = false;
    } else {
      // Fetch fresh
      this.authService.loadProfile().subscribe({
        next: (profile: ProfileResponse) => {
          if (profile.tipo === 'organizacion') {
            this.mapProfileToForm(profile);
          } else {
            this.message = 'El perfil no corresponde a una organización.';
            this.isError = true;
          }
          this.loading = false;
        },
        error: (err: any) => {
          console.error('Error loading profile:', err);
          this.message = 'Error al cargar el perfil.';
          this.isError = true;
          this.loading = false;
        }
      });
    }
  }

  private mapProfileToForm(profile: any): void {
    const data = profile.datos;
    this.organization = data;
    this.profileForm.patchValue({
      nombre: data.nombre,
      email: data.email,
      sector: data.sector,
      direccion: data.direccion,
      localidad: data.localidad,
      descripcion: data.descripcion,
      contacto: data.contacto || ''
    });
  }
}
