import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';

@Component({
    selector: 'app-register-organization',
    standalone: true,
    imports: [CommonModule, RouterLink, FormsModule],
    templateUrl: './register-organization.component.html',
    styleUrl: './register-organization.component.css'
})
export class RegisterOrganizationComponent { }
