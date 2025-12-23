import { Component, EventEmitter, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-crear-voluntariado-modal',
  imports: [CommonModule, FormsModule],
  templateUrl: './crear-voluntariado-modal.html',
  styleUrl: './crear-voluntariado-modal.css',
})
export class CrearVoluntariadoModal {
  @Output() close = new EventEmitter<void>();
  @Output() created = new EventEmitter<any>();

  form = {
    title: '',
    organization: '',
    sector: '',
    zone: '',
    description: '',
    startDate: '',
    endDate: '',
    skillInput: '',
    odsInput: '',
    skills: [] as string[],
    ods: [] as { id: number; name: string; color: string }[],
  };

  addSkill() {
    const value = this.form.skillInput.trim();
    if (!value) return;
    this.form.skills.push(value);
    this.form.skillInput = '';
  }

  removeSkill(skill: string) {
    this.form.skills = this.form.skills.filter((s) => s !== skill);
  }

  addOds() {
    const value = this.form.odsInput.trim();
    if (!value) return;
    this.form.ods.push({
      id: this.form.ods.length + 1,
      name: value,
      color: '#00c851', // color por defecto, puedes cambiarlo
    });
    this.form.odsInput = '';
  }

  removeOds(ods: any) {
    this.form.ods = this.form.ods.filter((o) => o !== ods);
  }

  crear() {
    if (!this.form.title || !this.form.organization) return;

    this.created.emit({
      title: this.form.title,
      organization: this.form.organization,
      skills: this.form.skills,
      ods: this.form.ods,
      startDate: this.form.startDate,
      endDate: this.form.endDate,
      status: 'Pendiente',
    });
  }

  onClose() {
    this.close.emit();
  }
}
