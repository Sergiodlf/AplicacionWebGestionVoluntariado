import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { Navbar } from './components/Global-Components/navbar/navbar';
import { AuthService } from './services/auth.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule, RouterOutlet, Navbar],
  templateUrl: './app.html',
  styleUrl: './app.css',
})
export class App {
  protected readonly title = signal('GestionVoluntariado');

  constructor(private authService: AuthService) {
    this.authService.user$.subscribe(user => {
      if (user && !this.authService.getCurrentProfile()) {
        this.authService.loadProfile().subscribe({
          error: err => console.error('Error restoring profile on app load', err)
        });
      }
    });
  }
}
