import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-status-toggle-voluntariado',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './status-toggle-voluntariado.html',
  styleUrl: './status-toggle-voluntariado.css',
})
export class StatusToggleVoluntariado {

  @Input() leftLabel: string = 'Pendientes';
  @Input() secondLabel: string = ''; // New option between left and middle
  @Input() middleLabel: string = '';
  @Input() rightLabel: string = '';

  @Input() leftCount: number = 0;
  @Input() secondCount: number = 0;
  @Input() middleCount: number = 0;
  @Input() rightCount: number = 0;

  @Output() optionChanged = new EventEmitter<'left' | 'second' | 'middle' | 'right'>();

  active: 'left' | 'second' | 'middle' | 'right' = 'left';

  setOption(option: 'left' | 'second' | 'middle' | 'right') {
    this.active = option;
    this.optionChanged.emit(option);
  }
}
