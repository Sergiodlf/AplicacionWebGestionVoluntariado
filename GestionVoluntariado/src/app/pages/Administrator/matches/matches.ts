import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../../components/Global-Components/navbar/navbar.component';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { StatusToggleComponent } from '../../../components/Global-Components/status-toggle/status-toggle.component';
import { MatchCardComponent } from '../../../components/Administrator/Matches/match-card/match-card.component';

@Component({
  selector: 'app-matches',
  standalone: true,
  imports: [
    CommonModule,
    StatusToggleComponent,
    MatchCardComponent,
    NavbarComponent,
    SidebarComponent
  ],
  templateUrl: './matches.html',
  styleUrl: './matches.css'
})
export class MatchesComponent {
  activeTab: 'left' | 'right' = 'left';

  matches = [
    {
      volunteer: {
        name: 'María García',
        email: 'maria.garcia@gmail.com',
        skills: ['Educación', 'Comunicación', 'Inglés'],
        availability: 'Fines de semana',
        interests: ['Educación', 'Niños']
      },
      organization: {
        name: 'Fundación Ayuda',
        email: 'hogares.verdes@gmail.org',
        description: 'Necesitamos una profesora para educación a niños con dificultades',
        schedule: 'Fines de semana',
        needs: ['Educación', 'Comunicación', 'Inglés']
      }
    },
    {
      volunteer: {
        name: 'Juan Pérez',
        email: 'juan.perez@gmail.com',
        skills: ['Educación', 'Comunicación', 'Inglés'],
        availability: 'Fines de semana',
        interests: ['Educación', 'Niños']
      },
      organization: {
        name: 'Asociación Verde',
        email: 'hogares.verdes@gmail.org',
        description: 'Necesitamos una profesora para educación a niños con dificultades',
        schedule: 'Fines de semana',
        needs: ['Educación', 'Comunicación', 'Inglés']
      }
    }
  ];

  onTabChange(tab: 'left' | 'right') {
    this.activeTab = tab;
  }

  onAccept(match: any) {
    console.log('Match accepted', match);
  }

  onReject(match: any) {
    console.log('Match rejected', match);
  }

  onCreateMatch() {
    console.log('Create match clicked');
  }
}
