import { Component, Output, EventEmitter, input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-organization-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './organization-card.component.html',
  styleUrl: './organization-card.component.css'
})
export class OrganizationCardComponent {
  name = input.required<string>();
  type = input<string>('');
  location = input<string>('');
  description = input<string>('');
  tags = input<string[]>([]);
  
  @Output() onAccept = new EventEmitter<void>();
  @Output() onReject = new EventEmitter<void>();
}
