import { Component, inject, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { VolunteerCardComponent } from '../../../components/Administrator/Volunteers/volunteer-card/volunteer-card.component';
import { VolunteerFormComponent } from '../../../components/Global-Components/volunteer-form/volunteer-form.component';
import { BehaviorSubject, combineLatest, of } from 'rxjs';
import { map, switchMap, catchError } from 'rxjs/operators';
import { VolunteerService } from '../../../services/volunteer.service';
import { CategoryService } from '../../../services/category.service';
import { NotificationService } from '../../../services/notification.service';
import { CreateMatchModalComponent } from '../../../components/Administrator/Matches/create-match-modal/create-match-modal.component';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';

@Component({
  selector: 'app-volunteers',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    StatusToggleComponent,
    VolunteerCardComponent,
    VolunteerFormComponent,
    CreateMatchModalComponent,
    Navbar,
    SidebarComponent,
  ],
  templateUrl: './volunteers.component.html',
  styleUrl: './volunteers.component.css',
})
export class VolunteersComponent implements OnInit, OnDestroy {
  private volunteerService = inject(VolunteerService);
  private categoryService = inject(CategoryService);
  private notificationService = inject(NotificationService);
  private refresh$ = new BehaviorSubject<boolean>(false);

  isLoading = true;

  // Filter Criteria Subject
  private filterCriteria$ = new BehaviorSubject<any>({
    zona: '',
    habilidades: [],
    intereses: [],
    disponibilidad: [],
    text: '',
  });

  searchTerm: string = '';

  // Available Options
  availableZones: string[] = [
    'Casco Viejo',
    'Ensanche',
    'San Juan',
    'Iturrama',
    'Rochapea',
    'Txantrea',
    'Azpiligaña',
    'Milagrosa',
    'Buztintxuri',
    'Mendillorri',
    'Sarriguren',
    'Barañáin',
    'Burlada',
    'Villava',
    'Uharte',
    'Berriozar',
    'Ansoáin',
    'Noáin',
    'Zizur Mayor',
    'Mutilva',
  ];
  availableSkills: any[] = [];
  availableInterests: any[] = [];
  availableAvailability: string[] = [
    'Lunes Mañana',
    'Lunes Tarde',
    'Martes Mañana',
    'Martes Tarde',
    'Miércoles Mañana',
    'Miércoles Tarde',
    'Jueves Mañana',
    'Jueves Tarde',
    'Viernes Mañana',
    'Viernes Tarde',
    'Fines de Semana',
  ];

  // Temporary filter state for the modal
  tempFilters: any = {
    zona: '',
    habilidades: [],
    intereses: [],
    disponibilidad: [],
  };

  // Main data stream
  private rawVolunteers$ = this.refresh$.pipe(
    switchMap((force) => {
      this.isLoading = true; // Start loading
      return this.volunteerService.getVolunteers(force).pipe(
        map(data => {
          this.isLoading = false; // Stop loading on success
          return data;
        }),
        catchError((err) => {
          console.error('Error fetching volunteers:', err);
          this.isLoading = false; // Stop loading on error
          return of([]);
        })
      );
    })
  );

  // Filtered stream
  filteredVolunteers$ = combineLatest([this.rawVolunteers$, this.filterCriteria$]).pipe(
    map(([volunteers, criteria]) => {
      return volunteers.filter((v) => {
        if (criteria.text) {
          const term = criteria.text.toLowerCase();
          const matchesName = v.nombre?.toLowerCase().includes(term);
          const matchesEmail = v.email?.toLowerCase().includes(term);
          if (!matchesName && !matchesEmail) return false;
        }

        if (criteria.zona && v.zona !== criteria.zona) return false;

        if (criteria.habilidades.length > 0) {
          const vSkillsNames = Array.isArray(v.habilidades)
            ? v.habilidades.map((s: any) => s.nombre || s)
            : [];
          const hasSkill = criteria.habilidades.some((s: string) => vSkillsNames.includes(s));
          if (!hasSkill) return false;
        }

        if (criteria.intereses.length > 0) {
          const vInterestsNames = Array.isArray(v.intereses)
            ? v.intereses.map((i: any) => i.nombre || i)
            : [];
          const hasInterest = criteria.intereses.some((i: string) => vInterestsNames.includes(i));
          if (!hasInterest) return false;
        }

        if (criteria.disponibilidad.length > 0) {
          const vAvailability = Array.isArray(v.disponibilidad) ? v.disponibilidad : [];
          const hasAvailability = criteria.disponibilidad.some((a: string) =>
            vAvailability.includes(a)
          );
          if (!hasAvailability) return false;
        }

        return true;
      });
    })
  );

  activeTab: 'left' | 'middle' | 'right' = 'left';

