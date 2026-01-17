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

  constructor(private router: Router, private http: HttpClient, private authService: AuthService) {}

  login() {
    if (!this.email || !this.password) {
      this.errorMessage = 'Por favor, introduce email y contraseña';
      return;
    }

    this.authService.login(this.email, this.password)
      .then(() => {
        console.log('Firebase login successful');
        const user = this.authService.getCurrentUser();
        if (user && !user.emailVerified) {
             // Android behaves: mAuth.signOut() and show "Verificación pendiente".
             // We will warn but maybe allow proceeding if the backend allows it, 
             // OR to match Android exactly, we should block.
             // Let's Match Android Logic:
             alert('Debes verificar tu correo antes de entrar.');
             this.authService.logout();
             return;
        }
        this.proceedToBackendLogin();
      })
      .catch((error) => {
        console.error('Firebase login error:', error);
        if (error.code === 'auth/invalid-email') {
          this.errorMessage = 'El formato del correo es inválido.';
        } else if (error.code === 'auth/user-not-found' || error.code === 'auth/wrong-password' || error.code === 'auth/invalid-credential') {
          this.errorMessage = 'Credenciales incorrectas.';
        } else {
          this.errorMessage = 'Error al iniciar sesión con Firebase: ' + error.message;
        }
      });
  }

  proceedToBackendLogin() {
    const body = { email: this.email, password: this.password };

    this.http.post<any>('/api/auth/login', body).subscribe({
      next: (response) => {
        console.log('Login backend exitoso:', response);

        // Save session data (Basic implementation)
        localStorage.setItem('user_token', 'mock_token'); // Backend doesn't send token yet, mimicking it
        localStorage.setItem('user_role', response.tipo);
        localStorage.setItem('user_id', response.id);
        localStorage.setItem('user_name', response.nombre);

        // Redirect based on role
        if (response.tipo === 'voluntario') {
          this.router.navigate(['/volunteer/voluntariados']);
        } else if (response.tipo === 'organizacion') {
          this.router.navigate(['/organization/mis-voluntariados-organizacion']);
        } else {
          // Fallback for admin or others
          this.router.navigate(['/admin/dashboard'], {
            state: { fromLogin: true },
          });
        }
      },
      error: (err) => {
        console.error('Login backend error:', err);
        // Even if backend fails, if Firebase succeeded, we might want to handle it. 
        // But for now, let's show the backend error or proceed if we want to rely solely on Firebase eventually.
        // Assuming we need backend data:
        if (err.status === 401) {
          this.errorMessage = 'Credenciales incorrectas en el servidor.';
        } else if (err.status === 404) {
          this.errorMessage = 'Usuario no encontrado en el servidor.';
        } else {
          this.errorMessage = 'Error en el servidor. Inténtalo más tarde.';
        }
      },
    });
  }
}
