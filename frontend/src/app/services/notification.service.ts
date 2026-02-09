import { Injectable } from '@angular/core';
import Swal from 'sweetalert2';

@Injectable({
    providedIn: 'root'
})
export class NotificationService {

    constructor() { }

    /**
     * Shows a success toast notification (top-end)
     */
    showSuccess(message: string) {
        Swal.fire({
            icon: 'success',
            title: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    /**
     * Shows an error toast notification (top-end)
     */
    showError(message: string) {
        Swal.fire({
            icon: 'error',
            title: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
        });
    }

    /**
     * Shows an info toast notification (top-end)
     */
    showInfo(message: string) {
        Swal.fire({
            icon: 'info',
            title: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    /**
     * Shows a confirmation modal. Returns true if confirmed.
     */
    async showConfirmation(title: string, text: string, confirmButtonText: string = 'Sí, continuar'): Promise<boolean> {
        const result = await Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Cancelar'
        });

        return result.isConfirmed;
    }

    /**
     * Shows a warning toast notification (top-end)
     */
    showWarning(message: string) {
        Swal.fire({
            icon: 'warning',
            title: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
        });
    }

    /**
     * Shows a standard centered popup for important success messages that require user acknowledgment
     */
    showSuccessPopup(title: string, message: string) {
        return Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            confirmButtonColor: '#3085d6'
        });
    }
}
