import { Component, signal, OnInit } from '@angular/core';
import { RouterOutlet, Router, NavigationEnd, Event } from '@angular/router';
import { Navbar } from './components/Global-Components/navbar/navbar';
import { ToastComponent } from './components/Global-Components/toast/toast.component';
import { AuthService } from './services/auth.service';
import { CommonModule } from '@angular/common';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule, RouterOutlet, Navbar, ToastComponent],
  templateUrl: './app.html',
  styleUrl: './app.css',
})
export class App implements OnInit {
  protected readonly title = signal('GestionVoluntariado');
  showNavbar = signal(true);

  constructor(private authService: AuthService, private router: Router) {
    this.authService.user$.subscribe(user => {
      if (user && !this.authService.getCurrentProfile() && !this.authService.isRegistrationInProgress) {
        this.authService.loadProfile().subscribe({
          error: err => console.error('Error restoring profile on app load', err)
        });
      }
    });
  }

  ngOnInit() {
    this.router.events.pipe(
      filter((event: Event): event is NavigationEnd => event instanceof NavigationEnd)
    ).subscribe((event: NavigationEnd) => {
      this.checkNavbarVisibility(event.urlAfterRedirects);
    });
  }

  private checkNavbarVisibility(url: string) {
    // Hide navbar on landing, login, register and their sub-paths
    const hiddenRoutes = ['/', '/login', '/register'];

    // Check if the current URL matches any of the hidden routes strictly or is a subpath of /register
    // We want to hide on /register, /register/volunteer, /register/organization
    // But show on / (landing) which is exactly '/'

    // Simplification:
    // If it is exactly '/' -> Hide
    // If it starts with '/login' -> Hide
    // If it starts with '/register' -> Hide

    const isHidden = url === '/' ||
      url.startsWith('/login') ||
      url.startsWith('/register');

    this.showNavbar.set(!isHidden);
  }
}
