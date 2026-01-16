import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule, Location } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators, FormsModule } from '@angular/forms';
import { OrganizationService } from '../../../services/organization.service';
import { Organization } from '../../../models/organizationModel';
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
    const email = localStorage.getItem('user_id');
    if (email) {
      this.organizationService.getOrganizationByEmail(email).subscribe({
        next: (data) => {
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
          this.loading = false;
        },
        error: (err) => {
          this.message = 'Error al cargar el perfil.';
          this.isError = true;
          this.loading = false;
          // Auto-dismiss error
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
