import { Injectable, inject } from '@angular/core';
import { Auth, signInWithEmailAndPassword, createUserWithEmailAndPassword, signOut, User, authState, sendEmailVerification } from '@angular/fire/auth';
import { Firestore, doc, setDoc } from '@angular/fire/firestore';
import { Observable } from 'rxjs';

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

  constructor() {}

  login(email: string, pass: string): Promise<void> {
    return signInWithEmailAndPassword(this.auth, email, pass)
      .then(() => {
        // Login successful
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
    return signOut(this.auth);
  }

  getCurrentUser(): User | null {
    return this.auth.currentUser;
  }
}
