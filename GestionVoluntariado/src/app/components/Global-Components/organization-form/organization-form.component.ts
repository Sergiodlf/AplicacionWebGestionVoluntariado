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
    // Emite el objeto Organization que devuelve la API (sin password)
    @Output() onSubmit = new EventEmitter<Organization>();

    organizationForm!: FormGroup;

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

            password: new FormControl('', [
                Validators.required,
                Validators.minLength(8),
                Validators.pattern(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/)
            ]),

            direccion: new FormControl('', Validators.required),

            sector: new FormControl('Educación', Validators.required),
            localidad: new FormControl('Pamplona', Validators.required),

            descripcion: new FormControl('', [
                Validators.required,
                Validators.minLength(20),
                Validators.maxLength(500)
            ]),
        });
    }

    submit() {
        if (this.organizationForm.valid) {
            const formData: OrganizationCreateData = this.organizationForm.value;

            this.organizationService.addOrganization(formData).subscribe({
                next: (response) => {
                    this.onSubmit.emit(response);
                    // Notificamos al padre para que recargue la lista
                    //this.organizationService.notifyOrganizationUpdate();
                },
                error: (err) => {
                    console.error('Error al registrar la organización:', err);
                }
            });
        } else {
            this.organizationForm.markAllAsTouched();
            console.error('El formulario es inválido. Revise los campos.');
        }
    }

    get f() { return this.organizationForm.controls; }
}