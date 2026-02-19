import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { MatchCardComponent } from '../../../components/Administrator/Matches/match-card/match-card.component';
import { CreateMatchModalComponent } from '../../../components/Administrator/Matches/create-match-modal/create-match-modal.component';
import { VoluntariadoService } from '../../../services/voluntariado-service';
import { NotificationService } from '../../../services/notification.service';

@Component({
  selector: 'app-matches',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    StatusToggleComponent,
    MatchCardComponent,
    CreateMatchModalComponent,
    SidebarComponent
  ],
  templateUrl: './matches.html',
  styleUrl: './matches.css'
})
export class MatchesComponent implements OnInit {
  private voluntariadoService = inject(VoluntariadoService);
  private notificationService = inject(NotificationService);

  activeTab: 'left' | 'middle' = 'left';
  matches: any[] = [];
  showCreateModal = false;
  searchTerm: string = ''; // Added search term
  isLoading = true;
  isFiltering = false;
  loadingMatchId: number | null = null;

  ngOnInit() {
    this.loadMatches();
  }

  // Helper to safely parse JSON, CSV or Objects
  private parseList(value: any): any[] {
    if (!value) return [];
    if (Array.isArray(value)) return value;
    if (typeof value === 'string') {
      value = value.trim();
      if (value.startsWith('[') && value.endsWith(']')) {
        try {
          const parsed = JSON.parse(value);
          return Array.isArray(parsed) ? parsed : [value];
        } catch { }
      }
      return value.split(',').map((s: string) => s.trim()).filter((s: string) => s.length > 0);
    }
    return [value];
  }

  loadMatches(forceReload: boolean = false) {
    this.voluntariadoService.getAllInscripciones(forceReload).subscribe({
      next: (data) => {
        console.log('📊 Raw API Response:', data);
        this.matches = data.map(item => ({
          id: item.id_inscripcion,
          volunteer: {
            nombre: item.nombre_voluntario || 'Voluntario',
            email: item.email_voluntario || 'email@example.com',
            skills: this.parseList(item.habilidades_voluntario),
            availability: this.parseList(item.disponibilidad_voluntario).join(', ') || 'No especificada',
            interests: this.parseList(item.intereses_voluntario)
          },
          organization: {
            nombre: item.nombre_actividad || 'Actividad',
            email: item.email_organizacion || 'org@example.com',
            description: item.descripcion_actividad || '',
            schedule: item.horario || 'No especificado',
            needs: this.parseList(item.habilidades_actividad),
            endDate: item.fecha_fin_actividad ? new Date(item.fecha_fin_actividad) : null
          },
          status: item.status || item.estado
        }));
        console.log('✅ Processed Matches:', this.matches);
        this.applyFilters();
        console.log('🎯 Filtered Matches:', this.filteredMatches);
        this.isLoading = false;
      },
      error: (err: any) => {
        console.error('Error loading matches', err);
        this.isLoading = false;
      }
    });
  }

  filteredMatches: any[] = [];

  applyFilters() {
    this.isFiltering = true;
    setTimeout(() => {
      let list = this.matches;
      // 0. Filter out expired activities (endDate < now)
      list = list.filter(m => this.isMatchActive(m));

      // First filter by status
      switch (this.activeTab) {
        case 'left':
          list = list.filter(m => (m.status || '').toUpperCase() === 'PENDIENTE');
          break;
        case 'middle':
          list = list.filter(m => ['CONFIRMADA', 'ACEPTADA', 'ACEPTADO', 'CONFIRMADO'].includes((m.status || '').toUpperCase()));
          break;
      }

      // Then filter by search term (Logical AND)
      if (this.searchTerm) {
        const term = this.searchTerm.toLowerCase();
        list = list.filter(m =>
          m.volunteer.nombre.toLowerCase().includes(term) ||
          m.organization.nombre.toLowerCase().includes(term) ||
          m.volunteer.email.toLowerCase().includes(term)
        );
      }

      this.filteredMatches = list;
      this.isFiltering = false;
    }, 100);
  }

  isMatchActive(m: any): boolean {
    if (!m.organization.endDate) return true;
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    const end = new Date(m.organization.endDate);
    end.setHours(0, 0, 0, 0);
    return end >= now;
  }

  get countPending() {
    return this.matches.filter(m => (m.status || '').toUpperCase() === 'PENDIENTE' && this.isMatchActive(m)).length;
  }

  get countConfirmed() {
    const valid = ['CONFIRMADA', 'ACEPTADA', 'ACEPTADO', 'CONFIRMADO'];
    return this.matches.filter(m => valid.includes((m.status || '').toUpperCase()) && this.isMatchActive(m)).length;
  }

  onTabChange(tab: 'left' | 'middle' | 'right') {
    if (tab === 'right') return;
    this.activeTab = tab as 'left' | 'middle';
    this.applyFilters();
  }

  onAccept(match: any) {
    this.loadingMatchId = match.id;
    this.voluntariadoService.updateInscripcionStatus(match.id, 'CONFIRMADO').subscribe({
      next: () => {
        this.notificationService.showSuccess('Match aceptado correctamente');
        this.loadingMatchId = null;
        this.loadMatches(true);
      },
      error: (err: any) => {
        console.error('Error accepting match', err);
        this.loadingMatchId = null;
        this.notificationService.showError('Error al aceptar el match');
      }
    });
  }

  onReject(match: any) {
    this.notificationService.showConfirmation('¿Estás seguro?', 'Vas a rechazar este match. esta acción no se puede deshacer.')
      .then((confirmed) => {
        if (confirmed) {
          this.loadingMatchId = match.id;
          this.voluntariadoService.updateInscripcionStatus(match.id, 'RECHAZADO').subscribe({
            next: () => {
              this.notificationService.showSuccess('Match rechazado correctamente');
              this.loadingMatchId = null;
              this.loadMatches(true);
            },
            error: (err: any) => {
              console.error('Error rejecting match', err);
              this.loadingMatchId = null;
              this.notificationService.showError('Error al rechazar el match');
            }
          });
        }
      });
  }

  onComplete(match: any) {
    this.loadingMatchId = match.id;
    this.voluntariadoService.updateInscripcionStatus(match.id, 'COMPLETADA').subscribe({
      next: () => {
        this.notificationService.showSuccess('Match completado correctamente');
        this.loadingMatchId = null;
        this.loadMatches(true);
      },
      error: (err: any) => {
        console.error('Error completing match', err);
        this.loadingMatchId = null;
        this.notificationService.showError('Error al completar el match');
      }
    });
  }

  onCreateMatch() {
    this.showCreateModal = true;
  }

  onMatchCreated() {
    this.loadMatches(true);
    this.showCreateModal = false;
  }
}
