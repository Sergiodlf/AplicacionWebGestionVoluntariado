import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { MatchCardComponent } from '../../../components/Administrator/Matches/match-card/match-card.component';
import { VoluntariadoService } from '../../../services/voluntariado-service';

@Component({
  selector: 'app-matches',
  standalone: true,
  imports: [
    CommonModule,
    StatusToggleComponent,
    MatchCardComponent,
    Navbar,
    SidebarComponent
  ],
  templateUrl: './matches.html',
  styleUrl: './matches.css'
})
export class MatchesComponent implements OnInit {
  private voluntariadoService = inject(VoluntariadoService);

  activeTab: 'left' | 'right' = 'left';
  matches: any[] = [];

  ngOnInit() {
    this.loadMatches();
  }

  loadMatches() {
    this.voluntariadoService.getAllInscripciones().subscribe({
      next: (data) => {
        console.log('Matches API Response:', data);
        this.matches = data.map(item => ({
          id: item.id_inscripcion,
          volunteer: {
            name: item.nombre_voluntario || 'Voluntario',
            email: item.email_voluntario || 'email@example.com',
            skills: item.habilidades_voluntario ? item.habilidades_voluntario.split(',') : [],
            availability: item.disponibilidad_voluntario || 'No especificada',
            interests: item.intereses_voluntario ? item.intereses_voluntario.split(',') : []
          },
          organization: {
            name: item.nombre_actividad || 'Actividad', // Mapping activity name to org section based on card layout
            email: item.email_organizacion || 'org@example.com',
            description: item.descripcion_actividad || '',
            schedule: item.horario || 'No especificado',
            needs: item.habilidades_actividad ? item.habilidades_actividad.split(',') : []
          },
          status: item.estado
        }));
      },
      error: (err) => console.error('Error loading matches', err)
    });
  }

  onTabChange(tab: 'left' | 'right') {
    this.activeTab = tab;
  }

  onAccept(match: any) {
    console.log('Accepting match', match);
    this.voluntariadoService.updateInscripcionStatus(match.id, 'CONFIRMADO').subscribe({
      next: () => {
        alert('Match aceptado correctamente');
        this.loadMatches();
      },
      error: (err) => {
        console.error('Error accepting match', err);
        alert('Error al aceptar el match');
      }
    });
  }

  onReject(match: any) {
    console.log('Rejecting match', match);
    this.voluntariadoService.updateInscripcionStatus(match.id, 'RECHAZADO').subscribe({
      next: () => {
        alert('Match rechazado correctamente');
        this.loadMatches();
      },
      error: (err) => {
        console.error('Error rejecting match', err);
        alert('Error al rechazar el match');
      }
    });
  }

  onCreateMatch() {
    console.log('Create match clicked');
  }
}
