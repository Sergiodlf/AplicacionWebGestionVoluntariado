import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../components/Global-Components/navbar/navbar.component';
import { OrganizerSidebarComponent } from '../../components/Organizer/Sidebar/organizer-sidebar.component';

@Component({
    selector: 'app-organizer-layout',
    standalone: true,
    imports: [CommonModule, RouterOutlet, NavbarComponent, OrganizerSidebarComponent],
    templateUrl: './organizer-layout.component.html',
    styleUrl: './organizer-layout.component.css'
})
export class OrganizerLayoutComponent { }
