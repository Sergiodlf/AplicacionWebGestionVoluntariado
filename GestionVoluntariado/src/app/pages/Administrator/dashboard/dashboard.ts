import { Component, OnInit, inject } from '@angular/core';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatCard } from '../../../components/Administrator/Dashboard/stat-card/stat-card';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { CommonModule } from '@angular/common';
import { DashboardService, DashboardMetrics } from '../../../services/dashboard.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, SidebarComponent, StatCard, Navbar],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css',
})
export class DashboardComponent implements OnInit {
  private dashboardService = inject(DashboardService);

  isLoading = false;
  metrics: DashboardMetrics | null = null;

  ngOnInit() {
    this.dashboardService.metrics$.subscribe(m => this.metrics = m);
    this.loadDashboardData();
  }

  loadDashboardData() {
    // Only show loading screen if it's the very first load
    this.isLoading = this.dashboardService.isFirstLoad;

    this.dashboardService.loadDashboardData().subscribe({
      next: () => {
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading dashboard data', err);
        this.isLoading = false;
      }
    });
  }

  // Getters for template compatibility
  get volunteersTotal() { return this.metrics?.volunteersTotal || 0; }
  get volunteersPending() { return this.metrics?.volunteersPending || 0; }
  get organizationsTotal() { return this.metrics?.organizationsTotal || 0; }
  get organizationsPending() { return this.metrics?.organizationsPending || 0; }
  get matchesTotal() { return this.metrics?.matchesTotal || 0; }
  get matchesAccepted() { return this.metrics?.matchesAccepted || 0; }
  get matchesPending() { return this.metrics?.matchesPending || 0; }
  get matchesCompleted() { return this.metrics?.matchesCompleted || 0; }
  get activitiesActive() { return this.metrics?.activitiesActive || 0; }
  get activitiesPending() { return this.metrics?.activitiesPending || 0; }
  get activitiesCompleted() { return this.metrics?.activitiesCompleted || 0; }
  get acceptanceRate() { return this.metrics?.acceptanceRate || 0; }
  get completionRate() { return this.metrics?.completionRate || 0; }
}
