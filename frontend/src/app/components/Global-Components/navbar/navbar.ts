import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';
import { AuthService } from '../../../services/auth.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './navbar.html',
  styleUrl: './navbar.css',
})
export class Navbar implements OnInit {
  // Use observable for reactive UI
  userProfile$: Observable<any>;

  // Derived observables
  userName$: Observable<string>;
  profileLink$: Observable<string>;

  constructor(
    private router: Router,
    private authService: AuthService
  ) {
    this.userProfile$ = this.authService.userProfile$;
    // Determine name from profile or fallback to localStorage
    this.userName$ = this.userProfile$.pipe(
      map(profile => {
        if (profile?.datos?.nombre) return profile.datos.nombre;
        return localStorage.getItem('user_name') || 'Usuario';
      })
    );

    // Determine link from profile type or fallback to localStorage
    this.profileLink$ = this.userProfile$.pipe(
      map(profile => {
        let role = profile?.tipo;
        if (!role) role = localStorage.getItem('user_role') as 'voluntario' | 'organizacion';

        if (role === 'voluntario') return '/volunteer/profile';
        if (role === 'organizacion') return '/organization/profile';
        return '';
      })
    );
  }

  ngOnInit(): void {
    // Ensure profile is loaded if missing (optional safety)
    if (!this.authService.getCurrentProfile()) {
      // We don't force load here to avoid double calls, assuming AppComponent or Login does it.
      // But checking localStorage for immediate display is handled in the map.
    }
  }

  logout(): void {
    this.authService.logout().then(() => {
      this.router.navigate(['/login']);
    });
  }
}
