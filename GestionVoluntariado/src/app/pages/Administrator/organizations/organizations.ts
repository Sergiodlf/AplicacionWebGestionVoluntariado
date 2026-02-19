import { Component, OnDestroy, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { OrganizationCardComponent } from '../../../components/Administrator/Organizations/organization-card/organization-card.component';
import { OrganizationFormComponent } from '../../../components/Global-Components/organization-form/organization-form.component';
import { OrganizationService } from '../../../services/organization.service';
import { Organization } from '../../../models/organizationModel';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-organizations',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule, // Added to fix ngModel
    SidebarComponent,
    StatusToggleComponent,
    OrganizationCardComponent,
    OrganizationFormComponent,
  ],
  templateUrl: './organizations.html',
  styleUrl: './organizations.css',
})
export class OrganizationsComponent implements OnInit, OnDestroy {
  constructor(private organizationService: OrganizationService) { }
  activeTab: 'left' | 'middle' | 'right' = 'left'; // 'left' = Pending (Pendientes), 'middle' = Pending (compat), 'right' = Approved (Aceptados)

  organizations = signal<Organization[]>([]);
  private organizationsSubscription: Subscription | undefined;
  private updateSubscription: Subscription | undefined; // Suscripción para notificaciones del servicio
  currentTab: 'PENDIENTE' | 'APROBADO' = 'PENDIENTE';
  showModal = signal(false);
  pendingCount = signal(0);
  approvedCount = signal(0);

  // Modal detalle actividad
  showActivityModal = signal(false);
  selectedActivity: any = null;

  // Data storage
  allOrganizations: Organization[] = [];

  // Filter Criteria
  filterCriteria = {
    localidad: '',
    sector: ''
  };

  searchTerm: string = ''; // Added for search functionality

  // Temporary state for modal
  tempFilters = {
    localidad: '',
    sector: ''
  };

  // Available options for dropdowns
  availableLocations: string[] = [];
  availableSectors: string[] = [];

  showFilterModal = signal(false);

  isLoading = true;

  ngOnInit(): void {
    this.loadOrganizations();
    this.setupUpdateSubscription(); // Inicia la escucha de notificaciones
  }

  ngOnDestroy(): void {
    if (this.organizationsSubscription) {
      this.organizationsSubscription.unsubscribe();
    }
    if (this.updateSubscription) {
      this.updateSubscription.unsubscribe();
    }
    this.setBodyScroll(false);
  }

  private setBodyScroll(lock: boolean) {
    if (lock) {
      document.body.classList.add('body-modal-open');
    } else {
      document.body.classList.remove('body-modal-open');
    }
  }

  /**
   * Se suscribe al Subject del servicio para recargar la lista si un hijo
   * llama a notifyOrganizationUpdate().
   */
  setupUpdateSubscription(): void {
    this.updateSubscription = this.organizationService.organizationUpdated$.subscribe(() => {
      this.loadOrganizations(true);
    });
  }

  loadOrganizations(forceReload: boolean = false): void {
    if (this.organizationsSubscription) {
      this.organizationsSubscription.unsubscribe();
    }
    this.organizationsSubscription = this.organizationService.getOrganizations(forceReload).subscribe({
      next: (data) => {
        this.allOrganizations = data; // Store all data
        this.extractFilterOptions(data); // Populate dropdowns

        this.filterAndSetOrganizations(); // Apply filters

        const pending = data.filter((org) => org.estado?.toLowerCase() === 'pendiente');
        const approved = data.filter((org) => org.estado?.toLowerCase() === 'aprobado');
        this.pendingCount.set(pending.length);
        this.approvedCount.set(approved.length);
        this.isLoading = false;
      },
      error: (error) => {
        console.error('Error al obtener organizaciones de la API:', error);
        this.isLoading = false;
      },
    });
  }

  extractFilterOptions(data: Organization[]) {
    const locs = new Set<string>();
    const secs = new Set<string>();
    data.forEach(org => {
      if (org.localidad) locs.add(org.localidad);
      if (org.sector) secs.add(org.sector);
    });
    this.availableLocations = Array.from(locs).sort();
    this.availableSectors = Array.from(secs).sort();
  }

  filterAndSetOrganizations(): void {
    const filtered = this.allOrganizations.filter((org) => {
      // 1. Status Filter (Tab)
      const status = org.estado?.trim().toLowerCase();
      const target = this.currentTab === 'PENDIENTE' ? 'pendiente' : 'aprobado';
      if (status !== target) return false;

      // 2. Attribute Filters
      if (this.filterCriteria.localidad && org.localidad !== this.filterCriteria.localidad) return false;
      if (this.filterCriteria.sector && org.sector !== this.filterCriteria.sector) return false;

      // 3. Search Term Filter
      if (this.searchTerm) {
        const term = this.searchTerm.toLowerCase();
        const matchesName = org.nombre?.toLowerCase().includes(term);
        const matchesEmail = org.email?.toLowerCase().includes(term);
        // Include other fields if necessary
        if (!matchesName && !matchesEmail) return false;
      }

      return true;
    });
    this.organizations.set(filtered);
  }

  // LÓGICA DE INTERFAZ Y ACCIONES //

  onTabChange(tab: 'left' | 'middle' | 'right') {
    this.activeTab = tab;
    // Map status-toggle options to our currentTab ('pending' | 'approved')
    if (tab === 'right') {
      this.currentTab = 'APROBADO';
    } else {
      // treat 'left' and 'middle' as pending for this page
      this.currentTab = 'PENDIENTE';
    }
    this.filterAndSetOrganizations(); // Re-filter based on new tab
  }

  onAddOrganization(): void {
    this.showModal.set(true);
    this.setBodyScroll(true);
  }

  openFilterModal() {
    // Copy current state to temp
    this.tempFilters = { ...this.filterCriteria };
    this.showFilterModal.set(true);
    this.setBodyScroll(true);
  }

  closeFilterModal() {
    this.showFilterModal.set(false);
    this.setBodyScroll(false);
  }

  applyFilters() {
    this.filterCriteria = { ...this.tempFilters };
    this.filterAndSetOrganizations();
    this.closeFilterModal();
  }

  resetFilters() {
    this.tempFilters = { localidad: '', sector: '' };
    this.applyFilters();
  }

  get activeFilterCount(): number {
    let count = 0;
    if (this.filterCriteria.localidad) count++;
    if (this.filterCriteria.sector) count++;
    return count;
  }

  closeModal(): void {
    this.showModal.set(false);
    this.setBodyScroll(false);
  }

  onFormSubmit(data: Organization): void {
    this.closeModal();
    this.organizationService.notifyOrganizationUpdate();
  }

  openActivityModal(activity: any) {
    this.selectedActivity = activity;
    this.showActivityModal.set(true);
    this.setBodyScroll(true);
  }

  closeActivityModal() {
    this.showActivityModal.set(false);
    this.selectedActivity = null;
    this.setBodyScroll(false);
  }
}
