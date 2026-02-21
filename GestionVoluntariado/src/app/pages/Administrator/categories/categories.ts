import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { CategoryService } from '../../../services/category.service';
import { Category } from '../../../models/Category';
import { NotificationService } from '../../../services/notification.service';

@Component({
    selector: 'app-categories',
    standalone: true,
    imports: [CommonModule, FormsModule, SidebarComponent],
    templateUrl: './categories.html',
    styleUrl: './categories.css'
})
export class CategoriesComponent implements OnInit {
    private categoryService = inject(CategoryService);
    private notificationService = inject(NotificationService);

    skills: Category[] = [];
    interests: Category[] = [];

    newSkillName: string = '';
    newInterestName: string = '';

    ngOnInit() {
        this.loadCategories();
    }

    loadCategories() {
        this.categoryService.getHabilidades().subscribe(data => this.skills = data);
        this.categoryService.getIntereses().subscribe(data => this.interests = data);
    }

    addSkill() {
        if (!this.newSkillName.trim()) return;

        const exists = this.skills.some(s => s.nombre.toLowerCase() === this.newSkillName.trim().toLowerCase());
        if (exists) {
            this.notificationService.showWarning('Esta habilidad ya existe.');
            return;
        }

        this.categoryService.addHabilidad(this.newSkillName).subscribe({
            next: () => {
                this.notificationService.showSuccess('Habilidad añadida correctamente.');
                this.newSkillName = '';
                this.loadCategories();
            },
            error: () => this.notificationService.showError('Error al añadir la habilidad.')
        });
    }

    async deleteSkill(id: number) {
        const confirmed = await this.notificationService.showConfirmation(
            '¿Eliminar habilidad?',
            '¿Estás seguro de eliminar esta habilidad? Esta acción no se puede deshacer.'
        );
        if (confirmed) {
            this.categoryService.deleteHabilidad(id).subscribe({
                next: () => {
                    this.notificationService.showSuccess('Habilidad eliminada.');
                    this.loadCategories();
                },
                error: () => this.notificationService.showError('Error al eliminar la habilidad.')
            });
        }
    }

    addInterest() {
        if (!this.newInterestName.trim()) return;

        const exists = this.interests.some(i => i.nombre.toLowerCase() === this.newInterestName.trim().toLowerCase());
        if (exists) {
            this.notificationService.showWarning('Este interés ya existe.');
            return;
        }

        this.categoryService.addInteres(this.newInterestName).subscribe({
            next: () => {
                this.notificationService.showSuccess('Interés añadido correctamente.');
                this.newInterestName = '';
                this.loadCategories();
            },
            error: () => this.notificationService.showError('Error al añadir el interés.')
        });
    }

    async deleteInterest(id: number) {
        const confirmed = await this.notificationService.showConfirmation(
            '¿Eliminar interés?',
            '¿Estás seguro de eliminar este interés? Esta acción no se puede deshacer.'
        );
        if (confirmed) {
            this.categoryService.deleteInteres(id).subscribe({
                next: () => {
                    this.notificationService.showSuccess('Interés eliminado.');
                    this.loadCategories();
                },
                error: () => this.notificationService.showError('Error al eliminar el interés.')
            });
        }
    }
}
