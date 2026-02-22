// src/app/components/Global-Components/organization-form/organization-form.component.ts

import { Component, EventEmitter, Output, Input, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormGroup, FormControl, Validators } from '@angular/forms';
import { OrganizationService } from '../../../services/organization.service';
import { Organization } from '../../../models/organizationModel';
import { HttpClientModule } from '@angular/common/http';
import { AuthService } from '../../../services/auth.service';
import { NotificationService } from '../../../services/notification.service';

@Component({
    selector: 'app-organization-form',
    standalone: true,
    imports: [CommonModule, ReactiveFormsModule, HttpClientModule],
    templateUrl: './organization-form.component.html',
    styleUrl: './organization-form.component.css'
})
export class OrganizationFormComponent implements OnInit {

    private organizationService = inject(OrganizationService);
    private authService = inject(AuthService);
    private notificationService = inject(NotificationService);

    @Input() submitLabel: string = 'Registrarme';
    @Input() isModal: boolean = true;
    @Input() isEdit: boolean = false;
    @Input() initialData: any | null = null;
    @Input() hideSubmitButton: boolean = false;

    // Emite el objeto Organization que devuelve la API (sin password)
    @Output() onSubmit = new EventEmitter<Organization>();

    organizationForm!: FormGroup;
    errorMessage: string = '';

    availableSectors: string[] = [
        'Educación', 'Salud', 'Social', 'Medio Ambiente',
        'Comunitario', 'Cultura', 'Deportes', 'Internacional',
        'Derechos Humanos', 'Protección Animal', 'Tecnología'
    ];

    availableZones: string[] = [
        'Casco Viejo', 'Ensanche', 'San Juan', 'Iturrama', 'Rochapea',
        'Txantrea', 'Azpiligaña', 'Milagrosa', 'Buztintxuri', 'Mendillorri',
        'Sarriguren', 'Barañáin', 'Burlada', 'Villava', 'Uharte',
        'Berriozar', 'Ansoáin', 'Noáin', 'Zizur Mayor', 'Mutilva', 'Pamplona (Otros)', 'Tudela', 'Estella', 'Olite', 'Tafalla'
    ];

    ngOnInit(): void {
        const cifNifPattern = /^[A-HJNPQRSUVW]{1}[0-9]{7}([A-Z]|[0-9]){1}$/i;

        this.organizationForm = new FormGroup({

            cif: new FormControl('', [
                Validators.required,
                Validators.pattern(cifNifPattern)
            ]),

            nombre: new FormControl('', [
                Validators.required,
                Validators.minLength(3),
                Validators.maxLength(100)
            ]),

            email: new FormControl('', [
                Validators.required,
                Validators.email
            ]),

            password: new FormControl('', this.isEdit ? [] : [
                Validators.required,
                Validators.minLength(8),
                Validators.pattern(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/)
            ]),

            direccion: new FormControl('', Validators.required),

            sector: new FormControl('Educación', Validators.required),
            localidad: new FormControl('Pamplona', Validators.required),

            cp: new FormControl('', [
                Validators.required,
                Validators.pattern(/^[0-9]{5}$/)
            ]),

            contacto: new FormControl('', Validators.required),

            descripcion: new FormControl('', [
                Validators.required,
                Validators.minLength(20),
                Validators.maxLength(500)
            ]),


        });

        if (this.isEdit && this.initialData) {
            this.organizationForm.patchValue(this.initialData);
        }
    }

    submit() {
        this.errorMessage = '';
        if (this.organizationForm.valid) {
            const formData = this.organizationForm.value;

            if (this.isEdit && this.initialData) {
                // Remove password if empty during edit
                if (!formData.password) delete formData.password;

                const userRole = localStorage.getItem('user_role');
                const isDocente = userRole === 'docente' || userRole === 'admin';

                if (isDocente && this.initialData.cif) {
                    // Admin/Docente editing an organization
                    this.organizationService.updateProfile(this.initialData.cif, formData).subscribe({
                        next: (response) => {
                            this.notificationService.showSuccess('Organización actualizada correctamente.');
                            this.onSubmit.emit(response.datos || response);
                        },
                        error: (err: any) => {
                            const backendMsg = err.error?.message || err.error?.msg || err.error?.error || err.error || err.message || 'Inténtalo de nuevo.';
                            this.errorMessage = backendMsg;
                            this.notificationService.showError('Error al actualizar la organización: ' + backendMsg);
                            console.error('Admin edit error:', err);
                        }
                    });
                } else {
                    // Organization editing its own profile
                    this.authService.updateProfile(formData).subscribe({
                        next: (response) => {
                            this.notificationService.showSuccess('Perfil actualizado correctamente.');
                            this.onSubmit.emit(response.datos || response);
                        },
                        error: (err: any) => {
                            const backendMsg = err.error?.message || err.error?.msg || err.error?.error || err.error || err.message || 'Inténtalo de nuevo.';
                            this.errorMessage = backendMsg;
                            this.notificationService.showError('Error al actualizar su perfil: ' + backendMsg);
                            console.error('Self edit error:', err);
                        }
                    });
                }
            } else {
                // For new registration, pass the raw form data (including password) to the parent helper
                // The parent (RegisterOrganizationComponent) handles Firebase + Backend registration
                this.onSubmit.emit(formData as Organization);
            }
        } else {
            this.organizationForm.markAllAsTouched();
            this.errorMessage = 'Por favor, revise los campos marcados en rojo.';
            console.error('El formulario es inválido. Revise los campos.');
        }
    }

    get f() { return this.organizationForm.controls; }
}
