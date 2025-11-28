import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-volunteer-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './volunteer-card.component.html',
  styleUrl: './volunteer-card.component.css'
})
export class VolunteerCardComponent {
  @Input() name: string = '';
  @Input() email: string = '';
  @Input() skills: string[] = [];
  @Input() availability: string = '';
  @Input() interests: string[] = [];
  
  @Output() onAccept = new EventEmitter<void>();
  @Output() onReject = new EventEmitter<void>();
}
