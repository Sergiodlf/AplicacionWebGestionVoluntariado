import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-navbar',
  imports: [],
  templateUrl: './navbar.html',
  styleUrl: './navbar.css',
})
export class Navbar implements OnInit {
  userName: string = 'Admin';

  ngOnInit(): void {
    const storedName = localStorage.getItem('user_name');
    if (storedName) {
      this.userName = storedName;
    }
  }
}
