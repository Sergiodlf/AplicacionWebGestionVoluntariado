import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http'; 
import { Observable, Subject } from 'rxjs'; 
import { Organization, OrganizationCreateData } from '../models/organizationModel'; 


@Injectable({
  providedIn: 'root'
})
export class OrganizationService {
  
  private apiUrl = 'http://localhost:8000/api/organizations';
  // 1. Subject para notificar cambios en la lista de organizaciones
   private organizationUpdatedSource = new Subject<void>(); 
   
   // 2. Observable público para que otros componentes se suscriban
   organizationUpdated$ = this.organizationUpdatedSource.asObservable();

  constructor(private http: HttpClient) { }

  /**
   * Obtiene la lista completa de organizaciones desde la API de Symfony.
   * @returns Un Observable de un array de objetos Organization.
   */
  getOrganizations(): Observable<Organization[]> {
    return this.http.get<Organization[]>(this.apiUrl);
  }

  /**
   * Registra una nueva organización enviando todos los datos, incluyendo la contraseña.
   * @param data Objeto OrganizationCreateData con todos los campos requeridos.
   * @returns Un Observable del objeto Organization creado.
   */
  addOrganization(data: OrganizationCreateData): Observable<Organization> {
    return this.http.post<Organization>(this.apiUrl, data);
  }
  /**
   * Elimina una organización por su CIF.
   * @param cif El Código de Identificación Fiscal de la organización.
   * @returns Un Observable que completa la petición (la respuesta suele ser vacía o un status 204).
   */
  removeOrganization(cif: string): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${cif}`);
  }

  /**
    * Llama a la API para aceptar (aprobar) una organización.
    * @param cif CIF de la organización a aprobar.
    */
   acceptOrganization(cif: string): Observable<any> {
      // 3. Lógica para llamar a la API (ej: PATCH para cambiar el estado)
      const updateData = { estado: 'Aprobado' };
      // Asumo que la API tiene un endpoint para PATCH por CIF: /api/organizations/{cif}
      return this.http.patch(`${this.apiUrl}/${cif}`, updateData);
   }

   /**
    * Llama a la API para rechazar una organización.
    * @param cif CIF de la organización a rechazar.
    */
   rejectOrganization(cif: string): Observable<any> {
      // 4. Lógica para llamar a la API (ej: DELETE o PATCH para cambiar estado a Rechazado)
      // Si quieres eliminar la organización:
      return this.http.delete(`${this.apiUrl}/${cif}`);
      
      // O si quieres cambiar el estado a "Rechazado":
      /* const updateData = { estado: 'Rechazado' };
      return this.http.patch(`${this.apiUrl}/${cif}`, updateData);
      */
   }
   
   /**
    * Llama a este método después de cualquier modificación (Aceptar/Rechazar/Añadir)
    * para notificar a todos los suscriptores (ej. OrganizationsComponent) que deben refrescarse.
    */
   notifyOrganizationUpdate(): void {
      this.organizationUpdatedSource.next();
   }
}