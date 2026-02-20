import { Injectable, inject } from '@angular/core';
import { forkJoin, BehaviorSubject, Observable, of } from 'rxjs';
import { map, tap } from 'rxjs/operators';
import { VolunteerService } from './volunteer.service';
import { OrganizationService } from './organization.service';
import { VoluntariadoService } from './voluntariado-service';
import { CategoryService } from './category.service';
import { DashboardMetrics } from '../models/DashboardMetrics';

@Injectable({
    providedIn: 'root'
})
export class DashboardService {
    private volunteerService = inject(VolunteerService);
    private organizationService = inject(OrganizationService);
    private voluntariadoService = inject(VoluntariadoService);
    private categoryService = inject(CategoryService);

    private metricsSubject = new BehaviorSubject<DashboardMetrics | null>(null);
    metrics$ = this.metricsSubject.asObservable();

    private _isFirstLoad = true;

    get isFirstLoad(): boolean {
        return this._isFirstLoad;
    }

    loadDashboardData(): Observable<DashboardMetrics> {
        if (this.metricsSubject.value) {
            return of(this.metricsSubject.value);
        }
        return forkJoin({
            volunteers: this.volunteerService.getVolunteers(),
            organizations: this.organizationService.getOrganizations(),
            matches: this.voluntariadoService.getAllInscripciones(),
            activities: this.voluntariadoService.getAllVoluntariados(false, { estadoAprobacion: 'ALL', history: true }),
            habilidades: this.categoryService.getHabilidades(),
            intereses: this.categoryService.getIntereses()
        }).pipe(
            map(result => {
                const metrics = this.calculateMetrics(result);
                this.metricsSubject.next(metrics);
                this._isFirstLoad = false;
                return metrics;
            })
        );
    }

    private calculateMetrics(result: any): DashboardMetrics {
        const normalize = (s: string) => s ? s.trim().toUpperCase() : '';

        // 1. Volunteers
        const volunteersTotal = result.volunteers.filter((v: any) => v.status === 'ACEPTADO').length;
        const volunteersPending = result.volunteers.filter((v: any) => v.status === 'PENDIENTE').length;

        // 2. Organizations
        const organizationsTotal = result.organizations.filter((o: any) => (o.estado?.trim().toLowerCase() || '') === 'aprobado').length;
        const organizationsPending = result.organizations.filter((o: any) => (o.estado?.trim().toLowerCase() || '') === 'pendiente').length;

        // 3. Matches
        const matches = result.matches;
        const matchesTotal = matches.length;
        const matchesPending = matches.filter((m: any) => m.estado === 'PENDIENTE').length;
        const matchesAccepted = matches.filter((m: any) => ['CONFIRMADA', 'ACEPTADA', 'ACEPTADO', 'CONFIRMADO'].includes(m.estado?.toUpperCase())).length;
        const matchesCompleted = matches.filter((m: any) => ['COMPLETADA', 'COMPLETADO'].includes(m.estado?.toUpperCase())).length;

        // 4. Activities
        const activities = result.activities;
        const activitiesActive = activities.filter((a: any) =>
            normalize(a.estadoAprobacion) === 'ACEPTADA' &&
            ['ABIERTA', 'EN CURSO', 'ACEPTADA'].includes(normalize(a.estado))
        ).length;

        const activitiesPending = activities.filter((a: any) =>
            normalize(a.estadoAprobacion) === 'PENDIENTE'
        ).length;

        const checkCompleted = (a: any) => {
            const rawStatus = normalize(a.estado);
            const rawApproval = normalize(a.estadoAprobacion);
            if (rawApproval !== 'ACEPTADA') return false; // Must be accepted first

            if (['CERRADA', 'FINALIZADA', 'COMPLETADA', 'COMPLETADO'].includes(rawStatus)) return true;

            // Fallback for dates
            if (a.fechaFin) {
                const end = new Date(a.fechaFin);
                if (new Date() > end) return true;
            }
            return false;
        };
        const activitiesCompleted = activities.filter(checkCompleted).length;

        // 5. Rates
        let acceptanceRate = 0;
        if (matchesTotal > 0) {
            acceptanceRate = Math.round((matchesAccepted / matchesTotal) * 100);
        }

        let completionRate = 0;
        if (matchesAccepted > 0) {
            completionRate = Math.round((matchesCompleted / matchesAccepted) * 100);
        }

        return {
            volunteersTotal, volunteersPending,
            organizationsTotal, organizationsPending,
            matchesTotal, matchesAccepted, matchesPending, matchesCompleted,
            activitiesActive, activitiesPending, activitiesCompleted,
            acceptanceRate, completionRate
        };
    }
}
