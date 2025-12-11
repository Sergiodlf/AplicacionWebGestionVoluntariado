import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-voluntariado-card',
  imports: [CommonModule],
  templateUrl: './voluntariado-card.html',
  styleUrl: './voluntariado-card.css',
})
export class VoluntariadoCard {
  @Input() title: string = '';
  @Input() organization: string = '';
  @Input() skills: string[] = [];
  @Input() date: string = '';
  @Input() ods: { id: number; name: string; color: string }[] = [];
  @Input() status: string = '';

  @Output() onAction = new EventEmitter<void>();
}
