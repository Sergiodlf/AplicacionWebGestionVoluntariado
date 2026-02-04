import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpClientModule } from '@angular/common/http';
import { AuthService } from '../../../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, HttpClientModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css',
})
export class LoginComponent {
  email = '';
  password = '';
  errorMessage = '';
  isLoading = false;

  constructor(private router: Router, private http: HttpClient, private authService: AuthService) { }

  login() {
    if (!this.email || !this.password) {
      this.errorMessage = 'Por favor, introduce email y contraseña';
      return;
    }

    this.isLoading = true;
    this.errorMessage = ''; // Clear previous errors

    this.authService.login(this.email, this.password)
      .then(() => {
        console.log('Firebase login successful');
        this.loadUserProfile();
      })
      .catch((error: any) => {
        console.error('Firebase login error:', error);
        this.isLoading = false;
        if (error.code === 'auth/invalid-email') {
          this.errorMessage = 'El formato del correo es inválido.';
        } else if (error.code === 'auth/user-not-found' || error.code === 'auth/wrong-password' || error.code === 'auth/invalid-credential') {
          this.errorMessage = 'Credenciales incorrectas.';
        } else {
          this.errorMessage = 'Error al iniciar sesión con Firebase: ' + error.message;
        }
      });
  }

  loadUserProfile() {
    this.authService.loadProfile().subscribe({
      next: (profile) => {
        console.log('Perfil cargado:', profile);
        const user = this.authService.getCurrentUser();

        // Redirect based on role from profile
        if (profile.tipo === 'voluntario') {
          if (user && !user.emailVerified) {
            alert('Debes verificar tu correo antes de entrar como voluntario.');
            this.authService.logout();
            this.isLoading = false;
            return;
          }
          this.router.navigate(['/volunteer/voluntariados']);
        } else if (profile.tipo === 'organizacion') {
          if (user && !user.emailVerified) {
            alert('Debes verificar tu correo antes de entrar como organización.');
            this.authService.logout();
            this.isLoading = false;
            return;
          }
          this.router.navigate(['/organization/mis-voluntariados-organizacion']);
        } else {
          // Fallback, maybe admin?
          this.router.navigate(['/admin/dashboard'], {
            state: { fromLogin: true },
          });
        }
        // Note: We don't set isLoading = false here because we are navigating away.
      },
      error: (err: any) => {
        console.error('Error loading profile:', err);
        this.isLoading = false;
        if (err.error && err.error.error === 'Voluntario no encontrado') {
          this.errorMessage = 'Usuario no registrado en la base de datos (Backend).';
        } else if (err.status === 404) {
          this.errorMessage = 'Perfil de usuario no encontrado.';
        } else {
          this.errorMessage = 'Error al cargar el perfil del usuario.';
        }
        this.authService.logout(); // Logout if profile fails to load
      }
    });
  }
}
