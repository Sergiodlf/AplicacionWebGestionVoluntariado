import { Routes } from '@angular/router';
import { Dashboard } from './pages/Administrator/dashboard/dashboard';
import { VolunteersComponent } from './pages/Administrator/volunteers/volunteers.component';
import { OrganizationsComponent } from './pages/Administrator/organizations/organizations.component';
import { VolunteersListComponent } from './pages/Administrator/volunteers-list/volunteers-list.component';
import { Matches } from './pages/Administrator/matches/matches';

import { LoginComponent } from './pages/Auth/login/login.component';
import { RegisterSelectionComponent } from './pages/Auth/register-selection/register-selection.component';
import { RegisterVolunteerComponent } from './pages/Auth/register-volunteer/register-volunteer.component';
import { RegisterOrganizationComponent } from './pages/Auth/register-organization/register-organization.component';

export const routes: Routes = [
    { path: '', redirectTo: '/login', pathMatch: 'full' },
    { path: 'login', component: LoginComponent },
    { path: 'register', component: RegisterSelectionComponent },
    { path: 'register/volunteer', component: RegisterVolunteerComponent },
    { path: 'register/organization', component: RegisterOrganizationComponent },
    { path: 'admin/dashboard', component: Dashboard },
    { path: 'admin/volunteers', component: VolunteersComponent },
    { path: 'admin/organizations', component: OrganizationsComponent },
    { path: 'admin/volunteers-list', component: VolunteersListComponent },
    { path: 'admin/matches', component: Matches }
];
