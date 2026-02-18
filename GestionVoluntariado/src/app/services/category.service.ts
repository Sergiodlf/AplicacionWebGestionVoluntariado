import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { tap } from 'rxjs/operators';
import { Category } from '../models/Category';
import { ODS } from '../models/ODS';

@Injectable({
    providedIn: 'root'
})
export class CategoryService {
    private apiUrl = '/api/categories';
    private odsSubject = new BehaviorSubject<ODS[] | null>(null);
    private habilidadesSubject = new BehaviorSubject<Category[] | null>(null);
    private interesesSubject = new BehaviorSubject<Category[] | null>(null);
    private necesidadesSubject = new BehaviorSubject<Category[] | null>(null);

    constructor(private http: HttpClient) { }

    getODS(forceReload = false): Observable<ODS[]> {
        if (this.odsSubject.value && !forceReload) return of(this.odsSubject.value);
        return this.http.get<ODS[]>(`${this.apiUrl}?type=ods`).pipe(
            tap(data => this.odsSubject.next(data))
        );
    }

    getHabilidades(forceReload = false): Observable<Category[]> {
        if (this.habilidadesSubject.value && !forceReload) return of(this.habilidadesSubject.value);
        return this.http.get<Category[]>(`${this.apiUrl}?type=habilidades`).pipe(
            tap(data => this.habilidadesSubject.next(data))
        );
    }

    getIntereses(forceReload = false): Observable<Category[]> {
        if (this.interesesSubject.value && !forceReload) return of(this.interesesSubject.value);
        return this.http.get<Category[]>(`${this.apiUrl}?type=intereses`).pipe(
            tap(data => this.interesesSubject.next(data))
        );
    }

    getNecesidades(forceReload = false): Observable<Category[]> {
        if (this.necesidadesSubject.value && !forceReload) return of(this.necesidadesSubject.value);
        return this.http.get<Category[]>(`${this.apiUrl}?type=necesidades`).pipe(
            tap(data => this.necesidadesSubject.next(data))
        );
    }

    addHabilidad(nombre: string): Observable<any> {
        return this.http.post(`${this.apiUrl}/habilidades`, { nombre });
    }

    deleteHabilidad(id: number): Observable<any> {
        return this.http.delete(`${this.apiUrl}/habilidades/${id}`);
    }

    addInteres(nombre: string): Observable<any> {
        return this.http.post(`${this.apiUrl}/intereses`, { nombre });
    }

    deleteInteres(id: number): Observable<any> {
        return this.http.delete(`${this.apiUrl}/intereses/${id}`);
    }
}
