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
  availability = input<string[]>([]);
  interests = input<string[]>([]);

  @Output() onAccept = new EventEmitter<void>();
  @Output() onReject = new EventEmitter<void>();
  @Output() onAssign = new EventEmitter<void>();

  status = input<string>('PENDIENTE');
  parseList(value: string | string[]): string[] {
    if (Array.isArray(value)) {
      return value;
    }
    if (!value) {
      return [];
    }
    try {
      // Intentar parsear si es un string JSON '["a", "b"]'
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed : [value];
    } catch (e) {
      // Si falla, asumir que es un string simple o separado por comas si se desea, 
      // pero por ahora devolvemos como array simple
      return [value];
    }
  }
}
