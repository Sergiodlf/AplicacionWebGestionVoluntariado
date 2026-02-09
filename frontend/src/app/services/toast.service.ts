import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { Toast } from '../models/Toats';

@Injectable({
    providedIn: 'root'
})
export class ToastService {
    private toastsSubject = new BehaviorSubject<Toast[]>([]);
    toasts$ = this.toastsSubject.asObservable();

    show(message: string, type: 'success' | 'error' | 'info' | 'warning' = 'info') {
        const currentToasts = this.toastsSubject.value;
        const toast: Toast = { message, type, id: Date.now() };
        this.toastsSubject.next([...currentToasts, toast]);

        // Auto-remove after 3 seconds
        setTimeout(() => this.remove(toast.id!), 4000);
    }

    remove(id: number) {
        const currentToasts = this.toastsSubject.value;
        this.toastsSubject.next(currentToasts.filter(t => t.id !== id));
    }
}
