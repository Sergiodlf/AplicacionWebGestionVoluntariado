import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet } from '@angular/router';
import { SidebarComponent } from '../../components/Administrator/Sidebar/sidebar.component';
import { NavbarComponent } from '../../components/Global-Components/navbar/navbar.component';

@Component({
    selector: 'app-admin-layout',
    standalone: true,
    imports: [CommonModule, RouterOutlet, SidebarComponent, NavbarComponent],
    templateUrl: './admin-layout.component.html',
    styleUrl: './admin-layout.component.css'
})
export class AdminLayoutComponent { }
