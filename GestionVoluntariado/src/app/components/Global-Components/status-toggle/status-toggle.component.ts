import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-status-toggle',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="d-inline-flex bg-white rounded-pill p-1 shadow-sm border">
      <button 
        class="btn rounded-pill px-4 py-2 fw-bold d-flex align-items-center gap-2 transition-all"
        [ngClass]="{'bg-custom-blue text-white': activeOption === 'left', 'text-muted bg-transparent': activeOption !== 'left'}"
        (click)="selectOption('left')">
        {{ leftLabel }}
        <span *ngIf="leftCount !== undefined" class="badge bg-white text-custom-blue rounded-pill ms-1">{{ leftCount }}</span>
      </button>
      <button 
        class="btn rounded-pill px-4 py-2 fw-bold transition-all"
        [ngClass]="{'bg-custom-blue text-white': activeOption === 'right', 'text-muted bg-transparent': activeOption !== 'right'}"
        (click)="selectOption('right')">
        {{ rightLabel }}
        <span *ngIf="rightCount !== undefined" class="badge bg-white text-custom-blue rounded-pill ms-1">{{ rightCount }}</span>
      </button>
    </div>
  `,
  styles: [`
    .bg-custom-blue { background-color: #1a237e !important; }
    .text-custom-blue { color: #1a237e !important; }
    .transition-all { transition: all 0.3s ease; }
    .btn:focus { box-shadow: none; }
  `]
})
export class StatusToggleComponent {
  @Input() leftLabel: string = 'Pendientes';
  @Input() rightLabel: string = 'Aprobados';
  @Input() leftCount?: number;
  @Input() rightCount?: number;
  @Input() activeOption: 'left' | 'right' = 'left';
  
  @Output() optionChanged = new EventEmitter<'left' | 'right'>();

  selectOption(option: 'left' | 'right') {
    this.activeOption = option;
    this.optionChanged.emit(option);
  }
}
