import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { FormsModule } from '@angular/forms';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { VolunteeringCardComponent } from '../../../components/Administrator/Volunteers/volunteering-card/volunteering-card.component';
import { VoluntariadoService } from '../../../services/voluntariado-service';
import { CrearVoluntariadoModal } from '../../../components/organization/crear-voluntariado-modal/crear-voluntariado-modal';
import { CreateMatchModalComponent } from '../../../components/Administrator/Matches/create-match-modal/create-match-modal.component';
import { CategoryService } from '../../../services/category.service';
import { NotificationService } from '../../../services/notification.service';
import { Category } from '../../../models/Category';

@Component({
  selector: 'app-activities',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    StatusToggleComponent,
    VolunteeringCardComponent,
    SidebarComponent,
    CrearVoluntariadoModal,
    CreateMatchModalComponent
  ],
  templateUrl: './activities.html',
  styleUrl: './activities.css'
})
export class ActivitiesComponent implements OnInit {
  private voluntariadoService = inject(VoluntariadoService);
  private categoryService = inject(CategoryService);
  private notificationService = inject(NotificationService);
  activeTab: 'left' | 'middle' | 'right' = 'left';

  volunteeringOpportunities: any[] = [];
  filteredOpportunities: any[] = [];

  // Filtered lists for counts
  pendingOpportunities: any[] = [];
  acceptedOpportunities: any[] = [];

  // Filter State
  showFilterModal = false;
  searchTerm: string = ''; // Bound to search input
  filters = {
    date: '',
    skills: [] as string[],
    organization: '',
    ods: [] as string[]
  };

  // Temp filters for modal
  tempFilters = {
    date: '',
    skills: [] as string[],
    organization: '',
    ods: [] as string[]
  };

  availableOrganizations: string[] = [];
  availableODS: string[] = Array.from({ length: 17 }, (_, i) => `ODS ${i + 1}`);
  availableSkills: Category[] = [];



  isLoading = true;

  ngOnInit() {
    this.loadActivities();
    this.categoryService.getHabilidades().subscribe(data => this.availableSkills = data);
  }

  loadActivities() {
    console.log('ActivitiesComponent: calling getAllVoluntariados(false, ALL)...');

    this.voluntariadoService.getAllVoluntariados(false, { estadoAprobacion: 'ALL' }).subscribe({
      next: (allData) => {
        // Helper to safely check status handling property variations
        const checkApproval = (item: any, expected: string) => {
          const val = (item.estadoAprobacion || item.estado_aprobacion || '').toUpperCase();
          return val === expected;
        };

        const mapActivity = (item: any) => {
          // Check approval status first
          const approvalStatus = (item.estadoAprobacion || item.estado_aprobacion || '').toUpperCase();

          // Date-based status calculation
          let computedStatus = 'En curso';
          const now = new Date();
          const start = item.fechaInicio ? new Date(item.fechaInicio) : null;
          const end = item.fechaFin ? new Date(item.fechaFin) : null;

          if (start && now < start) {
            computedStatus = 'Sin comenzar';
          } else if (end && now > end) {
            computedStatus = 'Completado';
          }

          // FORCE 'PENDIENTE' status for UI if approval is pending
          // Otherwise use the computed date-based status
          const finalStatus = approvalStatus === 'PENDIENTE' ? 'Pendiente' : computedStatus;

          return {
            ...item,
            id: item.codActividad || item.id,
            title: item.nombre,
            organization: item.nombre_organizacion || item.nombreOrganizacion || 'Organización',
            skills: item.habilidades || [],
            date: item.fechaInicio ? new Date(item.fechaInicio).toLocaleDateString() : 'N/A',
            ods: item.ods || [],
            estado: finalStatus
          };
        };

        // Robust client-side filtering from the SINGLE cached list
        this.pendingOpportunities = allData
          .filter(i => checkApproval(i, 'PENDIENTE'))
          .map(mapActivity);

        this.acceptedOpportunities = allData
          .filter(i => checkApproval(i, 'ACEPTADA') || checkApproval(i, 'ACEPTADO'))
          .map(mapActivity);

        console.log('Admin Pending Loaded:', this.pendingOpportunities.length);
        console.log('Admin Accepted Loaded:', this.acceptedOpportunities.length);

        // Populate common list for filtering
        this.volunteeringOpportunities = [...this.pendingOpportunities, ...this.acceptedOpportunities];

        // Extract Options for filters (from ALL loaded data)
        const orgs = new Set<string>();
        this.volunteeringOpportunities.forEach(op => {
          if (op.organization) orgs.add(op.organization);
        });
        this.availableOrganizations = Array.from(orgs).sort();

        this.applyFilters();
        this.isLoading = false;
      },
      error: (err) => {
        console.error('ActivitiesComponent: Error fetching activities:', err);
        this.isLoading = false;
      }
    });
  }

  onTabChange(tab: 'left' | 'middle' | 'right') {
    this.activeTab = tab;
    this.applyFilters();
  }

  // Filter Methods
  openFilterModal() {
    this.tempFilters = { ...this.filters, ods: [...this.filters.ods], skills: [...this.filters.skills] };
    this.showFilterModal = true;
  }

  closeFilterModal() {
    this.showFilterModal = false;
  }