  // Derived filtered streams
  pendingVolunteers$ = this.filteredVolunteers$.pipe(
    map((list) => list.filter((v) => v.status === 'PENDIENTE'))
  );
  approvedVolunteers$ = this.filteredVolunteers$.pipe(
    map((list) => list.filter((v) => v.status === 'ACEPTADO'))
  );

  showModal = false;
  showFilterModal = false;
  showCreateMatchModal = false;
  selectedVolunteerForMatch: any = null;


  ngOnInit() {
    this.categoryService.getHabilidades().subscribe((data) => (this.availableSkills = data));
    this.categoryService.getIntereses().subscribe((data) => (this.availableInterests = data));
  }

  ngOnDestroy() {
    this.unlockBody();
  }

  onTabChange(tab: 'left' | 'middle' | 'right') {
    this.activeTab = tab;
  }

  openMatchModal(volunteer: any) {
    this.selectedVolunteerForMatch = volunteer;
    this.showCreateMatchModal = true;
  }

  onMatchCreated() {
    this.showCreateMatchModal = false;
    this.selectedVolunteerForMatch = null;
    this.refresh$.next(true);
  }

  applyFilters() {
    const current = this.filterCriteria$.value;
    this.filterCriteria$.next({
      ...current,
      ...this.tempFilters,
      text: this.searchTerm,
    });
    this.closeModal('filter');
  }

  resetFilters() {
    this.tempFilters = {
      zona: '',
      habilidades: [],
      intereses: [],
      disponibilidad: [],
    };
    this.searchTerm = '';
    this.applyFilters();
  }

  toggleFilterItem(category: 'habilidades' | 'intereses' | 'disponibilidad', item: any) {
    const list = this.tempFilters[category];
    const identifier = category === 'disponibilidad' ? item : item.nombre || item;
    const index = list.indexOf(identifier);
    if (index === -1) {
      list.push(identifier);
    } else {
      list.splice(index, 1);
    }
  }

  isFilterSelected(category: 'habilidades' | 'intereses' | 'disponibilidad', item: any): boolean {
    const identifier = category === 'disponibilidad' ? item : item.nombre || item;
    return this.tempFilters[category].includes(identifier);
  }

  get activeFilterCount(): number {
    const c = this.filterCriteria$.value;
    let count = 0;
    if (c.zona) count++;
    count += c.habilidades.length;
    count += c.intereses.length;
    count += c.disponibilidad.length;
    return count;
  }

  openModal(type: 'add' | 'filter') {
    if (type === 'add') this.showModal = true;
    if (type === 'filter') this.showFilterModal = true;
    this.lockBody();
  }

  closeModal(type: 'add' | 'filter') {
    if (type === 'add') this.showModal = false;
    if (type === 'filter') this.showFilterModal = false;
    this.unlockBody();
  }

  private lockBody() {
    document.body.classList.add('body-modal-open');
  }

  private unlockBody() {
    document.body.classList.remove('body-modal-open');
  }

  onAccept(volunteer: any) {
    if (!volunteer.dni) return console.error('Missing DNI');
    this.volunteerService.updateStatus(volunteer.dni, 'ACEPTADO').subscribe({
      next: () => this.refresh$.next(true),
      error: (err) => console.error('Error updating status:', err),
    });
  }

  onReject(volunteer: any) {
    if (!volunteer.dni) return console.error('Missing DNI');
    this.volunteerService.updateStatus(volunteer.dni, 'RECHAZADO').subscribe({
      next: () => this.refresh$.next(true),
      error: (err) => console.error('Error updating status:', err),
    });
  }

  handleCreateVolunteer(volunteerData: any) {
    const mappedVolunteer = {
      nombre: volunteerData.nombreCompleto,
      email: volunteerData.correo,
      dni: volunteerData.dni,
      password: volunteerData.password,
      zona: volunteerData.zona,
      ciclo: volunteerData.ciclo,
      fechaNacimiento: volunteerData.fechaNacimiento,
      experiencia: volunteerData.experiencia,
      coche: volunteerData.coche,
      idiomas: volunteerData.idiomas,
      habilidades: volunteerData.habilidades,
      intereses: volunteerData.intereses,
      disponibilidad: volunteerData.disponibilidad,
    };

    this.volunteerService.createVolunteer(mappedVolunteer).subscribe({
      next: () => {
        this.refresh$.next(true);
        this.closeModal('add');
        this.notificationService.showSuccess('Voluntario creado con éxito');
      },
      error: (error) => {
        console.error('Error creating volunteer', error);
        // Extract message from typical Angular HttpErrorResponse (error.error.message, error.error.error, or error.message)
        const errorMessage = error.error?.message || error.error?.error || error.message || 'Error al crear voluntario';
        this.notificationService.showError('Error: ' + errorMessage);
      },
    });
  }
}
