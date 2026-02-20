import { Component, OnInit, inject } from '@angular/core';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatCard } from '../../../components/Administrator/Dashboard/stat-card/stat-card';
import { CommonModule } from '@angular/common';
import { DashboardService } from '../../../services/dashboard.service';
import { DashboardMetrics } from '../../../models/DashboardMetrics';



@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, SidebarComponent, StatCard],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css',
})
export class DashboardComponent implements OnInit {
  private dashboardService = inject(DashboardService);

  metrics: DashboardMetrics | null = null;
  showLoading = false;

  ngOnInit() {
    this.showLoading = true;
    this.dashboardService.loadDashboardData().subscribe({
      next: () => (this.showLoading = false),
      error: () => (this.showLoading = false)
    });
    this.dashboardService.metrics$.subscribe((m) => (this.metrics = m));
  }

  // Getters for template compatibility
  get volunteersTotal() {
    return this.metrics?.volunteersTotal || 0;
  }
  get volunteersPending() {
    return this.metrics?.volunteersPending || 0;
  }
  get organizationsTotal() {
    return this.metrics?.organizationsTotal || 0;
  }
  get organizationsPending() {
    return this.metrics?.organizationsPending || 0;
  }
  get matchesTotal() {
    return this.metrics?.matchesTotal || 0;
  }
  get matchesAccepted() {
    return this.metrics?.matchesAccepted || 0;
  }
  get matchesPending() {
    return this.metrics?.matchesPending || 0;
  }
  get matchesCompleted() {
    return this.metrics?.matchesCompleted || 0;
  }
  get activitiesAccepted() {
    return this.metrics?.activitiesAccepted || 0;
  }
  get activitiesEnCurso() {
    return this.metrics?.activitiesEnCurso || 0;
  }
  get activitiesPending() {
    return this.metrics?.activitiesPending || 0;
  }
  get activitiesCompleted() {
    return this.metrics?.activitiesCompleted || 0;
  }
  get acceptanceRate() {
    return this.metrics?.acceptanceRate || 0;
  }
  get completionRate() {
    return this.metrics?.completionRate || 0;
  }
}
