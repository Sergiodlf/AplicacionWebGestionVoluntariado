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

import { StatusToggleVoluntariado } from '../../../components/Volunteer/status-toggle-voluntariado/status-toggle-voluntariado';

@Component({
  selector: 'app-activities',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    StatusToggleVoluntariado,
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
  activeTab: 'left' | 'second' | 'middle' | 'right' = 'left';

  volunteeringOpportunities: any[] = [];
  filteredOpportunities: any[] = [];

  // Filtered lists for counts
  pendingOpportunities: any[] = [];
  porEmpezarOpportunities: any[] = [];
  enCursoOpportunities: any[] = [];
  completedOpportunities: any[] = [];
  acceptedOpportunities: any[] = []; // For compatibility/counting

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

    this.voluntariadoService.getAllVoluntariados(false, { estadoAprobacion: 'ALL' }).subscribe({
      next: (allData) => {
        const normalize = (s: any) => String(s || '').toUpperCase().trim();
        const checkApproval = (item: any, expected: string) => {
          const val = normalize(item.estadoAprobacion || item.estado_aprobacion || item.status || '');
          if (expected === 'ACEPTADA') {
            return val === 'ACEPTADA' || val === 'ACEPTADO' || val === 'CONFIRMADA' || val === 'CONFIRMADO' || val === 'APROBADA';
          }
          return val === expected;
        };

        const mapActivity = (item: any) => {
          const parseDateStr = (d: any) => {
            if (!d) return '';
            const date = new Date(d);
            return isNaN(date.getTime()) ? '' : date.toLocaleDateString();
          };

          return {
            ...item,
            id: item.codActividad || item.id || Math.random(),
            title: item.nombre || item.title || 'Sin Título',
            organization: item.nombreOrganizacion || item.nombre_organizacion || item.organizacion || 'Organización',
            skills: item.habilidades || [],
            fechaInicio: parseDateStr(item.fechaInicio || item.fecha_inicio),
            fechaFin: parseDateStr(item.fechaFin || item.fecha_fin),
            fechaInicioRaw: item.fechaInicio || item.fecha_inicio,
            fechaFinRaw: item.fechaFin || item.fecha_fin,
            ods: item.ods || [],
            date: parseDateStr(item.fechaInicio || item.fecha_inicio),
            necesidades: this.parseJson(item.necesidades || item.skills),
            estado: normalize(item.estadoAprobacion || item.estado_aprobacion || 'PENDIENTE') === 'PENDIENTE' ? 'Pendiente' : 'Aceptada'
          };
        };

        const now = new Date();

        this.pendingOpportunities = allData
          .filter(i => checkApproval(i, 'PENDIENTE'))
          .map(mapActivity);

        const acceptedAll = allData
          .filter(i => checkApproval(i, 'ACEPTADA'))
          .map(mapActivity);

        acceptedAll.forEach(i => {
          const start = i.fechaInicioRaw ? new Date(i.fechaInicioRaw) : null;
          const end = i.fechaFinRaw ? new Date(i.fechaFinRaw) : null;

          if (end && now > end) {
            i.estadoDisplay = 'Completadas'; // Changed to plural to match tab
          } else if (start && now < start) {
            i.estadoDisplay = 'Por Empezar';
          } else {
            i.estadoDisplay = 'En Curso';
          }

          if (i.estadoDisplay === 'Completadas') this.completedOpportunities.push(i);
          else if (i.estadoDisplay === 'Por Empezar') this.porEmpezarOpportunities.push(i);
          else this.enCursoOpportunities.push(i);
        });

        this.acceptedOpportunities = [...this.porEmpezarOpportunities, ...this.enCursoOpportunities, ...this.completedOpportunities];
        this.volunteeringOpportunities = [...this.pendingOpportunities, ...this.acceptedOpportunities];

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

  onTabChange(tab: 'left' | 'second' | 'middle' | 'right') {
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

    let sourceList: any[] = [];
    switch (this.activeTab) {
      case 'left': sourceList = this.pendingOpportunities; break;
      case 'second': sourceList = this.porEmpezarOpportunities; break;
      case 'middle': sourceList = this.enCursoOpportunities; break;
      case 'right': sourceList = this.completedOpportunities; break;
    }

    this.filteredOpportunities = sourceList.filter(item => {
      let matchesDate = true;
      let matchesSkill = true;
      let matchesOrg = true;
      let matchesODS = true;
      let matchesSearch = true;

      // Filter by Date
      if (this.filters.date) {
        const itemDate = new Date(item.fechaInicioRaw || item.fechaInicio).toDateString();
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
