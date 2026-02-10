import { Injectable, inject } from '@angular/core';
import { Auth, signOut } from '@angular/fire/auth';
import { Firestore, doc, setDoc } from '@angular/fire/firestore';
import { Observable, BehaviorSubject, of } from 'rxjs';
import { tap, map, catchError } from 'rxjs/operators';
import { HttpClient } from '@angular/common/http';
import { ProfileResponse } from '../models/profile.model';
import { VoluntariadoService } from './voluntariado-service';
import { environment } from '../../environments/environment';

export interface LoginResponse {
  message: string;
  token: string;
  refreshToken: string;
  expiresIn: string;
  localId: string;
  email: string;
  emailVerified: boolean;
  rol: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  isRegistrationInProgress = false;
  private auth: Auth = inject(Auth); // Keep for signOut cleanup if needed
  private firestore: Firestore = inject(Firestore); // Keep for legacy saves if needed

  // User State
  private userRoleSubject = new BehaviorSubject<string | null>(localStorage.getItem('user_role'));
  private userProfileSubject = new BehaviorSubject<ProfileResponse | null>(null);

  // Public Observables
  userProfile$ = this.userProfileSubject.asObservable();
  userRole$ = this.userRoleSubject.asObservable();

  // Mocking user$ for compatibility (creates a partial User object based on state)
  // implementing a basic observable that emits if we have a token
  user$ = new BehaviorSubject<any>(this.hasToken() ? { emailVerified: true } : null);

  private voluntariadoService = inject(VoluntariadoService);

  constructor(private http: HttpClient) {
    // Initialize state if token exists
    if (this.hasToken()) {
      this.loadProfile().subscribe({
        error: () => this.logout() // Auto-logout if token is invalid/profile load fails
      });
    }
  }

  // --- API LOGIN ---
  login(email: string, pass: string): Promise<void> {
    return new Promise((resolve, reject) => {
      this.http.post<LoginResponse>(`${environment.apiUrl}/auth/login`, { email, password: pass })
        .pipe(
          tap(response => {
            console.log('API Login successful:', response);
            this.setSession(response);
          }),
          catchError(err => {
            console.error('API Login failed', err);
            throw err;
          })
        )
        .subscribe({
          next: () => resolve(),
          error: (err) => reject(err)
        });
    });
  }

  private setSession(authResult: LoginResponse) {
    localStorage.setItem('user_token', authResult.token);
    localStorage.setItem('user_role', authResult.rol);
    localStorage.setItem('user_email', authResult.email);

    // Update State
    this.userRoleSubject.next(authResult.rol);
    // Emulate Firebase User object for components checking emailVerified
    this.user$.next({
      email: authResult.email,
      emailVerified: authResult.emailVerified,
      uid: authResult.localId
    });
  }

  hasToken(): boolean {
    return !!localStorage.getItem('user_token');
  }

  getToken(): string | null {
    return localStorage.getItem('user_token');
  }

  // Keeping register for compatibility, though components use Backend Services directly now
  register(email: string, pass: string): Promise<void> {
    // Logic moved to components/services utilizing backend endpoints
    return Promise.reject('Use specific register methods.');
  }

  async saveUserRole(uid: string, email: string, role: string): Promise<void> {
    // Legacy support or if we still write to firestore for some reason
    const userDocRef = doc(this.firestore, `usuarios/${uid}`);
    try {
      await setDoc(userDocRef, {
        email: email,
        rol: role
      });
    } catch (error) {
      console.error('Error saving user role to Firestore:', error);
    }
  }

  logout(): Promise<void> {
    this.userProfileSubject.next(null);
    this.userRoleSubject.next(null);
    this.user$.next(null);

    this.voluntariadoService.clearState();

    localStorage.removeItem('user_id');
    localStorage.removeItem('user_name');
    localStorage.removeItem('user_role');
    localStorage.removeItem('user_token');
    localStorage.removeItem('user_email');
    localStorage.removeItem('user_dni');
    localStorage.removeItem('user_cif');

    return signOut(this.auth); // Cleanup Firebase SDK state too
  }

  // Deprecated: verify where this is used. 
  // Components should generally subscribe to user$ or userProfile$
  getCurrentUser(): any {
    return this.user$.value;
  }

  private profileRequest$: Observable<ProfileResponse> | null = null;

  getProfile(): Observable<ProfileResponse> {
    return this.http.get<ProfileResponse>(`${environment.apiUrl}/auth/profile`);
  }

  loadProfile(): Observable<ProfileResponse> {
    if (this.profileRequest$) {
      return this.profileRequest$;
    }

    this.profileRequest$ = this.getProfile().pipe(
      tap(profile => {
        console.log('Profile loaded:', profile);
        this.userProfileSubject.next(profile);

        // Persist essential session data
        localStorage.setItem('user_role', profile.tipo);
        if (profile.datos.nombre) {
          localStorage.setItem('user_name', profile.datos.nombre);
        }

        // Handle ID ambiguity for legacy support
        if (profile.tipo === 'organizacion' && profile.datos.cif) {
          localStorage.setItem('user_cif', profile.datos.cif);
        } else if (profile.tipo === 'voluntario' && profile.datos.dni) {
          localStorage.setItem('user_dni', profile.datos.dni);
        }
      }),
      tap({
        complete: () => this.profileRequest$ = null,
        error: () => this.profileRequest$ = null
      })
    );

    return this.profileRequest$;
  }

  updateProfile(data: any): Observable<any> {
    return this.http.put(`${environment.apiUrl}/auth/profile`, data).pipe(
      tap(() => {
        const current = this.userProfileSubject.value;
        if (current) {
          this.loadProfile().subscribe();
        }
      })
    );
  }

  getCurrentProfile(): ProfileResponse | null {
    return this.userProfileSubject.value;
  }

  changePassword(oldPass: string, newPass: string): Observable<any> {
    return this.http.post(`${environment.apiUrl}/auth/changePassword`, { oldPassword: oldPass, newPassword: newPass });
  }
}
