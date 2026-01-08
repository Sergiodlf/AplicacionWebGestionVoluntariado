import { Component, EventEmitter, Output, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { VoluntariadoService } from '../../../services/voluntariado-service';

@Component({
  selector: 'app-crear-voluntariado-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './crear-voluntariado-modal.html',
  styleUrl: './crear-voluntariado-modal.css',
})
export class CrearVoluntariadoModal {
  @Input() cifOrganization: string = '';
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

  constructor(private voluntariadoService: VoluntariadoService) { }

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
    if (!this.form.title) return;

    const payload = {
      nombre: this.form.title,
      cifOrganizacion: this.cifOrganization,
      descripcion: this.form.description,
      fechaInicio: this.form.startDate,
      fechaFin: this.form.endDate,
      direccion: this.form.zone,
      maxParticipantes: 10,
      estado: 'Pendiente',
      sector: this.form.sector,
      habilidades: this.form.skills.join(','),
      ods: this.form.ods
    };

    console.log('Sending payload:', payload);

    this.voluntariadoService.crearActividad(payload).subscribe({
      next: (res) => {
        console.log('Activity created:', res);
        this.created.emit(res);
        this.onClose();
      },
      error: (err) => {
        console.error('Error creating activity:', err);
      }
    });
  }

  onClose() {
    this.close.emit();
  }
}
