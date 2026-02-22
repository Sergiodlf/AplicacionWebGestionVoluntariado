import { Component, Input, Output, EventEmitter, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Organization } from '../../../../models/organizationModel'; // Asegúrate de ajustar la ruta
import { OrganizationService } from '../../../../services/organization.service'; // Asegúrate de ajustar la ruta
import { NotificationService } from '../../../../services/notification.service';

@Component({
  selector: 'app-organization-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './organization-card.component.html',
  styleUrl: './organization-card.component.css'
})
export class OrganizationCardComponent {

  constructor(
    private organizationService: OrganizationService,
    private notificationService: NotificationService
  ) { }

  @Input({ required: true }) organization!: Organization;

  /**
   * Mapea el sector (string) a un ícono de Bootstrap contextual.
   */
  get iconClass(): string {
    switch (this.organization.sector?.toLowerCase()) {
      case 'educación':
        return 'bi-mortarboard';
      case 'salud':
      case 'sanitario':
        return 'bi-heart-pulse';
      case 'medio ambiente':
        return 'bi-tree';
      case 'comunitario':
        return 'bi-people';
      default:
        return 'bi-building';
    }
  }

  @Output() onActivityClick = new EventEmitter<any>();
  @Output() onEdit = new EventEmitter<void>();
  @Output() onChangePassword = new EventEmitter<void>();

  onAcceptClick(): void {
    // EL HIJO llama al servicio para ACEPTAR
    this.organizationService.acceptOrganization(this.organization.cif).subscribe({
      next: () => {
        // CRÍTICO: El hijo notifica al sistema que recargue la lista
        this.notificationService.showSuccess('Organización aceptada correctamente.');
        this.organizationService.notifyOrganizationUpdate();
      },
      error: (err) => {
        console.error('Error al aceptar organización:', err);
        const backendMsg = err.error?.message || err.error?.msg || err.error?.error || err.error || err.message || 'Inténtalo de nuevo.';
        this.notificationService.showError('Error al aceptar organización: ' + backendMsg);
      }
    });
  }

  onRejectClick(): void {
    // EL HIJO llama al servicio para RECHAZAR
    this.organizationService.rejectOrganization(this.organization.cif).subscribe({
      next: () => {
        // CRÍTICO: El hijo notifica al sistema que recargue la lista
        this.notificationService.showSuccess('Organización rechazada correctamente.');
        this.organizationService.notifyOrganizationUpdate();
      },
      error: (err) => {
        console.error('Error al rechazar organización:', err);
        const backendMsg = err.error?.message || err.error?.msg || err.error?.error || err.error || err.message || 'Inténtalo de nuevo.';
        this.notificationService.showError('Error al rechazar organización: ' + backendMsg);
      }
    });
  }
}
