import { Component, Output, EventEmitter, input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-volunteer-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './volunteer-card.component.html',
  styleUrl: './volunteer-card.component.css'
})
export class VolunteerCardComponent {
  name = input.required<string>();
  email = input.required<string>();
  skills = input<string[]>([]);
  availability = input<string>('');
  interests = input<string[]>([]);

  @Output() onAccept = new EventEmitter<void>();
  @Output() onReject = new EventEmitter<void>();

  status = input<string>('PENDIENTE');
}
