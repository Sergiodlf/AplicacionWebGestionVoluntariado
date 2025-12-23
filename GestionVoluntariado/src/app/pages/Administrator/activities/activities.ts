import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../../components/Global-Components/navbar/navbar.component';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { FormsModule } from '@angular/forms';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { VolunteeringCardComponent } from '../../../components/Administrator/Volunteers/volunteering-card/volunteering-card.component';
import { ActividadService } from '../../../services/actividad';

@Component({
  selector: 'app-activities',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    StatusToggleComponent,
    VolunteeringCardComponent,
    NavbarComponent,
    SidebarComponent
  ],
  templateUrl: './activities.html',
  styleUrl: './activities.css'
})
export class ActivitiesComponent implements OnInit {
  private actividadService = inject(ActividadService);
  activeTab: 'left' | 'right' = 'left';

  volunteeringOpportunities: any[] = [];
  filteredOpportunities: any[] = [];

  // Filter State
  showFilterModal = false;
  filters = {
    date: '',
    skill: ''
  };

  ngOnInit() {
    console.log('ActivitiesComponent: calling getActividades()...');
    this.actividadService.getActividades().subscribe({
      next: (data) => {
        console.log('ActivitiesComponent: Data received from API:', data);
        this.volunteeringOpportunities = data.map(item => ({
          ...item,
          title: item.nombre,
          organization: 'Organización (API)',
          skills: item.habilidades
            ? (Array.isArray(item.habilidades) ? item.habilidades : item.habilidades.split(',').map((s: string) => s.trim()))
            : ['General'],
          date: new Date(item.fechaInicio).toLocaleDateString(),
          ods: Array.isArray(item.ods)
            ? item.ods.map((o: any) => {
              if (typeof o === 'object' && o !== null) {
                return {
                  id: o.id || 0,
                  name: o.name || o.nombre || 'ODS',
                  color: o.color || '#00c851'
                };
              } else {
                return {
                  id: 0,
                  name: `ODS ${o}`,
                  color: '#00c851'
                };
              }
            })
            : (typeof item.ods === 'string' ? [{ id: 0, name: item.ods, color: '#00c851' }] : [{ id: 4, name: 'ODS 4', color: '#00c851' }])
        }));
        this.filteredOpportunities = [...this.volunteeringOpportunities];
        console.log('ActivitiesComponent: Mapped opportunities:', this.volunteeringOpportunities);
      },
      error: (err) => {
        console.error('ActivitiesComponent: Error fetching activities:', err);
      }
    });
  }

  onTabChange(tab: 'left' | 'right') {
    this.activeTab = tab;
  }

  // Filter Methods
  openFilterModal() {
    this.showFilterModal = true;
  }

  closeFilterModal() {
    this.showFilterModal = false;
  }

  applyFilters() {
    console.log('Applying filters:', this.filters);
    this.filteredOpportunities = this.volunteeringOpportunities.filter(item => {
      let matchesDate = true;
      let matchesSkill = true;

      if (this.filters.date) {
        const itemDate = new Date(item.fechaInicio).toDateString();
        const filterDate = new Date(this.filters.date).toDateString();
        matchesDate = itemDate === filterDate;
      }

      if (this.filters.skill) {
        const searchTerm = this.filters.skill.toLowerCase();
        matchesSkill = item.title.toLowerCase().includes(searchTerm) ||
          (item.skills && item.skills.some((s: string) => s.toLowerCase().includes(searchTerm)));
      }

      return matchesDate && matchesSkill;
    });
    this.closeFilterModal();
  }

  resetFilters() {
    this.filters = { date: '', skill: '' };
    this.filteredOpportunities = [...this.volunteeringOpportunities];
    this.closeFilterModal();
  }

  onAction(item: any) {
    console.log('Action clicked for:', item.title);
  }

  showInfoModal = false;
  selectedVolunteering: any = null;

  openInfoModal(item: any) {
    this.selectedVolunteering = item;
    this.showInfoModal = true;
  }

  closeInfoModal() {
    this.showInfoModal = false;
    this.selectedVolunteering = null;
  }
}
