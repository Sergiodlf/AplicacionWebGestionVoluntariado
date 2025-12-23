import { Component, OnInit, inject } from '@angular/core';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatCard } from '../../../components/Administrator/Dashboard/stat-card/stat-card';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { VolunteerService } from '../../../services/volunteer.service';
import { OrganizationService } from '../../../services/organization.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, SidebarComponent, StatCard, Navbar],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css',
})
export class DashboardComponent implements OnInit {
  private volunteerService = inject(VolunteerService);
  private organizationService = inject(OrganizationService);

  // Metrics
  volunteersTotal = 0;
  volunteersPending = 0;

  organizationsTotal = 0;
  organizationsPending = 0;

  matchesTotal = 0;
  matchesPending = 0;
  matchesAccepted = 0;
  matchesCompleted = 0;

  // Rates
  acceptanceRate = 0;
  completionRate = 0;

  ngOnInit() {
    console.log('DashboardComponent initialized');
    this.loadVolunteers();
    this.loadOrganizations();
    // Matches logic would ideally come from a dedicated service,
    // for now we'll simulate or derive if possible, or leave layout ready.
  }

  loadVolunteers() {
    console.log('Loading volunteers...');
    this.volunteerService.getVolunteers().subscribe({
      next: (data) => {
        console.log('Volunteers loaded:', data);
        this.volunteersTotal = data.length;
        this.volunteersPending = data.filter((v) => v.status === 'PENDIENTE').length;
        console.log('Volunteers metrics updated:', {
          total: this.volunteersTotal,
          pending: this.volunteersPending,
        });
      },
      error: (err) => console.error('Error loading volunteers metrics:', err),
    });
  }

  loadOrganizations() {
    console.log('Loading organizations...');
    this.organizationService.getOrganizations().subscribe({
      next: (data) => {
        console.log('Organizations loaded:', data);
        this.organizationsTotal = data.length;
        // Check organization status mapping logic from OrganizationsComponent
        this.organizationsPending = data.filter((o) => {
          const status = o.estado ? o.estado.toUpperCase() : '';
          return status === 'PENDIENTE';
        }).length;
        console.log('Organizations metrics updated:', {
          total: this.organizationsTotal,
          pending: this.organizationsPending,
        });
      },
      error: (err) => console.error('Error loading organizations metrics:', err),
    });
  }
}
