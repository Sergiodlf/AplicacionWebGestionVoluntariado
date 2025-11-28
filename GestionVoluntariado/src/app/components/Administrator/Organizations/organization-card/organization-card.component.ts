import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-organization-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './organization-card.component.html',
  styleUrl: './organization-card.component.css'
})
export class OrganizationCardComponent {
  @Input() name: string = '';
  @Input() type: string = '';
  @Input() location: string = '';
  @Input() description: string = '';
  @Input() tags: string[] = [];
  
  @Output() onAccept = new EventEmitter<void>();
  @Output() onReject = new EventEmitter<void>();
}
