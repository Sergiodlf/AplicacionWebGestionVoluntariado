import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { FormsModule } from '@angular/forms';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { VolunteeringCardComponent } from '../../../components/Administrator/Volunteers/volunteering-card/volunteering-card.component';
import { VoluntariadoService, Voluntariado } from '../../../services/voluntariado-service';
import { CrearVoluntariadoModal } from '../../../components/organization/crear-voluntariado-modal/crear-voluntariado-modal';
import { CreateMatchModalComponent } from '../../../components/Administrator/Matches/create-match-modal/create-match-modal.component';

@Component({
  selector: 'app-activities',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    StatusToggleComponent,
    VolunteeringCardComponent,
    Navbar,
    SidebarComponent,
    CrearVoluntariadoModal,
    CreateMatchModalComponent
  ],
  templateUrl: './activities.html',
  styleUrl: './activities.css'
})
export class ActivitiesComponent implements OnInit {
  private voluntariadoService = inject(VoluntariadoService);
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
    skill: '',
    organization: '',
    ods: [] as string[]
  };

  // Temp filters for modal
  tempFilters = {
    date: '',
    skill: '',
    organization: '',
    ods: [] as string[]
  };

  availableOrganizations: string[] = [];
  availableODS: string[] = Array.from({ length: 17 }, (_, i) => `ODS ${i + 1}`);

  ngOnInit() {
    this.loadActivities();
  }

  loadActivities() {
    console.log('ActivitiesComponent: calling getAllVoluntariados()...');
    this.voluntariadoService.getAllVoluntariados().subscribe({
      next: (data) => {
        console.log('ActivitiesComponent: Data received from API:', data);
        this.volunteeringOpportunities = data.map((item: any) => ({
          ...item,
          id: item.codActividad || item.id, // Fallback if interface differs slightly
          title: item.nombre,
          organization: item.nombre_organizacion || 'Organización (API)',
          skills: this.parseJson(item.habilidades).length > 0 ? this.parseJson(item.habilidades) : ['General'],
          date: item.fechaInicio ? new Date(item.fechaInicio).toLocaleDateString() : 'N/A',
          ods: Array.isArray(item.ods)
            ? item.ods.map((o: any) => {
              if (typeof o === 'object' && o !== null) {
                return {
                  id: o.id || 0,
                  name: o.name || o.nombre || 'ODS',
                  color: o.color || '#00c851'
                };
              } else {
                return {
                  id: 0,
                  name: `ODS ${o}`,
                  color: '#00c851'
                };
              }
            })
            : (typeof item.ods === 'string' ? [{ id: 0, name: item.ods, color: '#00c851' }] : [{ id: 4, name: 'ODS 4', color: '#00c851' }])
        }));

        // Extract Filter Options
        const orgs = new Set<string>();
        this.volunteeringOpportunities.forEach(op => {
          if (op.organization) orgs.add(op.organization);
        });
        this.availableOrganizations = Array.from(orgs).sort();

        // Calculate filtered lists
        this.pendingOpportunities = this.volunteeringOpportunities.filter(o => o.estado?.toUpperCase() === 'PENDIENTE');
        this.acceptedOpportunities = this.volunteeringOpportunities.filter(o => o.estado?.toUpperCase() === 'ABIERTA' || o.estado?.toUpperCase() === 'ACEPTADA' || o.estado?.toUpperCase() === 'EN CURSO');

        this.applyFilters(); // Apply initial empty filters
      },
      error: (err) => {
        console.error('ActivitiesComponent: Error fetching activities:', err);
      }
    });
  }

  onTabChange(tab: 'left' | 'middle' | 'right') {
    this.activeTab = tab;
    this.applyFilters();
  }

  // Filter Methods
  openFilterModal() {
    this.tempFilters = { ...this.filters, ods: [...this.filters.ods] };
    this.showFilterModal = true;
  }

  closeFilterModal() {
    this.showFilterModal = false;
  }

  applyFilters() {
    // If called from modal, commit temps. If called from search, use current filters.
    if (this.showFilterModal) {
      this.filters = { ...this.tempFilters, ods: [...this.tempFilters.ods] };
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

      // Filter by Skill (Modal)
      if (this.filters.skill) {
        const term = this.filters.skill.toLowerCase();
        matchesSkill = item.skills && item.skills.some((s: string) => s.toLowerCase().includes(term));
      }

      // Filter by Organization
      if (this.filters.organization) {
        matchesOrg = item.organization === this.filters.organization;
      }

      // Filter by ODS
      if (this.filters.ods.length > 0) {
        // item.ods is array of objects {id, name, color}
        const itemOdsNames = item.ods.map((o: any) => o.name);
        // Check if ANY of selected ODS is present
        matchesODS = this.filters.ods.some(f => itemOdsNames.includes(f));
      }

      // Filter by Search Term (Global)
      if (this.searchTerm) {
        const term = this.searchTerm.toLowerCase();
        matchesSearch = item.title.toLowerCase().includes(term) ||
          item.organization.toLowerCase().includes(term) ||
          (item.skills && item.skills.some((s: string) => s.toLowerCase().includes(term)));

      }

      return matchesDate && matchesSkill && matchesSearch && matchesOrg && matchesODS;
    });

    if (this.showFilterModal) {
      this.closeFilterModal();
    }
  }

  resetFilters() {
    this.filters = { date: '', skill: '', organization: '', ods: [] };
    this.tempFilters = { date: '', skill: '', organization: '', ods: [] };
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

  get activeFilterCount(): number {
    let count = 0;
    if (this.filters.date) count++;
    if (this.filters.skill) count++;
    if (this.filters.organization) count++;
    count += this.filters.ods.length;
    return count;
  }

  onAction(item: any) {
    console.log('Action clicked for:', item.title);
    if (item.estado?.toUpperCase() === 'PENDIENTE') {
      this.voluntariadoService.actualizarEstadoActividad(item.id, 'ACEPTADA').subscribe({
        next: () => {
          alert('Actividad aceptada con éxito');
          this.voluntariadoService.getAllVoluntariados(true).subscribe(() => this.loadActivities()); // Reload with force refresh
        },
        error: (err) => {
          console.error('Error updating activity status:', err);
          alert('Error al aceptar la actividad');
        }
      });
    } else {
      alert('Esta actividad ya ha sido procesada.');
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
    alert('Voluntario asignado correctamente');
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
