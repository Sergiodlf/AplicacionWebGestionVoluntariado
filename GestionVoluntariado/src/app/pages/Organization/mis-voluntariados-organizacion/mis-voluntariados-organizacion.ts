import { Component, OnInit, signal } from '@angular/core';
import { VoluntariadoService } from '../../../services/voluntariado-service';
import { AuthService } from '../../../services/auth.service';

import { CommonModule } from '@angular/common';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { StatusToggleVoluntariado } from '../../../components/Volunteer/status-toggle-voluntariado/status-toggle-voluntariado';
import { VoluntariadoCard } from '../../../components/Volunteer/voluntariado-card/voluntariado-card';
import { CrearVoluntariadoModal } from '../../../components/organization/crear-voluntariado-modal/crear-voluntariado-modal';

import { FormsModule } from '@angular/forms';
import { forkJoin } from 'rxjs';

@Component({
  selector: 'app-mis-voluntariados-organizacion',
  standalone: true,
  imports: [CommonModule, Navbar, StatusToggleVoluntariado, VoluntariadoCard, CrearVoluntariadoModal, FormsModule],
  templateUrl: './mis-voluntariados-organizacion.html',
  styleUrl: './mis-voluntariados-organizacion.css',
})
export class MisVoluntariadosOrganizacion implements OnInit {
  activeTab: 'left' | 'middle' = 'left';
  tabLabel = 'Pendientes';
  volunteeringData: any[] = [];
  allVolunteeringData: any[] = [];
  modalOpen = false;
  isLoading = true;

  searchTerm: string = '';
  filterCriteria = {
    localidad: '',
    sector: ''
  };

  availableLocations: string[] = [];
  availableSectors: string[] = [];

  showFilterModal = signal(false);
  tempFilters = {
    localidad: '',
    sector: ''
  };

  // Counts
  countPending = 0;
  countAccepted = 0;

  // CIF for template
  currentCif: string = '';

  constructor(
    private voluntariadoService: VoluntariadoService,
    private authService: AuthService
  ) { }

  ngOnInit(): void {
    this.loadAllData();
  }

  loadAllData() {
    this.isLoading = true;

    // Get CIF dynamically from authenticated user
    const user = this.authService.getCurrentUser();
    const profile = this.authService.getCurrentProfile();

    // Fallback logic if profile isn't fully loaded yet but we have user
    // Ideally we rely on profile.datos.cif
    if (profile && profile.tipo === 'organizacion') {
      this.currentCif = profile.datos.cif;
    } else {
      // Graceful fallback or error
      console.warn('Profile not loaded or not organization. Trying localStorage as backup.');
      this.currentCif = localStorage.getItem('user_id') || '';
    }

    if (!this.currentCif) {
      console.error('No CIF found for logged in user.');
      this.isLoading = false;
      return;
    }

    console.log('Fetching activities for CIF:', this.currentCif);

    const pending$ = this.voluntariadoService.getActivitiesByOrganization(this.currentCif, undefined, 'PENDIENTE');
    const accepted$ = this.voluntariadoService.getActivitiesByOrganization(this.currentCif, undefined, 'ACEPTADA');

    forkJoin([pending$, accepted$]).subscribe({
      next: ([pendingRes, acceptedRes]) => {
        console.log('RAW Returned Pending:', pendingRes);
        console.log('RAW Returned Accepted:', acceptedRes);

        // Helper to safely check status handling property variations
        const checkApproval = (item: any, expected: string) => {
          const val = (item.estadoAprobacion || item.estado_aprobacion || '').toUpperCase();
          console.log(`Checking item ${item.nombre} [${item.codActividad}]: Val=${val}, Expected=${expected}, Match=${val === expected}`);
          return val === expected;
        };

        // Validation: Filter again client-side in case the API returns mixed results
        // We strictly enforce that 'accepted' tab ONLY contains confirmed/accepted items
        const pendingResults = pendingRes.filter(i => checkApproval(i, 'PENDIENTE'));
        const acceptedResults = acceptedRes.filter(i => checkApproval(i, 'ACEPTADA') || checkApproval(i, 'ACEPTADO'));

        console.log('Filtered Pending:', pendingResults);
        console.log('Filtered Accepted:', acceptedResults);

        this.countPending = pendingResults.length;
        this.countAccepted = acceptedResults.length;

        const orgName = localStorage.getItem('user_name') || 'Mi Organización';

        const mapItem = (item: any, cat: string) => {
          let statusLabel = '';
          let buttonText = '';

          // Labels only for Aceptados (middle) where lifecycle matters
          if (cat === 'middle') {
            const now = new Date();
            const start = item.fechaInicio ? new Date(item.fechaInicio) : null;
            const end = item.fechaFin ? new Date(item.fechaFin) : null;

            if (start && now < start) {
              statusLabel = 'Sin comenzar';
            } else if (end && now > end) {
              statusLabel = 'Completado';
            } else {
              statusLabel = 'En curso';
            }

            // buttonText removed
          } else if (cat === 'left') {
            // Pendientes
            buttonText = 'Aceptar';
          }

          return {
            ...item,
            category: cat,
            title: item.nombre,
            organization: orgName,
            skills: item.habilidades || [],
            date: item.fechaInicio ? new Date(item.fechaInicio).toLocaleDateString() : 'Fecha pendiente',
            status: statusLabel, // Computed date-based status
            buttonText: buttonText,
            ods: item.ods || []
          };
        };

        this.allVolunteeringData = [
          ...pendingResults.map((i: any) => mapItem(i, 'left')),
          ...acceptedResults.map((i: any) => mapItem(i, 'middle'))
        ];

        this.extractFilterOptions();
        this.applyFilters();
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading organization activities:', err);
        this.isLoading = false;
        if (err.status === 404) {
          this.allVolunteeringData = [];
          this.applyFilters();
        }
      }
    });
  }

  extractFilterOptions() {
    const locs = new Set<string>();
    const secs = new Set<string>();
    this.allVolunteeringData.forEach(v => {
      if (v.direccion) locs.add(v.direccion); // Using direccion as location for now
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


  editingActivity: any = null;

  openCreateModal() {
    this.editingActivity = null;
    this.modalOpen = true;
  }

  openEditModal(item: any) {
    console.log('Opening edit modal for:', item);
    this.editingActivity = item;
    this.modalOpen = true;
  }

  onTabChange(tab: 'left' | 'second' | 'middle' | 'right') {
    // We only use 'left' (Pendientes) and 'middle' (Aceptados) now
    if (tab === 'left') {
      this.activeTab = 'left';
      this.tabLabel = 'Pendientes';
    } else if (tab === 'middle') {
      this.activeTab = 'middle';
      this.tabLabel = 'Aceptados';
    }

    this.applyFilters();
  }

  onAction(item: any) {
    console.log('Action in Mis Voluntariados:', item.title);
  }

  onVoluntariadoCreated(newVoluntariado: any) {
    this.loadAllData(); // Reload list after creation
    this.modalOpen = false;
  }
}
