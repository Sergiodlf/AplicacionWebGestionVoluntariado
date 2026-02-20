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
import { filter, take } from 'rxjs/operators';

@Component({
  selector: 'app-mis-voluntariados-organizacion',
  standalone: true,
  imports: [CommonModule, StatusToggleVoluntariado, VoluntariadoCard, CrearVoluntariadoModal, FormsModule],
  templateUrl: './mis-voluntariados-organizacion.html',
  styleUrl: './mis-voluntariados-organizacion.css',
})
export class MisVoluntariadosOrganizacion implements OnInit {
  activeTab: 'left' | 'second' | 'middle' | 'right' = 'left';
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
  countPorEmpezar = 0;
  countEnCurso = 0;
  countCompleted = 0;

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

    // Use userProfile$ observable to wait for the profile to be loaded
    this.authService.userProfile$.pipe(
      filter(profile => !!profile && profile.tipo === 'organizacion'),
      take(1)
    ).subscribe(profile => {
      this.currentCif = profile!.datos.cif;

      if (!this.currentCif) {
        console.error('No CIF found for logged in user.');
        this.isLoading = false;
        return;
      }

      this.fetchActivities();
    });

    if (!this.authService.getCurrentProfile()) {
      const backupCif = localStorage.getItem('user_cif');
      if (backupCif) {
        this.currentCif = backupCif;
        this.fetchActivities();
        return;
      }
    }
  }

  fetchActivities() {
    if (!this.currentCif) return;

    const pending$ = this.voluntariadoService.getActivitiesByOrganization(this.currentCif, undefined, 'PENDIENTE');
    const accepted$ = this.voluntariadoService.getActivitiesByOrganization(this.currentCif, undefined, 'ACEPTADA');

    forkJoin([pending$, accepted$]).subscribe({
      next: ([pendingRes, acceptedRes]) => {
        const normalize = (s: any) => String(s || '').toUpperCase().trim();
        const checkApproval = (item: any, expected: string) => {
          const val = normalize(item.estadoAprobacion || item.estado_aprobacion || item.status || '');
          if (expected === 'ACEPTADA') {
            return val === 'ACEPTADA' || val === 'ACEPTADO' || val === 'CONFIRMADA' || val === 'CONFIRMADO' || val === 'APROBADA';
          }
          return val === expected;
        };

        const orgName = localStorage.getItem('user_name') || 'Mi Organización';

        const mapItem = (item: any, cat: 'left' | 'second' | 'middle' | 'right') => {
          const parseDateStr = (d: any) => {
            if (!d) return '';
            const date = new Date(d);
            return isNaN(date.getTime()) ? '' : date.toLocaleDateString();
          };

          return {
            ...item,
            id: item.codActividad || item.id || Math.random(),
            category: cat,
            title: item.nombre || item.title || 'Actividad sin nombre',
            organization: orgName,
            skills: item.habilidades || [],
            necesidades: this.parseJson(item.necesidades || item.skills),
            fechaInicio: parseDateStr(item.fechaInicio || item.fecha_inicio),
            fechaFin: parseDateStr(item.fechaFin || item.fecha_fin),
            fechaInicioRaw: item.fechaInicio || item.fecha_inicio,
            fechaFinRaw: item.fechaFin || item.fecha_fin,
            status: cat === 'left' ? 'Pendiente' : (cat === 'second' ? 'Por Empezar' : (cat === 'middle' ? 'En Curso' : 'Completadas')),
            date: parseDateStr(item.fechaInicio || item.fecha_inicio),
            buttonText: cat === 'left' ? 'Aceptar' : '',
            ods: item.ods || []
          };
        };

        const now = new Date();

        const pendingMapped = pendingRes
          .filter(i => checkApproval(i, 'PENDIENTE'))
          .map(i => mapItem(i, 'left'));

        const acceptedRaw = acceptedRes.filter(i => checkApproval(i, 'ACEPTADA'));

        const porEmpezarMapped: any[] = [];
        const enCursoMapped: any[] = [];
        const completedMapped: any[] = [];

        acceptedRaw.forEach(i => {
          const start = i.fechaInicio ? new Date(i.fechaInicio) : null;
          const end = i.fechaFin ? new Date(i.fechaFin) : null;

          if (end && now > end) {
            completedMapped.push(mapItem(i, 'right'));
          } else if (start && now < start) {
            porEmpezarMapped.push(mapItem(i, 'second'));
          } else {
            enCursoMapped.push(mapItem(i, 'middle'));
          }
        });

        this.countPending = pendingMapped.length;
        this.countPorEmpezar = porEmpezarMapped.length;
        this.countEnCurso = enCursoMapped.length;
        this.countCompleted = completedMapped.length;

        this.allVolunteeringData = [
          ...pendingMapped,
          ...porEmpezarMapped,
          ...enCursoMapped,
          ...completedMapped
        ];

        this.extractFilterOptions();
        this.applyFilters();
        this.isLoading = false;
      },
      error: (err: any) => {
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
      if (v.direccion) locs.add(v.direccion);
      if (v.sector) secs.add(v.sector);
    });
    this.availableLocations = Array.from(locs).sort();
    this.availableSectors = Array.from(secs).sort();
  }

  applyFilters() {
    this.volunteeringData = this.allVolunteeringData.filter(v => {
      if (v.category !== this.activeTab) return false;
      if (this.searchTerm && !v.title.toLowerCase().includes(this.searchTerm.toLowerCase())) return false;
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
    this.editingActivity = item;
    this.modalOpen = true;
  }

  onTabChange(tab: 'left' | 'second' | 'middle' | 'right') {
    this.activeTab = tab;
    switch (tab) {
      case 'left': this.tabLabel = 'Pendientes'; break;
      case 'second': this.tabLabel = 'Por Empezar'; break;
      case 'middle': this.tabLabel = 'En Curso'; break;
      case 'right': this.tabLabel = 'Completadas'; break;
    }
    this.applyFilters();
  }

  onAction(item: any) {
    // Basic action placeholder
  }

  onVoluntariadoCreated(newVoluntariado: any) {
    this.loadAllData();
    this.modalOpen = false;
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
      return value.split(',').map((s: string) => s.trim()).filter((s: string) => s.length > 0);
    }
    return [String(value)];
  }
}