  applyFilters() {
    // If called from modal, commit temps. If called from search, use current filters.
    if (this.showFilterModal) {
      this.filters = { ...this.tempFilters, ods: [...this.tempFilters.ods], skills: [...this.tempFilters.skills] };
    }

    console.log('Applying filters:', this.filters, 'Search:', this.searchTerm);
    const sourceList = this.activeTab === 'left' ? this.pendingOpportunities : this.acceptedOpportunities;

    this.filteredOpportunities = sourceList.filter(item => {
      let matchesDate = true;
      let matchesSkill = true;
      let matchesOrg = true;
      let matchesODS = true;
      let matchesSearch = true;

      // Filter by Date
      if (this.filters.date) {
        const itemDate = new Date(item.fechaInicio).toDateString();
        const filterDate = new Date(this.filters.date).toDateString();
        matchesDate = itemDate === filterDate;
      }

      // Filter by Skills (Modal)
      if (this.filters.skills.length > 0) {
        const itemSkillsNames = (item.skills || []).map((s: any) => (s.nombre || s).toLowerCase());
        matchesSkill = this.filters.skills.every(f => itemSkillsNames.some((s: string) => s.includes(f.toLowerCase())));
      }

      // Filter by Organization
      if (this.filters.organization) {
        matchesOrg = item.organization === this.filters.organization;
      }

      // Filter by ODS
      if (this.filters.ods.length > 0) {
        // item.ods is array of objects {id, nombre, color}
        const itemOdsNames = item.ods.map((o: any) => o.nombre);
        // Check if ANY of selected ODS is present
        matchesODS = this.filters.ods.some(f => itemOdsNames.includes(f));
      }

      // Filter by Search Term (Global)
      if (this.searchTerm) {
        const term = this.searchTerm.toLowerCase();
        matchesSearch = item.title.toLowerCase().includes(term) ||
          item.organization.toLowerCase().includes(term) ||
          (item.skills && item.skills.some((s: any) => (s.nombre || s).toLowerCase().includes(term)));

      }

      return matchesDate && matchesSkill && matchesSearch && matchesOrg && matchesODS;
    });

    if (this.showFilterModal) {
      this.closeFilterModal();
    }
  }

  resetFilters() {
    this.filters = { date: '', skills: [], organization: '', ods: [] };
    this.tempFilters = { date: '', skills: [], organization: '', ods: [] };
    this.searchTerm = '';
    this.applyFilters();
  }

  toggleOdsFilter(odsName: string) {
    const idx = this.tempFilters.ods.indexOf(odsName);
    if (idx === -1) {
      this.tempFilters.ods.push(odsName);
    } else {
      this.tempFilters.ods.splice(idx, 1);
    }
  }

  isOdsSelected(odsName: string): boolean {
    return this.tempFilters.ods.includes(odsName);
  }

  toggleSkillFilter(skillName: string) {
    const idx = this.tempFilters.skills.indexOf(skillName);
    if (idx === -1) {
      this.tempFilters.skills.push(skillName);
    } else {
      this.tempFilters.skills.splice(idx, 1);
    }
  }

  isSkillSelected(skillName: string): boolean {
    return this.tempFilters.skills.includes(skillName);
  }

  get activeFilterCount(): number {
    let count = 0;
    if (this.filters.date) count++;
    if (this.filters.skills.length > 0) count++;
    if (this.filters.organization) count++;
    count += this.filters.ods.length;
    return count;
  }

  onAction(item: any) {
    console.log('Action clicked for:', item.title);
    if (item.estado?.toUpperCase() === 'PENDIENTE') {
      this.voluntariadoService.actualizarEstadoActividad(item.id, 'ACEPTADA').subscribe({
        next: () => {
          this.notificationService.showSuccess('Actividad aceptada con éxito');
          this.voluntariadoService.getAllVoluntariados(true, { estadoAprobacion: 'ALL' }).subscribe(() => this.loadActivities()); // Reload with force refresh
        },
        error: (err) => {
          console.error('Error updating activity status:', err);
          this.notificationService.showError('Error al aceptar la actividad');
        }
      });
    } else {
      this.notificationService.showInfo('Esta actividad ya ha sido procesada.');
    }
  }

  showInfoModal = false;
  selectedVolunteering: any = null;

  openInfoModal(item: any) {
    this.selectedVolunteering = item;
    this.showInfoModal = true;
  }

  closeInfoModal() {
    this.showInfoModal = false;
    this.selectedVolunteering = null;
  }

  // Modal crear actividad
  modalCrearActividadOpen = false;

  openCreateModal() {
    this.modalCrearActividadOpen = true;
  }

  onVoluntariadoCreated(newVoluntariado: any) {
    this.voluntariadoService.crearActividad(newVoluntariado).subscribe(() => {
      this.voluntariadoService.getAllVoluntariados(true).subscribe(() => this.loadActivities());
      this.modalCrearActividadOpen = false;
      this.notificationService.showSuccess('Actividad creada con éxito');
    });
  }

  // Assign Modal
  showAssignModal = false;
  assignActivityId: number | null = null;

  openAssignModal(item: any) {
    this.assignActivityId = item.id;
    this.showAssignModal = true;
  }

  onMatchCreated() {
    this.showAssignModal = false;
    this.notificationService.showSuccess('Voluntario asignado correctamente');
  }
  parseJson(value: any): string[] {
    if (!value) return [];
    if (Array.isArray(value)) return value;
    if (typeof value === 'string') {
      value = value.trim();
      if (value.startsWith('[') && value.endsWith(']')) {
        try {
          const parsed = JSON.parse(value);
          return Array.isArray(parsed) ? parsed : [value];
        } catch (e) {
          console.error('Error parsing JSON:', value, e);
        }
      }
      // Fallback for CSV
      return value.split(',').map((s: string) => s.trim()).filter((s: string) => s.length > 0);
    }
    return [String(value)];
  }
}
