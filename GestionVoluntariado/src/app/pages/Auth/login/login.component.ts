import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';
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

    constructor(private router: Router) { }

    login() {
        console.log('Login attempt', this.email, this.password);
        if (this.email === 'test@example.com' && this.password === 'password') {
            this.errorMessage = '';
            this.router.navigate(['/admin/dashboard']);
        } else {
            this.errorMessage = 'Credenciales incorrectas. Prueba con: test@example.com / password';
        }
    }
}
