import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
    providedIn: 'root'
})
export class OrganizationService {

    private apiUrl = '/api/organizations';

    constructor(private http: HttpClient) { }

    getOrganizations(): Observable<any[]> {
        return this.http.get<any[]>(this.apiUrl);
    }

    updateOrganizationStatus(cif: string, newStatus: string): Observable<any> {
        const url = `${this.apiUrl}/${cif}/state`;
        return this.http.patch(url, { estado: newStatus });
    }

    createOrganization(org: any): Observable<any> {
        return this.http.post('/api/organizations', org);
    }
}
