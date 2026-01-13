import { Component, EventEmitter, Output, Input, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { VoluntariadoService } from '../../../services/voluntariado-service';
import { CategoryService, ODS, Category } from '../../../services/category.service';

@Component({
  selector: 'app-crear-voluntariado-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './crear-voluntariado-modal.html',
  styleUrl: './crear-voluntariado-modal.css',
})
export class CrearVoluntariadoModal implements OnInit, OnDestroy {
  @Input() cifOrganization: string = '';
  @Output() close = new EventEmitter<void>();
  @Output() created = new EventEmitter<any>();

  availableOds: ODS[] = [];
  availableHabilidades: Category[] = [];

  form = {
    title: '',
    organization: '',
    sector: '',
    zone: '',
    description: '',
    startDate: '',
    endDate: '',
    selectedSkillId: '' as string | number,
    selectedOdsId: '' as string | number,
    skills: [] as Category[],
    ods: [] as ODS[],
  };

  constructor(
    private voluntariadoService: VoluntariadoService,
    private categoryService: CategoryService
  ) { }

  ngOnInit() {
    this.categoryService.getODS().subscribe(ods => {
      this.availableOds = ods;
    });
    this.categoryService.getHabilidades().subscribe(h => {
      this.availableHabilidades = h;
    });
    this.setBodyScroll(true);
  }

  ngOnDestroy() {
    this.setBodyScroll(false);
  }

  private setBodyScroll(lock: boolean) {
    if (lock) {
      document.body.classList.add('body-modal-open');
    } else {
      document.body.classList.remove('body-modal-open');
    }
  }

  addSkill() {
    if (!this.form.selectedSkillId) return;
    const skill = this.availableHabilidades.find(h => h.id == this.form.selectedSkillId);
    if (skill && !this.form.skills.find(s => s.id === skill.id)) {
      this.form.skills.push(skill);
    }
    this.form.selectedSkillId = '';
  }

  removeSkill(skill: Category) {
    this.form.skills = this.form.skills.filter((s) => s.id !== skill.id);
  }

  addOds() {
    if (!this.form.selectedOdsId) return;
    const ods = this.availableOds.find(o => o.id == this.form.selectedOdsId);
    if (ods && !this.form.ods.find(o => o.id === ods.id)) {
      this.form.ods.push(ods);
    }
    this.form.selectedOdsId = '';
  }

  removeOds(ods: ODS) {
    this.form.ods = this.form.ods.filter((o) => o.id !== ods.id);
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
      habilidades: this.form.skills.map(s => s.id),
      ods: this.form.ods.map(o => o.id)
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
    this.setBodyScroll(false);
    this.close.emit();
  }
}
