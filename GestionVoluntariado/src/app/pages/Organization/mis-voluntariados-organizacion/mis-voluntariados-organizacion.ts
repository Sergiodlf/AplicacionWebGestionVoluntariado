import { Component, OnInit, signal } from '@angular/core';
import { VoluntariadoService } from '../../../services/voluntariado-service';

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
  activeTab: 'left' | 'middle' | 'right' = 'left';
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
  countMiddle = 0;
  countRight = 0;

  // CIF de prueba: si no es organización, usamos Cruz Roja por defecto para evitar 404 en pruebas
  public readonly TEST_CIF = (localStorage.getItem('user_role') === 'organizacion'
    ? (localStorage.getItem('user_id') || 'A12345678')
    : 'A12345678').trim();

  constructor(private voluntariadoService: VoluntariadoService) { }

  ngOnInit(): void {
    this.loadAllData();
  }

  loadAllData() {
    this.isLoading = true;
    console.log('Fetching activities for CIF:', this.TEST_CIF);

    this.voluntariadoService.getActivitiesByOrganization(this.TEST_CIF, undefined, undefined).subscribe({
      next: (results: any[]) => {
        // En este componente, 'left' (Pendientes) incluye:
        // 1. Actividades ACEPTADAS que están en estado PENDIENTE o ABIERTA
        // 2. Actividades que aún están en revisión (Aprobación PENDIENTE)

        const pendingReal = results.filter(i =>
          (i.estado === 'PENDIENTE' || i.estado === 'ABIERTA') &&
          i.estadoAprobacion === 'ACEPTADA'
        );
        const inReview = results.filter(i => i.estadoAprobacion === 'PENDIENTE');

        const inCourse = results.filter(i => (i.estado === 'EN_CURSO' || i.estado === 'En Curso') && i.estadoAprobacion === 'ACEPTADA');
        const completed = results.filter(i => (i.estado === 'COMPLETADA' || i.estado === 'CERRADA') && i.estadoAprobacion === 'ACEPTADA');

        this.countPending = pendingReal.length + inReview.length;
        this.countMiddle = inCourse.length;
        this.countRight = completed.length;

        const orgName = localStorage.getItem('user_name') || 'Mi Organización';

        // Map and merge all for easier filtering
        const mapItem = (item: any, cat: string) => {
          let statusLabel = item.estado;
          if (statusLabel === 'ABIERTA' || statusLabel === 'PENDIENTE') {
            statusLabel = 'Pendiente';
          } else if (statusLabel === 'COMPLETADA' || statusLabel === 'CERRADA') {
            statusLabel = 'Completado';
          }

          return {
            ...item,
            category: cat,
            title: item.nombre,
            organization: orgName,
            // Backend now returns objects for skills and ods
            skills: item.habilidades || [],
            date: item.fechaInicio ? new Date(item.fechaInicio).toLocaleDateString() : 'Fecha pendiente',
            status: statusLabel,
            ods: item.ods || []
          };
        };

        this.allVolunteeringData = [
          ...pendingReal.map((i: any) => mapItem(i, 'left')),
          ...inReview.map((i: any) => mapItem(i, 'left')),
          ...inCourse.map((i: any) => mapItem(i, 'middle')),
          ...completed.map((i: any) => mapItem(i, 'right'))
        ];

        this.extractFilterOptions();
        this.applyFilters();
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading organization activities:', err);
        // Si hay error (como el 404), intentamos cargar al menos el nombre de la organización
        this.isLoading = false;
        if (err.status === 404) {
          console.warn('La organización no existe o el CIF es de un voluntario. Usando datos vacíos para evitar errores visuales.');
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


  openCreateModal() {
    this.modalOpen = true;
  }

  onTabChange(tab: 'left' | 'second' | 'middle' | 'right') {
    // Map 'second' to 'left' if it ever happens
    if (tab === 'second' || tab === 'left') {
      this.activeTab = 'left';
      this.tabLabel = 'Pendientes';
    } else if (tab === 'middle') {
      this.activeTab = 'middle';
      this.tabLabel = 'En Curso';
    } else if (tab === 'right') {
      this.activeTab = 'right';
      this.tabLabel = 'Completados';
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
