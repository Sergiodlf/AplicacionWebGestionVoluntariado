import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { OrganizationService } from '../../../services/organization.service';

@Component({
  selector: 'app-organizations',
  standalone: true,
  imports: [CommonModule, StatusToggleComponent, FormsModule],
  templateUrl: './organizations.html',
  styleUrl: './organizations.css'
})
export class OrganizationsComponent implements OnInit {
  private organizationService = inject(OrganizationService);
  activeTab: 'left' | 'right' = 'left'; // 'left' = Pending (Pendientes), 'right' = Approved (Aprobados)

  organizations: any[] = [];
  filteredOrganizations: any[] = [];

  ngOnInit() {
    console.log('OnInit: Loading initial data...');
    this.loadData();
  }

  loadData() {
    console.log('loadData: Fetching organizations...');
    this.organizationService.getOrganizations().subscribe({
      next: (data) => {
        console.log('loadData: Data received', data);
        this.organizations = data.map(item => ({
          name: item.nombre,
          email: item.email || 'N/A',
          cif: item.cif, // Keep original CIF for API calls
          description: item.descripcion || 'Sin descripción disponible',
          // Use first activity date as proxy or hide if none
          date: item.actividades && item.actividades.length > 0 ? item.actividades[0].fechaInicio : null,
          location: item.localidad || item.direccion || 'Ubicación desconocida',
          // Show sector and count of activities
          type: item.sector || 'Organización',
          activitiesCount: item.actividades ? item.actividades.length : 0,
          // Calculate total max participants across all activities
          maxParticipants: item.actividades ? item.actividades.reduce((sum: number, act: any) => sum + (act.maxParticipantes || 0), 0) : 0,
          ods: [], // ODS not at top level, could aggregate from activities if needed
          // Map status from API: PENDIENTE -> pending, APROBADA -> approved, RECHAZADA -> rejected
          // Default to 'pending' only if unknown and not rejected.
          status: this.mapStatus(item.estado)
        }));
        console.log('loadData: Mapped organizations', this.organizations);
        this.filterOrganizations();
      },
      error: (err) => console.error('Error fetching organizations:', err)
    });
  }

  private mapStatus(estado: string): string {
    if (!estado) return 'approved'; // Default if missing for now
    switch (estado.toUpperCase()) {
      case 'PENDIENTE': return 'pending';
      case 'APROBADO': // New masculine form
      case 'APROBADA': return 'approved';
      case 'RECHAZADO': // New masculine form
      case 'RECHAZADA': return 'rejected';
      default: return 'pending';
    }
  }

  onTabChange(tab: 'left' | 'right') {
    console.log('Tab changed to:', tab);
    this.activeTab = tab;
    this.filterOrganizations();
  }

  filterOrganizations() {
    const statusFilter = this.activeTab === 'left' ? 'pending' : 'approved';
    console.log(`Filtering for status: ${statusFilter}`);
    this.filteredOrganizations = this.organizations.filter(org => org.status === statusFilter);
    console.log('Filtered count:', this.filteredOrganizations.length);
  }

  onAccept(org: any) {
    console.log('onAccept clicked for:', org.name, org.cif);
    if (!org.cif) {
      alert('Error: CIF no encontrado para esta organización.');
      return;
    }
    this.organizationService.updateOrganizationStatus(org.cif, 'aprobado').subscribe({
      next: () => {
        console.log('Update success (aprobado), reloading data...');
        this.loadData();
      },
      error: (err) => {
        console.error('Error approving organization:', err);
        alert('Error al aprobar la organización. Consulta la consola para más detalles.');
      }
    });
  }

  onReject(org: any) {
    console.log('onReject clicked for:', org.name, org.cif);
    if (!org.cif) {
      alert('Error: CIF no encontrado para esta organización.');
      return;
    }
    this.organizationService.updateOrganizationStatus(org.cif, 'rechazado').subscribe({
      next: () => {
        console.log('Update success (rechazado), reloading data...');
        this.loadData();
      },
      error: (err) => {
        console.error('Error rejecting organization:', err);
        alert('Error al rechazar la organización. Consulta la consola para más detalles.');
      }
    });
  }

  // Modal logic
  showModal = false;
  newOrganization: any = {
    nombre: '',
    cif: '',
    email: '',
    password: '',
    sector: '',
    direccion: '',
    localidad: '',
    cp: '',
    descripcion: '',
    contacto: ''
  };

  openModal() {
    this.showModal = true;
  }

  closeModal() {
    this.showModal = false;
    // Reset form
    this.newOrganization = {
      nombre: '',
      cif: '',
      email: '',
      password: '',
      sector: '',
      direccion: '',
      localidad: '',
      cp: '',
      descripcion: '',
      contacto: ''
    };
  }

  submitNewOrganization() {
    console.log('submitNewOrganization called. Current data:', this.newOrganization);
    // Basic validation check
    const values = Object.values(this.newOrganization);
    if (values.some(val => val === '' || val === null || val === undefined)) {
      console.warn('Validation failed: Some fields are empty', this.newOrganization);
      alert('Por favor, rellena todos los campos.');
      return;
    }

    this.organizationService.createOrganization(this.newOrganization).subscribe({
      next: (res: any) => {
        console.log('Organization created successfully:', res);
        alert('Organización creada correctamente');
        this.closeModal();
        this.loadData();
      },
      error: (err: any) => {
        console.error('Error creating organization:', err);
        alert('Error al crear la organización: ' + (err.message || err.statusText || 'Error desconocido'));
      }
    });
  }
}
