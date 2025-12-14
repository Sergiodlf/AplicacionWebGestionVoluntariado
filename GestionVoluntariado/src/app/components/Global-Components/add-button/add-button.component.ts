import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-add-button',
  standalone: true,
  imports: [CommonModule],
  template: `
    <button class="btn bg-custom-blue text-white rounded-pill px-4 py-2 fw-bold d-flex align-items-center shadow-sm hover-effect" (click)="onClick.emit()">
      <i class="bi bi-plus-lg me-2"></i>
      {{ label }}
    </button>
  `,
  styles: [`
    .bg-custom-blue { background-color: #1a237e !important; }
    .hover-effect:hover { background-color: #151b60 !important; transform: translateY(-1px); }
  `]
})
export class AddButtonComponent {
  @Input() label: string = 'Añadir';
  @Output() onClick = new EventEmitter<void>();
}
