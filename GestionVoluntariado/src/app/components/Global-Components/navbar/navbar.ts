import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './navbar.html',
  styleUrl: './navbar.css',
})
export class Navbar implements OnInit {
  userName: string = 'Usuario';
  userRole: string | null = null;

  constructor(private router: Router) { }

  ngOnInit(): void {
    const storedName = localStorage.getItem('user_name');
    if (storedName) {
      this.userName = storedName;
    }
    this.userRole = localStorage.getItem('user_role');
  }

  get profileLink(): string {
    if (this.userRole === 'voluntario') return '/volunteer/profile';
    if (this.userRole === 'organizacion') return '/organization/profile';
    return '';
  }

  logout(): void {
    localStorage.clear();
    // Force navigation to login page
    this.router.navigate(['/login']).then(() => {
      window.location.reload();
    });
  }
}
