import { Component, EventEmitter, Output, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-organization-form',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './organization-form.component.html',
  styleUrl: './organization-form.component.css'
})
export class OrganizationFormComponent {
  @Input() submitLabel: string = 'Registrarme';
  @Output() onSubmit = new EventEmitter<void>();

  submit() {
    this.onSubmit.emit();
  }
}
