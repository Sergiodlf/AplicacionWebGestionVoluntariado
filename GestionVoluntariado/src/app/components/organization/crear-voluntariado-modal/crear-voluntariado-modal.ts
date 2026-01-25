import { Component, EventEmitter, Output, Input, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { VoluntariadoService } from '../../../services/voluntariado-service';
import { CategoryService, ODS, Category } from '../../../services/category.service';
import { OrganizationService } from '../../../services/organization.service';
import { NotificationService } from '../../../services/notification.service';
import { Organization } from '../../../models/organizationModel';

@Component({
  selector: 'app-crear-voluntariado-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './crear-voluntariado-modal.html',
  styleUrl: './crear-voluntariado-modal.css',
})
export class CrearVoluntariadoModal implements OnInit, OnDestroy {
  @Input() cifOrganization: string = '';
  @Input() activityToEdit: any = null;
  @Output() close = new EventEmitter<void>();
  @Output() created = new EventEmitter<any>();
  @Output() updated = new EventEmitter<any>();


  availableOds: ODS[] = [];
  availableHabilidades: Category[] = [];
  availableOrganizations: Organization[] = [];

  form = {
    title: '',
    selectedCif: '', // Changed from string name to selected CIF
    sector: '',
    zone: '',
    description: '',
    startDate: '',
    endDate: '',
    selectedSkillId: '' as string | number,
    selectedOdsId: '' as string | number,
    skills: [] as Category[],
    ods: [] as ODS[],
    maxParticipants: 10
  };

  constructor(
    private voluntariadoService: VoluntariadoService,
    private categoryService: CategoryService,
    private organizationService: OrganizationService,
    private notificationService: NotificationService
  ) { }

  ngOnInit() {
    this.categoryService.getODS().subscribe(ods => {
      this.availableOds = ods;
    });
    this.categoryService.getHabilidades().subscribe(h => {
      this.availableHabilidades = h;
    });

    // Load Accepted Organizations
    this.organizationService.getOrganizations().subscribe(orgs => {
      // ... logic skipped for brevity, keeping it same ...
      this.availableOrganizations = orgs.filter(o => {
        const estado = (o.estado || '').toLowerCase();
        const isAccepted = estado === 'aprobado' || estado === 'aceptada' || estado === 'aceptado' || estado === 'confirmado';
        const isForced = this.cifOrganization && o.cif === this.cifOrganization;

        return isAccepted || isForced;
      });

      // If Input cifOrganization is provided, try to pre-select it
      if (this.cifOrganization) {
        this.form.selectedCif = this.cifOrganization;
      }

      // Pre-fill if editing
      if (this.activityToEdit) {
        this.fillFormForEdit();
      }
    });


    this.setBodyScroll(true);
  }

  fillFormForEdit() {
    const a = this.activityToEdit;
    this.form.title = a.nombre;
    this.form.selectedCif = a.cif_organizacion || this.cifOrganization;
    this.form.zone = a.direccion;
    this.form.maxParticipants = a.maxParticipantes;
    this.form.sector = a.sector;

    // Dates (Expects YYYY-MM-DD from 'YYYY-MM-DD HH:mm:ss')
    if (a.fechaInicio) this.form.startDate = a.fechaInicio.substring(0, 10);

    if (a.fechaFin) {
      this.form.endDate = a.fechaFin.substring(0, 10);
    } else if (this.form.startDate) {
      // Fallback: If no end date, set to +30 days from start
      const d = new Date(this.form.startDate);
      d.setDate(d.getDate() + 30);
      this.form.endDate = d.toISOString().substring(0, 10);
    }

    // Lists (Assuming API returns arrays of objects {id, nombre})
    this.form.ods = a.ods ? [...a.ods] : [];
    this.form.skills = (a.habilidades || a.skills) ? [...(a.habilidades || a.skills)] : [];
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
    if (!this.form.title || !this.form.selectedCif || !this.form.startDate || !this.form.endDate || !this.form.sector || !this.form.zone) {
      this.notificationService.showError('Por favor rellena todos los campos obligatorios (Título, Organización, Fechas, Sector, Zona).');
      return;
    }

    const payload = {
      nombre: this.form.title,
      cifOrganizacion: this.form.selectedCif, // Use selected CIF
      descripcion: this.form.description || '', // Ensure string
      fechaInicio: this.form.startDate ? this.form.startDate.substring(0, 10) : '',
      fechaFin: this.form.endDate ? this.form.endDate.substring(0, 10) : '',
      direccion: this.form.zone,
      maxParticipantes: this.form.maxParticipants || 10,
      estado: 'Pendiente',
      sector: this.form.sector,
      habilidades: this.form.skills.map(s => s.id),
      ods: this.form.ods.map(o => o.id)
    };

    console.log('Enviando actividad:', payload);

    if (this.activityToEdit) {
      this.voluntariadoService.updateActivity(this.activityToEdit.codActividad, payload).subscribe({
        next: (res) => {
          this.notificationService.showSuccess('Actividad actualizada con éxito');
          this.updated.emit(res);
          this.onClose();
        },
        error: (err) => {
          console.error('Error actualizando:', err);
          const errorMessage = err.error?.message || err.error?.error || 'Error desconocido';
          this.notificationService.showError('Error al actualizar: ' + errorMessage);
        }
      });
    } else {
      this.voluntariadoService.crearActividad(payload).subscribe({
        next: (res) => {
          console.log('Actividad creada:', res);
          this.notificationService.showSuccess('Actividad creada con éxito');
          this.created.emit(res);
          this.onClose();
        },
        error: (err) => {
          console.error('Error creando actividad:', err);
          const errorMessage = err.error?.message || err.error?.error || err.message || 'Inténtalo de nuevo.';
          this.notificationService.showError('Error al crear la actividad: ' + errorMessage);
        }
      });
    }

  }

  onClose() {
    this.setBodyScroll(false);
    this.close.emit();
  }
}
