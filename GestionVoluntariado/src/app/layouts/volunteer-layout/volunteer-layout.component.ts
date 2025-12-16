import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../components/Global-Components/navbar/navbar.component';
import { VolunteerSidebarComponent } from '../../components/Volunteer/Sidebar/volunteer-sidebar.component';

@Component({
    selector: 'app-volunteer-layout',
    standalone: true,
    imports: [CommonModule, RouterOutlet, NavbarComponent, VolunteerSidebarComponent],
    templateUrl: './volunteer-layout.component.html',
    styleUrl: './volunteer-layout.component.css'
})
export class VolunteerLayoutComponent { }
