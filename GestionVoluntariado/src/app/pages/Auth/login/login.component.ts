import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {
  email = '';
  password = '';
  errorMessage = '';

  constructor(private router: Router) {}

  login() {
    if (this.email === 'test@example.com' && this.password === 'password') {
      this.router.navigate(['/admin/dashboard']);
    } else if (this.email === 'voluntario@example.com' && this.password === 'password'){
      this.router.navigate(['/volunteer/voluntariados']);
    } else if(this.email === 'organizacion@example.com' && this.password === 'password'){
      this.router.navigate(['organization/mis-voluntariados-organizacion']);
    } else {
      this.errorMessage = 'Credenciales incorrectas. Prueba con test@example.com / password';
    }
  }
}
