import { Injectable, inject } from '@angular/core';
import { Auth, signInWithEmailAndPassword, createUserWithEmailAndPassword, signOut, User, authState, sendEmailVerification } from '@angular/fire/auth';
import { Firestore, doc, setDoc } from '@angular/fire/firestore';
import { Observable, BehaviorSubject } from 'rxjs';
import { tap } from 'rxjs/operators';
import { HttpClient } from '@angular/common/http';
import { ProfileResponse } from '../models/profile.model';

@Injectable({
  providedIn: 'root'
})
@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private auth: Auth = inject(Auth);
  private firestore: Firestore = inject(Firestore);
  readonly user$: Observable<User | null> = authState(this.auth);

  private userProfileSubject = new BehaviorSubject<ProfileResponse | null>(null);
  userProfile$ = this.userProfileSubject.asObservable();

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
    return signOut(this.auth);
  }

  getCurrentUser(): User | null {
    return this.auth.currentUser;
  }

  getProfile(): Observable<ProfileResponse> {
    return this.http.get<ProfileResponse>('/api/auth/profile');
  }

  loadProfile(): Observable<ProfileResponse> {
    return this.getProfile().pipe(
      tap(profile => {
        console.log('Profile loaded:', profile);
        this.userProfileSubject.next(profile);
      })
    );
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
}
