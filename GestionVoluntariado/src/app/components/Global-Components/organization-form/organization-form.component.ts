// src/app/components/Global-Components/organization-form/organization-form.component.ts

import { Component, EventEmitter, Output, Input, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormGroup, FormControl, Validators } from '@angular/forms';
import { OrganizationService } from '../../../services/organization.service';
import { Organization, OrganizationCreateData } from '../../../models/organizationModel';
import { HttpClientModule } from '@angular/common/http';

@Component({
    selector: 'app-organization-form',
    standalone: true,
    imports: [CommonModule, ReactiveFormsModule, HttpClientModule],
    templateUrl: './organization-form.component.html',
    styleUrl: './organization-form.component.css'
})
export class OrganizationFormComponent implements OnInit {

    private organizationService = inject(OrganizationService);

    @Input() submitLabel: string = 'Registrarme';
    @Input() isModal: boolean = true;
    @Input() isEdit: boolean = false;
    @Input() initialData: Organization | null = null;
    @Input() hideSubmitButton: boolean = false;

    // Emite el objeto Organization que devuelve la API (sin password)
    @Output() onSubmit = new EventEmitter<Organization>();

    organizationForm!: FormGroup;
    errorMessage: string = '';

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

                this.organizationService.updateProfile(this.initialData.cif, formData).subscribe({
                    next: (response) => {
                        this.onSubmit.emit(response);
                    },
                    error: (err) => {
                        this.errorMessage = 'Error al actualizar la organización. Por favor, intente de nuevo.';
                        console.error('Error al actualizar la organización:', err);
                    }
                });
            } else {
                this.organizationService.addOrganization(formData as OrganizationCreateData).subscribe({
                    next: (response) => {
                        this.onSubmit.emit(response);
                    },
                    error: (err) => {
                        this.errorMessage = 'Error al registrar la organización. Por favor, intente de nuevo.';
                        console.error('Error al registrar la organización:', err);
                    }
                });
            }
        } else {
            this.organizationForm.markAllAsTouched();
            this.errorMessage = 'Por favor, revise los campos marcados en rojo.';
            console.error('El formulario es inválido. Revise los campos.');
        }
    }

    get f() { return this.organizationForm.controls; }
}