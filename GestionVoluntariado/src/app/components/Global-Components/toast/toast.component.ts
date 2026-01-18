import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ToastService, Toast } from '../../../services/toast.service';
import { Observable } from 'rxjs';

@Component({
    selector: 'app-toast',
    standalone: true,
    imports: [CommonModule],
    templateUrl: './toast.component.html',
    styles: [`
    .toast-container { pointer-events: none; }
    .toast { pointer-events: auto; transition: all 0.3s ease; }
  `]
})
export class ToastComponent {
    toasts$: Observable<Toast[]>;

    constructor(private toastService: ToastService) {
        this.toasts$ = this.toastService.toasts$;
    }

    remove(id: number) {
        this.toastService.remove(id);
    }
}
