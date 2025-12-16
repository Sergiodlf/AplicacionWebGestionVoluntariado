import { Component } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';

@Component({
    selector: 'app-volunteer-sidebar',
    standalone: true,
    imports: [RouterLink, RouterLinkActive],
    templateUrl: './volunteer-sidebar.component.html',
    styleUrl: './volunteer-sidebar.component.css'
})
export class VolunteerSidebarComponent { }
