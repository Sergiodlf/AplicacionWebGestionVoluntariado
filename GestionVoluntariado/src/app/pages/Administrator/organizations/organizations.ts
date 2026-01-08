import { Component, OnDestroy, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { OrganizationCardComponent } from '../../../components/Administrator/Organizations/organization-card/organization-card.component';
import { OrganizationFormComponent } from '../../../components/Global-Components/organization-form/organization-form.component';
import { OrganizationService } from '../../../services/organization.service';
import { Organization } from '../../../models/organizationModel';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-organizations',
  standalone: true,
  imports: [
    CommonModule,
    Navbar,
    SidebarComponent,
    StatusToggleComponent,
    OrganizationCardComponent,
    OrganizationFormComponent,
  ],
  templateUrl: './organizations.html',
  styleUrl: './organizations.css',
})
export class OrganizationsComponent implements OnInit, OnDestroy {
  constructor(private organizationService: OrganizationService) { }
  activeTab: 'left' | 'middle' | 'right' = 'left'; // 'left' = Pending (Pendientes), 'middle' = Pending (compat), 'right' = Approved (Aprobados)

  organizations = signal<Organization[]>([]);
  private organizationsSubscription: Subscription | undefined;
  private updateSubscription: Subscription | undefined; // Suscripción para notificaciones del servicio
  currentTab: 'pending' | 'approved' = 'pending';
  showModal = signal(false);
  pendingCount = signal(0);
  approvedCount = signal(0);

  ngOnInit(): void {
    this.loadOrganizations();
    this.setupUpdateSubscription(); // Inicia la escucha de notificaciones
  }

  ngOnDestroy(): void {
    if (this.organizationsSubscription) {
      this.organizationsSubscription.unsubscribe();
    }
    if (this.updateSubscription) {
      this.updateSubscription.unsubscribe();
    }
  }

  /**
   * Se suscribe al Subject del servicio para recargar la lista si un hijo
   * llama a notifyOrganizationUpdate().
   */
  setupUpdateSubscription(): void {
    this.updateSubscription = this.organizationService.organizationUpdated$.subscribe(() => {
      this.loadOrganizations();
    });
  }

  loadOrganizations(): void {
    if (this.organizationsSubscription) {
      this.organizationsSubscription.unsubscribe();
    }
    this.organizationsSubscription = this.organizationService.getOrganizations().subscribe({
      next: (data) => {
        const allOrgs = data; // Filtrado y actualización de la lista
        this.filterAndSetOrganizations(allOrgs); // Actualizar contadores

        const pending = data.filter((org) => org.estado?.toLowerCase() === 'pendiente');
        const approved = data.filter((org) => org.estado?.toLowerCase() === 'aprobado');
        this.pendingCount.set(pending.length);
        this.approvedCount.set(approved.length);
      },
      error: (error) => {
        console.error('Error al obtener organizaciones de la API:', error);
      },
    });
  }
  filterAndSetOrganizations(allOrgs: Organization[]): void {
    const filtered = allOrgs.filter((org) => {
      const status = org.estado?.trim().toLowerCase();
      const target = this.currentTab === 'pending' ? 'pendiente' : 'aprobado';
      return status === target;
    });
    this.organizations.set(filtered);
  }

  // LÓGICA DE INTERFAZ Y ACCIONES //

  onTabChange(tab: 'left' | 'middle' | 'right') {
    console.log('Tab changed to:', tab);
    this.activeTab = tab;
    // Map status-toggle options to our currentTab ('pending' | 'approved')
    if (tab === 'right') {
      this.currentTab = 'approved';
    } else {
      // treat 'left' and 'middle' as pending for this page
      this.currentTab = 'pending';
    }
    this.loadOrganizations();
  }

  onAddOrganization(): void {
    this.showModal.set(true);
  }

  closeModal(): void {
    this.showModal.set(false);
  }

  onFormSubmit(data: Organization): void {
    console.log('Nueva organización añadida:', data);
    this.closeModal();
    this.organizationService.notifyOrganizationUpdate();
  }
}
