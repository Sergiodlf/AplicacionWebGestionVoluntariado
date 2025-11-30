import { Routes } from '@angular/router';
import { Dashboard } from './pages/Administrator/dashboard/dashboard';
import { VolunteersComponent } from './pages/Administrator/volunteers/volunteers.component';
import { OrganizationsComponent } from './pages/Administrator/organizations/organizations.component';
import { VolunteersListComponent } from './pages/Administrator/volunteers-list/volunteers-list.component';
import { Matches } from './pages/Administrator/matches/matches';

export const routes: Routes = [
    { path: '', redirectTo: '/admin/dashboard', pathMatch: 'full' },
    { path: 'admin/dashboard', component: Dashboard },
    { path: 'admin/volunteers', component: VolunteersComponent },
    { path: 'admin/organizations', component: OrganizationsComponent },
    { path: 'admin/volunteers-list', component: VolunteersListComponent },
    { path: 'admin/matches', component: Matches }
];
