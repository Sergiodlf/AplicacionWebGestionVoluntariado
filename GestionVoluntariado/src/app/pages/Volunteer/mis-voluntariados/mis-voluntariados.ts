import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarVolunteer } from '../../../components/Volunteer/sidebar-volunteer/sidebar-volunteer';
import { VoluntariadoCard } from '../../../components/Volunteer/voluntariado-card/voluntariado-card';
import { VoluntariadoService } from '../../../services/voluntariado-service';
import { StatusToggleVoluntariado } from "../../../components/Volunteer/status-toggle-voluntariado/status-toggle-voluntariado";

@Component({
  selector: 'app-mis-voluntariados',
  imports: [
    CommonModule,
    Navbar,
    SidebarVolunteer,
    VoluntariadoCard,
    StatusToggleVoluntariado
  ],
  templateUrl: './mis-voluntariados.html',
  styleUrl: './mis-voluntariados.css',
})
export class MisVoluntariados {
  activeTab: 'left' | 'middle' | 'right' = 'left';

  tabLabel = 'Pendientes';

  private voluntariadoService = inject(VoluntariadoService);
  currentDNI = '11111111A';

  volunteeringData: any[] = [];

  ngOnInit() {
    this.loadData('pendiente');
  }

  loadData(estado: string) {
    this.voluntariadoService.getActividadesAceptadas(this.currentDNI, estado).subscribe({
      next: (data) => {
        console.log(`API Response MisVoluntariados (${estado}):`, data);
        this.volunteeringData = data.map(v => ({
          title: v.nombre,
          organization: v.nombre_organizacion,
          skills: Array.isArray(v.habilidades) ? v.habilidades : [],
          date: v.fechaInicio,
          status: v.estado,
          ods: Array.isArray(v.ods) ? v.ods.map((o: string) => ({
            id: 0,
            name: o,
            color: this.getOdsColor(o)
          })) : []
        }));
      },
      error: (err) => {
        console.error('Error fetching mis voluntariados', err);
        this.volunteeringData = [];
      }
    });
  }

  getOdsColor(ods: string): string {
    const colors: { [key: string]: string } = {
      'ODS1': '#ff4444',
      'ODS2': '#ffbb33',
      'ODS3': '#00c851',
      'ODS4': '#33b5e5',
    };
    return colors[ods] || '#9e9e9e';
  }

  // Removed filteredVolunteering getter as we now load filtered data directly

  onTabChange(tab: 'left' | 'middle' | 'right') {
    this.activeTab = tab;

    if (tab === 'left') {
      this.tabLabel = 'Pendientes';
      this.loadData('pendiente');
    }
    if (tab === 'middle') {
      this.tabLabel = 'En Curso';
      this.loadData('en curso');
    }
    if (tab === 'right') {
      this.tabLabel = 'Completados';
      this.loadData('completada');
    }
  }

  onAction(item: any) {
    console.log('Action in Mis Voluntariados:', item.title);
  }
}
