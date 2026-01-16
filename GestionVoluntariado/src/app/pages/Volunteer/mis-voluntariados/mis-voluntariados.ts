import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { forkJoin } from 'rxjs';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarVolunteer } from '../../../components/Volunteer/sidebar-volunteer/sidebar-volunteer';
import { VoluntariadoCard } from '../../../components/Volunteer/voluntariado-card/voluntariado-card';
import { VoluntariadoService } from '../../../services/voluntariado-service';
import { StatusToggleVoluntariado } from "../../../components/Volunteer/status-toggle-voluntariado/status-toggle-voluntariado";

@Component({
  selector: 'app-mis-voluntariados',
  imports: [
    CommonModule,
    FormsModule,
    Navbar,
    SidebarVolunteer,
    VoluntariadoCard,
    StatusToggleVoluntariado
  ],
  templateUrl: './mis-voluntariados.html',
  styleUrl: './mis-voluntariados.css',
})
export class MisVoluntariados implements OnInit {
  activeTab: 'left' | 'second' | 'middle' | 'right' = 'left';

  tabLabel = 'Pendientes';
  isLoading = true;
  searchTerm: string = '';

  // Dynamic counts
  countPending = 0;
  countAccepted = 0;

  showFilterModal = signal(false);
  tempFilters = {
    localidad: '',
    sector: ''
  };

  filterCriteria = {
    localidad: '',
    sector: ''
  };

  availableLocations: string[] = [];
  availableSectors: string[] = [];

  private voluntariadoService = inject(VoluntariadoService);
  // Fetch from session or fallback (00000000A is Carlos from fixtures)
  currentDNI = localStorage.getItem('user_id') || '00000000A';

  volunteeringData: any[] = [];
  allVolunteeringData: any[] = [];

  ngOnInit() {
    this.loadAllData();
  }

  loadAllData() {
    this.isLoading = true;
    forkJoin({
      pending: this.voluntariadoService.getInscripcionesVoluntario(this.currentDNI, 'PENDIENTE'),
      accepted: this.voluntariadoService.getInscripcionesVoluntario(this.currentDNI, 'CONFIRMADO'),
      ongoing: this.voluntariadoService.getInscripcionesVoluntario(this.currentDNI, 'EN_CURSO'),
      completed: this.voluntariadoService.getInscripcionesVoluntario(this.currentDNI, 'COMPLETADA')
    }).subscribe({
      next: (result) => {
        this.countPending = result.pending.length;
        this.countAccepted = result.accepted.length;

        const mapItem = (v: any) => {
          let statusLabel = v.estado_inscripcion || v.estado;
          // Map backend status to user-friendly status
          if (['CONFIRMADO', 'ACEPTADA', 'CONFIRMADA'].includes(statusLabel)) {
            statusLabel = 'Sin comenzar';
          } else if (['EN_CURSO', 'EN CURSO'].includes(statusLabel)) {
            statusLabel = 'En curso';
          } else if (['COMPLETADA', 'FINALIZADO'].includes(statusLabel)) {
            statusLabel = 'Completado';
          } else if (['PENDIENTE', 'ABIERTA', 'Solicitado'].includes(statusLabel)) {
            statusLabel = 'Pendiente';
          }

          return {
            ...v,
            title: v.nombre,
            organization: v.organizacion,
            skills: v.habilidades || [],
            date: v.fechaInicio,
            status: statusLabel,
            ods: v.ods || []
          };
        };

        this.allVolunteeringData = [
          ...result.pending.map(v => ({ ...mapItem(v), category: 'left' })),
          ...result.accepted.map(v => ({ ...mapItem(v), category: 'second' })),
          ...result.ongoing.map(v => ({ ...mapItem(v), category: 'second' })), // Merged into Accepted
          ...result.completed.map(v => ({ ...mapItem(v), category: 'second' })) // Merged into Accepted
        ];

        // Recalculate counts based on merged categories
        this.countPending = this.allVolunteeringData.filter(v => v.category === 'left').length;
        this.countAccepted = this.allVolunteeringData.filter(v => v.category === 'second').length;

        this.extractFilterOptions();
        this.applyFilters();
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error fetching data for volunteer', err);
        this.isLoading = false;
      }
    });
  }

  extractFilterOptions() {
    const locs = new Set<string>();
    const secs = new Set<string>();
    this.allVolunteeringData.forEach(v => {
      if (v.direccion) locs.add(v.direccion);
      if (v.sector) secs.add(v.sector);
    });
    this.availableLocations = Array.from(locs).sort();
    this.availableSectors = Array.from(secs).sort();
  }

  applyFilters() {
    this.volunteeringData = this.allVolunteeringData.filter(v => {
      // 1. Tab Filter
      if (v.category !== this.activeTab) return false;

      // 2. Search Term
      if (this.searchTerm && !v.title.toLowerCase().includes(this.searchTerm.toLowerCase())) return false;

      // 3. Attribute Filters
      if (this.filterCriteria.localidad && v.direccion !== this.filterCriteria.localidad) return false;
      if (this.filterCriteria.sector && v.sector !== this.filterCriteria.sector) return false;

      return true;
    });
  }

  openFilterModal() {
    this.tempFilters = { ...this.filterCriteria };
    this.showFilterModal.set(true);
  }

  closeFilterModal() {
    this.showFilterModal.set(false);
  }

  applyFiltersFromModal() {
    this.filterCriteria = { ...this.tempFilters };
    this.applyFilters();
    this.closeFilterModal();
  }

  resetFilters() {
    this.tempFilters = { localidad: '', sector: '' };
    this.applyFiltersFromModal();
  }

  get activeFilterCount(): number {
    let count = 0;
    if (this.filterCriteria.localidad) count++;
    if (this.filterCriteria.sector) count++;
    return count;
  }



  onTabChange(tab: 'left' | 'second' | 'middle' | 'right') {
    this.activeTab = tab;

    if (tab === 'left') this.tabLabel = 'Pendientes';
    if (tab === 'second') this.tabLabel = 'Aceptadas';
    // Removed middle/right labels logic as they are now merged

    this.applyFilters();
  }

  onAction(item: any) {
    console.log('Action in Mis Voluntariados:', item.title);
  }
}
