import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { AuthService } from './services/auth.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet],
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
