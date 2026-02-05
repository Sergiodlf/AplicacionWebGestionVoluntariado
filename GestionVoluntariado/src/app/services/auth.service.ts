import { Injectable, inject } from '@angular/core';
import { Auth, signInWithEmailAndPassword, createUserWithEmailAndPassword, signOut, User, authState, sendEmailVerification } from '@angular/fire/auth';
import { Firestore, doc, setDoc } from '@angular/fire/firestore';
import { Observable, BehaviorSubject } from 'rxjs';
import { tap } from 'rxjs/operators';
import { HttpClient } from '@angular/common/http';
import { ProfileResponse } from '../models/profile.model';
import { VoluntariadoService } from './voluntariado-service';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  isRegistrationInProgress = false;
  private auth: Auth = inject(Auth);
  private firestore: Firestore = inject(Firestore);
  readonly user$: Observable<User | null> = authState(this.auth);

  private userProfileSubject = new BehaviorSubject<ProfileResponse | null>(null);
  userProfile$ = this.userProfileSubject.asObservable();

  private voluntariadoService = inject(VoluntariadoService);

  constructor(private http: HttpClient) { }

  login(email: string, pass: string): Promise<void> {
    return signInWithEmailAndPassword(this.auth, email, pass)
      .then(() => {
        console.log('Login successful');
      })
      .catch((error) => {
        console.error('Login failed', error);
        throw error;
      });
  }

  register(email: string, pass: string): Promise<void> {
    return createUserWithEmailAndPassword(this.auth, email, pass)
      .then((userCredential) => {
        console.log('Registration successful');
        return sendEmailVerification(userCredential.user);
      })
      .catch((error) => {
        console.error('Registration failed', error);
        throw error;
      });
  }

  async saveUserRole(uid: string, email: string, role: string): Promise<void> {
    const userDocRef = doc(this.firestore, `usuarios/${uid}`);
    try {
      await setDoc(userDocRef, {
        email: email,
        rol: role
      });
      console.log('User role saved to Firestore');
    } catch (error) {
      console.error('Error saving user role to Firestore:', error);
      throw error;
    }
  }

  logout(): Promise<void> {
    this.userProfileSubject.next(null);
    this.voluntariadoService.clearState();
    localStorage.removeItem('user_id');
    localStorage.removeItem('user_name');
    localStorage.removeItem('user_role'); // Clean up any other potential keys
    localStorage.removeItem('user_token'); // If stored manually
    return signOut(this.auth);
  }

  getCurrentUser(): User | null {
    return this.auth.currentUser;
  }

  private profileRequest$: Observable<ProfileResponse> | null = null;

  getProfile(): Observable<ProfileResponse> {
    return this.http.get<ProfileResponse>('/api/auth/profile');
  }

  loadProfile(): Observable<ProfileResponse> {
    // Deduplicate concurrent requests
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

        // Save email specifically for legacy components
        if (profile.datos.email) {
          localStorage.setItem('user_email', profile.datos.email);
        }

        // Handle ID ambiguity for legacy support
        if (profile.tipo === 'organizacion' && profile.datos.cif) {
          localStorage.setItem('user_cif', profile.datos.cif);
        } else if (profile.tipo === 'voluntario' && profile.datos.dni) {
          localStorage.setItem('user_dni', profile.datos.dni);
        }
      }),
      // Clear the in-flight request on completion (success or error)
      tap({
        complete: () => this.profileRequest$ = null,
        error: () => this.profileRequest$ = null
      })
    );

    return this.profileRequest$;
  }

  updateProfile(data: any): Observable<any> {
    return this.http.put('/api/auth/profile', data).pipe(
      tap(() => {
        // Optimistic update or reload
        const current = this.userProfileSubject.value;
        if (current) {
          // We could merge data, but safe to reload or partially update
          // For now, let's trigger a reload to get fresh formatted data from backend
          this.loadProfile().subscribe();
        }
      })
    );
  }

  getCurrentProfile(): ProfileResponse | null {
    return this.userProfileSubject.value;
  }

  changePassword(oldPass: string, newPass: string): Observable<any> {
    return this.http.post('/api/auth/changePassword', { oldPassword: oldPass, newPassword: newPass });
  }
}
