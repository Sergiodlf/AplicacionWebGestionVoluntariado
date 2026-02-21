import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule, Location } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators, FormsModule } from '@angular/forms';
import { OrganizationService } from '../../../services/organization.service';
import { AuthService } from '../../../services/auth.service';
import { inject } from '@angular/core';
import { NotificationService } from '../../../services/notification.service';
import { Organization } from '../../../models/organizationModel';

@Component({
  selector: 'app-profile-organization',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule],
  templateUrl: './profile.html',
  styleUrl: './profile.css',
})
export class ProfileOrganizationComponent implements OnInit {
  private authService = inject(AuthService);
  private notificationService = inject(NotificationService);
  private organizationService = inject(OrganizationService);
  profileForm: FormGroup;
  loading = true;
  editMode = false;
  message = '';
  isError = false;
  organization: Organization | null = null;
  // Password modal state
  showPasswordModal: boolean = false;

  availableSectors: string[] = [
    'Educación', 'Salud', 'Social', 'Medio Ambiente',
    'Comunitario', 'Cultura', 'Deportes', 'Internacional',
    'Derechos Humanos', 'Protección Animal', 'Tecnología'
  ];

  availableZones: string[] = [
    'Casco Viejo', 'Ensanche', 'San Juan', 'Iturrama', 'Rochapea',
    'Txantrea', 'Azpiligaña', 'Milagrosa', 'Buztintxuri', 'Mendillorri',
    'Sarriguren', 'Barañáin', 'Burlada', 'Villava', 'Uharte',
    'Berriozar', 'Ansoáin', 'Noáin', 'Zizur Mayor', 'Mutilva', 'Pamplona (Otros)', 'Tudela', 'Estella', 'Olite', 'Tafalla'
  ];

  constructor(
    private fb: FormBuilder,
    private location: Location
  ) {
    this.profileForm = this.fb.group({
      nombre: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(40)]],
      email: [{ value: '', disabled: true }],
      cif: [{ value: '', disabled: true }],
      sector: ['', Validators.required],
      direccion: ['', [Validators.required, Validators.maxLength(40)]],
      localidad: ['', Validators.required],
      cp: ['', [Validators.required, Validators.pattern(/^[0-9]{5}$/)]],
      descripcion: ['', [Validators.required, Validators.maxLength(200)]],
      contacto: ['', [Validators.required, Validators.maxLength(40)]]
    });
  }

  private profileSubscription?: any;

  ngOnInit(): void {
    this.initProfileSubscription();
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
        if (profile) {
          if (profile.tipo === 'organizacion') {
            this.mapProfileToForm(profile);
            this.loading = false;
          } else {
            this.message = 'El perfil no corresponde a una organización.';
            this.isError = true;
            this.loading = false;
          }
        } else if (this.authService.hasToken() && !profile) {
          this.authService.loadProfile().subscribe({
            error: () => this.loading = false
          });
        }
      },
      error: (err) => {
        console.error('Error in profile subscription', err);
        this.loading = false;
      }
    });
  }

  toggleEdit(): void {
    this.editMode = !this.editMode;
    if (this.editMode && this.organization) {
      this.profileForm.patchValue({
        nombre: this.organization.nombre,
        email: this.organization.email,
        cif: this.organization.cif,
        sector: this.organization.sector,
        direccion: this.organization.direccion,
        localidad: this.organization.localidad,
        cp: this.organization.cp,
        descripcion: this.organization.descripcion,
        contacto: this.organization.contacto || ''
      });
    }
  }

  submitProfile(): void {
    if (this.profileForm.invalid) {
      this.message = 'Por favor, rellene los campos obligatorios.';
      this.isError = true;
      return;
    }

    this.loading = true;
    const formValue = this.profileForm.getRawValue();

    this.authService.updateProfile(formValue).subscribe({
      next: () => {
        this.message = 'Perfil actualizado con éxito';
        this.isError = false;
        this.editMode = false;
        this.loading = false;
        setTimeout(() => this.message = '', 3000);
      },
      error: (err: any) => {
        console.error('Error updating profile', err);
        this.message = err.error?.message || 'Error al actualizar el perfil.';
        this.isError = true;
        this.loading = false;
        setTimeout(() => {
          this.message = '';
          this.isError = false;
        }, 5000);
      }
    });
  }



  goBack(): void {
    this.location.back();
  }

  private mapProfileToForm(profile: any): void {
    const data = profile.datos;
    this.organization = data;
    this.profileForm.patchValue({
      nombre: data.nombre,
      email: data.email,
      cif: data.cif,
      sector: data.sector,
      direccion: data.direccion,
      localidad: data.localidad,
      cp: data.cp,
      descripcion: data.descripcion,
      contacto: data.contacto || ''
    });
  }

  // Modal de cambio de contraseña

  newPasswordValue: string = '';

  openPasswordModal() {
    // For profile page, change password applies to current organization
    this.showPasswordModal = true;
    this.newPasswordValue = '';
    this.setBodyScroll(true);
  }

  closePasswordModal() {
    this.showPasswordModal = false;
    this.newPasswordValue = '';
    this.setBodyScroll(false);
  }

  private setBodyScroll(lock: boolean) {
    if (lock) {
      document.body.classList.add('body-modal-open');
    } else {
      document.body.classList.remove('body-modal-open');
    }
  }

  submitPasswordChange() {
    if (!this.newPasswordValue || this.newPasswordValue.length < 6) {
      this.notificationService.showWarning('La contraseña debe tener al menos 6 caracteres');
      return;
    }

    this.authService.changePassword(this.newPasswordValue).subscribe({
      next: () => {
        this.closePasswordModal();
        this.notificationService.showSuccess('Contraseña actualizada correctamente.');
      },
      error: (err: any) => {
        console.error('Error updating password', err);
        this.notificationService.showError('Error al actualizar la contraseña.');
      }
    });
  }
}
