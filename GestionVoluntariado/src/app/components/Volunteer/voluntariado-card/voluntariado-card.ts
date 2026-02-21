import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-voluntariado-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './voluntariado-card.html',
  styleUrl: './voluntariado-card.css',
})
export class VoluntariadoCard {
  @Input() title: string = '';
  @Input() organization: string = '';
  @Input() skills: any[] = [];
  @Input() date: string = '';
  @Input() fechaInicio: string = '';
  @Input() fechaFin: string = '';
  @Input() instituciones?: string = '';
  @Input() ciclo: string = '';
  @Input() ods: { id: number; nombre: string; color: string }[] = [];
  @Input() status: string = '';
  @Input() isOrganization: boolean = false;
  @Input() buttonText: string = '';
  @Input() necesidades: any[] = [];
  @Input() editButton: boolean = false;

  @Output() onAction = new EventEmitter<void>();
  @Output() onEdit = new EventEmitter<void>();
  @Output() onDetails = new EventEmitter<void>();
}
