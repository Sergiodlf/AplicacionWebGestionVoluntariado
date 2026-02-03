import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Navbar } from '../../../components/Global-Components/navbar/navbar';
import { SidebarComponent } from '../../../components/Administrator/Sidebar/sidebar.component';
import { CategoryService } from '../../../services/category.service';
import { Category } from '../../../models/Category';

@Component({
    selector: 'app-categories',
    standalone: true,
    imports: [CommonModule, FormsModule, SidebarComponent],
    templateUrl: './categories.html',
    styleUrl: './categories.css'
})
export class CategoriesComponent implements OnInit {
    private categoryService = inject(CategoryService);

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
        this.categoryService.addHabilidad(this.newSkillName).subscribe(() => {
            this.newSkillName = '';
            this.loadCategories();
        });
    }

    deleteSkill(id: number) {
        if (confirm('¿Estás seguro de eliminar esta habilidad?')) {
            this.categoryService.deleteHabilidad(id).subscribe(() => {
                this.loadCategories();
            });
        }
    }

    addInterest() {
        if (!this.newInterestName.trim()) return;
        this.categoryService.addInteres(this.newInterestName).subscribe(() => {
            this.newInterestName = '';
            this.loadCategories();
        });
    }

    deleteInterest(id: number) {
        if (confirm('¿Estás seguro de eliminar este interés?')) {
            this.categoryService.deleteInteres(id).subscribe(() => {
                this.loadCategories();
            });
        }
    }
}
