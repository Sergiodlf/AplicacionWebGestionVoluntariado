import { Component, OnInit, inject } from '@angular/core';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatCard } from '../../../components/Administrator/Dashboard/stat-card/stat-card';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { VolunteerService } from '../../../services/volunteer.service';
import { OrganizationService } from '../../../services/organization.service';
import { VoluntariadoService } from '../../../services/voluntariado-service';
import { CommonModule } from '@angular/common';
import { forkJoin } from 'rxjs';

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
  private voluntariadoService = inject(VoluntariadoService);

  isLoading = true;

  // Metrics
  volunteersTotal = 0;
  volunteersPending = 0;

  organizationsTotal = 0;
  organizationsPending = 0;

  // Matches
  matchesTotal = 0;
  matchesAccepted = 0;
  matchesPending = 0;
  matchesCompleted = 0;

  // Activities
  activitiesActive = 0;
  activitiesPending = 0;
  activitiesCompleted = 0;

  // Rates
  acceptanceRate = 0;
  completionRate = 0;

  ngOnInit() {
    this.loadDashboardData();
  }

  loadDashboardData() {
    this.isLoading = true;

    // ForkJoin to wait for all observables
    forkJoin({
      volunteers: this.volunteerService.getVolunteers(true),
      organizations: this.organizationService.getOrganizations(true),
      matches: this.voluntariadoService.getAllInscripciones(true),
      activities: this.voluntariadoService.getAllVoluntariados(true)
    }).subscribe({
      next: (result) => {
        // 1. Volunteers
        // Fix: User requested total to reflect accepted volunteers, not all database records.
        this.volunteersTotal = result.volunteers.filter(v => v.status === 'ACEPTADO').length;
        this.volunteersPending = result.volunteers.filter(v => v.status === 'PENDIENTE').length;

        // 2. Organizations
        // Fix: Same for organizations, show Approved as main number.
        this.organizationsTotal = result.organizations.filter(o => (o.estado?.trim().toLowerCase() || '') === 'aprobado').length;
        this.organizationsPending = result.organizations.filter(o => (o.estado?.trim().toLowerCase() || '') === 'pendiente').length;

        // 3. Matches
        const matches = result.matches;
        this.matchesTotal = matches.length;
        this.matchesPending = matches.filter(m => m.estado === 'PENDIENTE').length;
        this.matchesAccepted = matches.filter(m => ['CONFIRMADA', 'ACEPTADA', 'ACEPTADO', 'CONFIRMADO'].includes(m.estado?.toUpperCase())).length;
        this.matchesCompleted = matches.filter(m => ['COMPLETADA', 'COMPLETADO'].includes(m.estado?.toUpperCase())).length;

        // 4. Activities
        const activities = result.activities;
        const normalize = (s: string) => s ? s.trim().toUpperCase() : '';

        // Active: Must be Approved (by admin) AND in an active state (Open/In Progress)
        this.activitiesActive = activities.filter((a: any) =>
          normalize(a.estadoAprobacion) === 'ACEPTADA' &&
          ['ABIERTA', 'EN CURSO', 'ACEPTADA'].includes(normalize(a.estado))
        ).length;

        // Pending: Pending approval logic
        this.activitiesPending = activities.filter((a: any) =>
          normalize(a.estadoAprobacion) === 'PENDIENTE'
        ).length;

        // Completed
        this.activitiesCompleted = activities.filter((a: any) =>
          ['CERRADA', 'FINALIZADA', 'CANCELADO'].includes(normalize(a.estado))
        ).length;

        // 5. Rates
        if (this.matchesTotal > 0) {
          this.acceptanceRate = Math.round((this.matchesAccepted / this.matchesTotal) * 100);
        } else {
          this.acceptanceRate = 0;
        }

        if (this.matchesAccepted > 0) {
          this.completionRate = Math.round((this.matchesCompleted / this.matchesAccepted) * 100);
        } else {
          this.completionRate = 0;
        }

        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading dashboard data', err);
        this.isLoading = false;
      }
    });
  }
}
