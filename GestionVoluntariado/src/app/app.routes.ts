import { Routes } from '@angular/router';
import { Dashboard } from './pages/Administrator/dashboard/dashboard';
import { VolunteersComponent } from './pages/Administrator/volunteers/volunteers.component';
import { OrganizationsComponent } from './pages/Administrator/organizations/organizations.component';

export const routes: Routes = [
    { path: '', redirectTo: '/admin/dashboard', pathMatch: 'full' },
    { path: 'admin/dashboard', component: Dashboard },
    { path: 'admin/volunteers', component: VolunteersComponent },
    { path: 'admin/organizations', component: OrganizationsComponent }
];
