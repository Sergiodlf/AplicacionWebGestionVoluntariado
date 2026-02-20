import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarVolunteer } from '../../../components/Volunteer/sidebar-volunteer/sidebar-volunteer';
import { VoluntariadoService } from '../../../services/voluntariado-service';
import { VoluntariadoCard } from '../../../components/Volunteer/voluntariado-card/voluntariado-card';
import { NotificationService } from '../../../services/notification.service';
import { Voluntariado } from '../../../models/Voluntariado';

@Component({
  selector: 'app-voluntariados',
  standalone: true,
  imports: [CommonModule, FormsModule, SidebarVolunteer, VoluntariadoCard],
  templateUrl: './voluntariados.html',
  styleUrl: './voluntariados.css',
})
export class Voluntariados implements OnInit {
  private voluntariadoService = inject(VoluntariadoService);
  private notificationService = inject(NotificationService);

  // Fetch from session or fallback
  currentDNI = localStorage.getItem('user_id') || '00000000A';

  // Raw data from API
  allVoluntariados: Voluntariado[] = [];
  myInscripciones: any[] = [];

  // Display data
  // Display data
  displayedVoluntariados: Voluntariado[] = [];

  // Filter State
  searchTerm: string = '';
  showFilterModal = signal(false);
  tempFilters = {
    localidad: '',
    sector: '',
  };
  filterCriteria = {
    localidad: '',
    sector: '',
  };
  availableLocations: string[] = [];
  availableSectors: string[] = [];

  isLoading = true;

  ngOnInit() {
    this.loadData();
  }

  loadData() {
    // 1. Get all available activities
    this.voluntariadoService
      .getAllVoluntariados(true, {
        estadoAprobacion: 'ALL',
        history: true,
      })
      .subscribe({
        next: (data) => {
          this.allVoluntariados = data.map((v: any) => {
            return {
              ...v,
              codAct: v.codActividad, // Map ID
              title: v.nombre,
              organization: v.nombre_organizacion, // Map Organization Name
              skills: v.habilidades || [],
              date: v.fechaInicio,
              ods: v.ods || [],
            } as Voluntariado;
          });
          this.extractFilterOptions();
          this.filterData();
          this.isLoading = false;
        },
        error: (err: any) => {
          console.error('Error fetching voluntariados', err);
          this.isLoading = false;
        },
      });

    // 2. Get my inscriptions (pending matches)
    // FIX: Fetch ALL inscriptions to ensure duplicate check works for Confirmed/Accepted too.
    this.voluntariadoService.getMyInscripciones().subscribe({
      next: (data: any[]) => {
        this.myInscripciones = data;
        this.filterData();
      },
      error: (err: any) => {
        console.error('Error fetching inscripciones', err);
        // Fallback or empty if 404
        this.myInscripciones = [];
        this.filterData();
      },
    });
  }

  extractFilterOptions() {
    const locs = new Set<string>();
    const secs = new Set<string>();
    this.allVoluntariados.forEach((v: any) => {
      // Assuming 'direccion' and 'sector' exist on the API response even if not mapped directly in interface yet
      if (v.direccion) locs.add(v.direccion);
      if (v.sector) secs.add(v.sector);
    });
    this.availableLocations = Array.from(locs).sort();
    this.availableSectors = Array.from(secs).sort();
  }

  filterData() {
    const inscIds = new Set(
      (this.myInscripciones || [])
        .map((i) => i.codActividad ?? i.id_actividad ?? i.codActividadInscrita)
        .filter((x: any) => x !== undefined && x !== null)
        .map((x: any) => String(x)),
    );

    const inscNames = new Set(
      (this.myInscripciones || [])
        .map((i) => i.actividad ?? i.nombre ?? i.title)
        .filter(Boolean)
        .map((x: string) => x.trim().toLowerCase()),
    );

    this.displayedVoluntariados = this.allVoluntariados.filter((v) => {
      // 0) Excluir si ya estoy inscrito
      const vId = v.codAct != null ? String(v.codAct) : '';
      const vName = (v.title || '').trim().toLowerCase();
      if ((vId && inscIds.has(vId)) || (vName && inscNames.has(vName)))
        return false;

      // 1) Search
      const title = v.title || '';
      if (
        this.searchTerm &&
        !title.toLowerCase().includes(this.searchTerm.toLowerCase())
      )
        return false;

      // 2) Filters
      const item = v as any;
      if (
        this.filterCriteria.localidad &&
        item.direccion !== this.filterCriteria.localidad
      )
        return false;
      if (
        this.filterCriteria.sector &&
        item.sector !== this.filterCriteria.sector
      )
        return false;

      return true;
    });
  }

  applyFilters() {
    this.filterData();
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

  onAction(item: any) {
    this.inscribirse(item);
  }

  inscribirse(item: Voluntariado) {
    // 0. Pre-check: Check if already signed up locally
    // We check against 'myInscripciones'. The API might return 'actividad' (name) or 'id_actividad'/'codActividad'.
    // 0. Pre-check: Check if already signed up locally
    // We check against 'myInscripciones'. The API might return 'actividad' (name) or 'id_actividad'/'codActividad'.

    const alreadySignedUp = this.myInscripciones.some((i) => {
      const matchId =
        (i.codActividad && i.codActividad === item.codAct) ||
        (i.id_actividad && i.id_actividad === item.codAct);
      const matchName =
        (i.actividad && i.actividad === item.title) ||
        (i.nombre && i.nombre === item.title);
      return matchId || matchName;
    });

    if (alreadySignedUp) {
      this.notificationService.showWarning(
        'Ya estás inscrito en el voluntariado: ' + item.title,
      );
      return;
    }

    this.voluntariadoService
      .inscribirVoluntario(this.currentDNI, item.codAct)
      .subscribe({
        next: (res) => {
          this.notificationService.showSuccess(
            'Te has apuntado correctamente!',
          );
          // Refresh data to update "status" and clear cache
          this.voluntariadoService
            .getMyInscripciones(this.currentDNI, true)
            .subscribe(() => {
              this.loadData();
            });
        },
        error: (err: any) => {
          console.error('Error al inscribirse', err);
          // Try to show more specific error from backend if available
          const serverMsg = err.error?.message || err.error?.error || '';
          this.notificationService.showError(
            `Error al apuntarse. ${serverMsg} Verifica que no estés ya inscrito.`,
          );
        },
      });
  }
}
