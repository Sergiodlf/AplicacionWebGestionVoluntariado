import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpClientModule } from '@angular/common/http';

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

  constructor(private router: Router, private http: HttpClient) {}

  login() {
    if (!this.email || !this.password) {
      this.errorMessage = 'Por favor, introduce email y contraseña';
      return;
    }

    const body = { email: this.email, password: this.password };

    this.http.post<any>('/api/auth/login', body).subscribe({
      next: (response) => {
        console.log('Login exitoso:', response);

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
        console.error('Login error:', err);
        if (err.status === 401) {
          this.errorMessage = 'Credenciales incorrectas.';
        } else if (err.status === 404) {
          this.errorMessage = 'Usuario no encontrado.';
        } else {
          this.errorMessage = 'Error en el servidor. Inténtalo más tarde.';
        }
      },
    });
  }
}
