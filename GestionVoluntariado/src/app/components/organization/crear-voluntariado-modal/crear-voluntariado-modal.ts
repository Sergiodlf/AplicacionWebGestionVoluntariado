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
  @Output() close = new EventEmitter<void>();
  @Output() created = new EventEmitter<any>();

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
      console.log('Raw Organizations fetched:', orgs);

      this.availableOrganizations = orgs.filter(o => {
        const estado = (o.estado || '').toLowerCase();
        // Include if Accepted OR if it matches the forced CIF (e.g. the logged-in org itself)
        const isAccepted = estado === 'aprobado' || estado === 'aceptada' || estado === 'aceptado' || estado === 'confirmado';
        const isForced = this.cifOrganization && o.cif === this.cifOrganization;

        return isAccepted || isForced;
      });

      console.log('Filtered Organizations:', this.availableOrganizations);

      // If Input cifOrganization is provided, try to pre-select it
      if (this.cifOrganization) {
        this.form.selectedCif = this.cifOrganization;
      }
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

  onClose() {
    this.setBodyScroll(false);
    this.close.emit();
  }
}
