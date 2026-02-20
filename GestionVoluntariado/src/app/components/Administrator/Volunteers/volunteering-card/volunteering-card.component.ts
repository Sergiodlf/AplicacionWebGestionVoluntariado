import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-volunteering-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './volunteering-card.component.html',
  styleUrl: './volunteering-card.component.css'
})
export class VolunteeringCardComponent {
  @Input() title: string = '';
  @Input() organization: string = '';
  @Input() skills: any[] = [];
  @Input() fechaInicio: string = '';
  @Input() fechaFin: string = '';
  @Input() ods: any[] = [];
  @Input() status: string = '';
  @Input() necesidades: any[] = [];

  @Output() onAction = new EventEmitter<void>();
  @Output() onInfo = new EventEmitter<void>();
  @Output() onAssign = new EventEmitter<void>();
}
