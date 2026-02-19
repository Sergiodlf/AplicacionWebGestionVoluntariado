import { Component, EventEmitter, Output, Input, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { AuthService } from '../../../services/auth.service';

@Component({
  selector: 'app-change-password-modal',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './change-password-modal.component.html',
  styleUrl: './change-password-modal.component.css'
})
export class ChangePasswordModalComponent {
  @Input() email?: string;
  @Output() close = new EventEmitter<void>();

  fb = inject(FormBuilder);
  authService = inject(AuthService);

  form: FormGroup;
  loading = false;
  errorMessage = '';
  successMessage = '';

  constructor() {
    this.form = this.fb.group({
      oldPassword: ['', [Validators.required]],
      newPassword: ['', [Validators.required, Validators.minLength(6)]],
      confirmPassword: ['', [Validators.required]]
    }, { validators: this.passwordMatchValidator });
  }

  passwordMatchValidator(g: FormGroup) {
    return g.get('newPassword')?.value === g.get('confirmPassword')?.value
      ? null
      : { mismatch: true };
  }

  onSubmit() {
    if (this.form.invalid) return;

    this.loading = true;
    this.errorMessage = '';
    this.successMessage = '';

    const { oldPassword, newPassword } = this.form.value;

    this.authService.changePassword(oldPassword, newPassword, this.email).subscribe({
      next: () => {
        this.loading = false;
        this.successMessage = 'Contraseña actualizada con éxito.';
        this.form.reset();
        setTimeout(() => this.close.emit(), 2000);
      },
      error: (err: any) => {
        this.loading = false;
        this.errorMessage = err.error?.error || 'Error al cambiar la contraseña';
      }
    });
  }

  onClose() {
    this.close.emit();
  }
}
