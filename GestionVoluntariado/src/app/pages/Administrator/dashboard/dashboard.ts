import { Component } from '@angular/core';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatCard } from '../../../components/Administrator/Dashboard/stat-card/stat-card';
import { NavbarComponent } from '../../../components/Global-Components/navbar/navbar.component';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [SidebarComponent, StatCard, NavbarComponent],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css',
})
export class Dashboard {

}
