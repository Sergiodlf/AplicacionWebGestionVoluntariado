import {
  Component,
  EventEmitter,
  Output,
  Input,
  inject,
  OnInit,
  OnDestroy,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { VolunteerService } from '../../../../services/volunteer.service';
import { ActividadService } from '../../../../services/actividad';
import { VoluntariadoService } from '../../../../services/voluntariado-service';
import { NotificationService } from '../../../../services/notification.service';
import { forkJoin } from 'rxjs';

@Component({
  selector: 'app-create-match-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './create-match-modal.component.html',
  styleUrl: './create-match-modal.component.css',
})
export class CreateMatchModalComponent implements OnInit, OnDestroy {
  @Output() close = new EventEmitter<void>();
  @Output() matchCreated = new EventEmitter<void>();
  @Input() preselectedActivityId: number | null = null;
  @Input() preselectedVolunteer: any = null;

  private volunteerService = inject(VolunteerService);
  private actividadService = inject(ActividadService);
  private voluntariadoService = inject(VoluntariadoService);
  private notificationService = inject(NotificationService);

  volunteers: any[] = [];
  activities: any[] = [];

  selectedVolunteerDnI: string = '';
  selectedActivityId: number | null = null;
  isLoading = false;

  ngOnInit() {
    this.selectedActivityId = this.preselectedActivityId;
    if (this.preselectedVolunteer && this.preselectedVolunteer.dni) {
      this.selectedVolunteerDnI = this.preselectedVolunteer.dni;
    }
    this.loadData();
  }

  ngOnDestroy() {}

  loadData() {
    this.isLoading = true;
    forkJoin({
      volunteers: this.volunteerService.getVolunteers(),
      activities: this.actividadService.getActividades(),
    }).subscribe({
      next: (result: { volunteers: any[]; activities: any[] }) => {
        this.volunteers = (result.volunteers || []).filter((v: any) => {
          const st = String(
            v.status || v.estado_voluntario || '',
          )
            .toUpperCase()
            .trim();
          return st === 'ACEPTADO';
        });
        this.activities = result.activities.filter((a: any) => {
          const st = (a.estadoAprobacion || '').toUpperCase();
          // We allow Pending (execution), Open, Accepted or In-course.
          // Basically just not wanting Cancelled or Rejected (if those are states).
          return st === 'ACEPTADA';
        });

        if (this.selectedActivityId) {
          this.selectedActivityId = Number(this.selectedActivityId);
          this.sortVolunteers();
        }
        this.isLoading = false;
      },
      error: (err: any) => {
        console.error('Error loading data for modal', err);
        this.isLoading = false;
      },
    });
  }

  get selectedVolunteer() {
    return this.volunteers.find((v) => v.dni === this.selectedVolunteerDnI);
  }

  get selectedActivity() {
    return this.activities.find(
      (a) => (a.codActividad || a.id) == this.selectedActivityId,
    );
  }

  onSubmit() {
    if (!this.selectedVolunteerDnI || !this.selectedActivityId) {
      this.notificationService.showError(
        'Por favor selecciona un voluntario y una actividad',
      );
      return;
    }

    this.isLoading = true;
    this.voluntariadoService
      .inscribirVoluntario(this.selectedVolunteerDnI, this.selectedActivityId)
      .subscribe({
        next: () => {
          this.notificationService.showSuccess('Match creado con éxito');
          this.isLoading = false;
          this.matchCreated.emit();
          this.close.emit();
        },
        error: (err) => {
          console.error('Error creating match', err);
          const msg =
            err.error?.error || err.error?.message || 'Error desconocido';

          if (err.status === 409) {
            // Check specific message content if needed, or just show the backend message
            if (msg.includes('ya está inscrito')) {
              this.notificationService.showWarning(msg);
            } else {
              // "Activity complete" or "Max participants"
              this.notificationService.showError(msg);
            }
          } else {
            this.notificationService.showError(
              'Error al crear el match: ' + msg,
            );
          }
          this.isLoading = false;
        },
      });
  }
  // ... (existing methods)

  // Helper to parse potential JSON, CSV or Objects
  private parseList(value: any): any[] {
    if (!value) return [];
    if (Array.isArray(value)) return value;
    if (typeof value === 'string') {
      value = value.trim();
      if (value.startsWith('[') && value.endsWith(']')) {
        try {
          const parsed = JSON.parse(value);
          return Array.isArray(parsed) ? parsed : [value];
        } catch {} // ignore error
      }
      return value
        .split(',')
        .map((s: string) => s.trim())
        .filter((s: string) => s.length > 0);
    }
    return [value];
  }

  calculateMatchScore(volunteer: any, activity: any): number {
    if (!activity) return 0;
    let score = 0;

    // 1. SKILLS MATCH (40%)
    const activityNeeds = this.parseList(
      activity.habilidades || activity.necesidades,
    ); // Handle both names just in case
    const volunteerSkills = this.parseList(
      volunteer.skills || volunteer.habilidades,
    );

    if (activityNeeds.length > 0) {
      const matches = activityNeeds.filter((need) => {
        const needName = (need.nombre || need).toLowerCase();
        return volunteerSkills.some((skill) =>
          (skill.nombre || skill).toLowerCase().includes(needName),
        );
      });
      score += (matches.length / activityNeeds.length) * 40;
    } else {
      // If activity has no specific needs, give full points for this section? Or partial?
      // Let's give partial to avoid biasing too much against others.
      score += 20;
    }

    // 2. CYCLE MATCH (30%)
    // We check if the volunteer has a cycle assigned.
    // Ideally we would map cycles to activity types, but for now:
    // if volunteer has a cycle, we assume they are a qualified student/grad.
    if (volunteer.nombre_ciclo || volunteer.ciclo) {
      score += 30;
    }

    // 3. LOCATION MATCH (15%)
    // Comparing activity.direccion (or city if we had it) with volunteer.zona
    if (
      volunteer.zona &&
      activity.direccion &&
      (activity.direccion
        .toLowerCase()
        .includes(volunteer.zona.toLowerCase()) ||
        volunteer.zona.toLowerCase().includes('pamplona'))
    ) {
      // Default assumption for demo
      score += 15;
    }

    // 4. AVAILABILITY MATCH (15%)
    // Simple overlap check
    const activityDate = new Date(activity.fechaInicio).getDay(); // 0-6
    // This is rough. Let's just give points if they have availability listed.
    const volAvailability = this.parseList(volunteer.disponibilidad);
    if (volAvailability.length > 0) {
      score += 15;
    }

    return Math.min(Math.round(score), 100);
  }

  sortVolunteers() {
    if (!this.selectedActivityId) return;

    const activity = this.selectedActivity;
    if (!activity) return;

    this.volunteers.forEach((v) => {
      v.matchScore = this.calculateMatchScore(v, activity);
    });

    this.volunteers.sort((a, b) => b.matchScore - a.matchScore);

    // Auto-select top match if none selected? No, user choice better.
  }

  // Update sort when activity changes
  onActivityChange() {
    this.sortVolunteers();
  }
}
