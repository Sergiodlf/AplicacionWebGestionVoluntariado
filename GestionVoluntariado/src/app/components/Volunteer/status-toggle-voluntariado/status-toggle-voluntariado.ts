import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-status-toggle-voluntariado',
  imports: [CommonModule],
  templateUrl: './status-toggle-voluntariado.html',
  styleUrl: './status-toggle-voluntariado.css',
})
export class StatusToggleVoluntariado {

  @Input() leftLabel: string = 'Pendientes';
  @Input() middleLabel: string = 'En Curso';
  @Input() rightLabel: string = 'Completados';

  @Input() leftCount: number = 0;
  @Input() middleCount: number = 0;
  @Input() rightCount: number = 0;

  @Output() optionChanged = new EventEmitter<'left' | 'middle' | 'right'>();

  active: 'left' | 'middle' | 'right' = 'left';

  setOption(option: 'left' | 'middle' | 'right') {
    this.active = option;
    this.optionChanged.emit(option);
  }
}
