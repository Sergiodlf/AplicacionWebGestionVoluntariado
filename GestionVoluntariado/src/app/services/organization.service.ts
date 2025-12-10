import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http'; 
import { Observable } from 'rxjs'; 
import { Organization, OrganizationCreateData } from '../models/organizationModel'; 


@Injectable({
  providedIn: 'root'
})
export class OrganizationService {
  
  private apiUrl = 'http://localhost:8000/api/organizations'; 

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
}