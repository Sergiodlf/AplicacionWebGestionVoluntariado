import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, Subject, BehaviorSubject, of } from 'rxjs';
import { tap, map } from 'rxjs/operators';
import { Organization, OrganizationCreateData } from '../models/organizationModel';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root',
})
export class OrganizationService {
  private apiUrl = `${environment.apiUrl}/organizations`;

  // 1. Subject para notificar cambios en la lista de organizaciones
  private organizationUpdatedSource = new Subject<void>();

  private organizationsSubject = new BehaviorSubject<Organization[] | null>(null);
  organizations$ = this.organizationsSubject.asObservable();

  // 2. Observable público para que otros componentes se suscriban
  organizationUpdated$ = this.organizationUpdatedSource.asObservable();

  constructor(private http: HttpClient) { }

  getOrganizations(forceReload: boolean = false): Observable<Organization[]> {
    if (this.organizationsSubject.value && !forceReload) {
      return of(this.organizationsSubject.value);
    }
    return this.loadOrganizations();
  }

  loadOrganizations(): Observable<Organization[]> {
    return this.http.get<Organization[]>(this.apiUrl).pipe(
      tap(data => this.organizationsSubject.next(data))
    );
  }

  updateOrganizationStatus(cif: string, newStatus: string): Observable<any> {
    const url = `${this.apiUrl}/${cif}`; // PATCH /api/organizations/{cif}
    return this.http.patch(url, { estado: newStatus });
  }

  addOrganization(data: OrganizationCreateData): Observable<Organization> {
    return this.http.post<Organization>(this.apiUrl, data);
  }

  createOrganization(organization: any): Observable<any> {
    return this.http.post(`${environment.apiUrl}/auth/register/organizacion`, organization);
  }

  removeOrganization(cif: string): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${cif}`);
  }

  /**
   * Llama a la API para aceptar (aprobar) una organización.
   * @param cif CIF de la organización a aprobar.
   */
  acceptOrganization(cif: string): Observable<any> {
    const updateData = { estado: 'aprobado' };
    return this.http.patch(`${this.apiUrl}/${cif}`, updateData);
  }

  /**
   * Llama a la API para rechazar una organización.
   * @param cif CIF de la organización a rechazar.
   */
  rejectOrganization(cif: string): Observable<any> {
    // 4. Lógica para llamar a la API (ej: DELETE o PATCH para cambiar estado a Rechazado)
    // Si quieres eliminar la organización:
    // return this.http.delete(`${this.apiUrl}/${cif}`);

    // O si quieres cambiar el estado a "Rechazado":
    const updateData = { estado: 'rechazado' };
    return this.http.patch(`${this.apiUrl}/${cif}`, updateData);
  }

  /**
   * Llama a este método después de cualquier modificación (Aceptar/Rechazar/Añadir)
   * para notificar a todos los suscriptores (ej. OrganizationsComponent) que deben refrescarse.
   */
  notifyOrganizationUpdate(): void {
    this.organizationUpdatedSource.next();
  }

  updateProfile(cif: string, data: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/${cif}`, data);
  }

  getOrganizationByEmail(email: string): Observable<Organization> {
    // GET /api/organizations/by-email?email=...
    return this.http.get<Organization>(`${this.apiUrl}/by-email`, { params: { email } });
  }

  getProfile(): Observable<Organization> {
    return this.http.get<any>(`${environment.apiUrl}/auth/profile`).pipe(
      tap(response => console.log('Organization Profile:', response)),
      map(response => response.datos as Organization)
    );
  }
}
