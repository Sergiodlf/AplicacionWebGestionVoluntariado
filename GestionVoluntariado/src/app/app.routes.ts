import { Routes } from '@angular/router';
import { LandingPageComponent } from './pages/Landing/landing-page.component';
import { LoginComponent } from './pages/Auth/login/login.component';
import { RegisterSelectionComponent } from './pages/Auth/register-selection/register-selection.component';
import { RegisterVolunteerComponent } from './pages/Auth/register-volunteer/register-volunteer.component';
import { RegisterOrganizationComponent } from './pages/Auth/register-organization/register-organization.component';
import { AdminLayoutComponent } from './layouts/admin-layout/admin-layout.component';
import { VolunteersComponent } from './pages/Administrator/volunteers/volunteers.component';
import { DashboardComponent } from './pages/Administrator/dashboard/dashboard';

import { MatchesComponent } from './pages/Administrator/matches/matches';
import { OrganizationsComponent } from './pages/Administrator/organizations/organizations';
import { ActivitiesComponent } from './pages/Administrator/activities/activities';

export const routes: Routes = [
    { path: '', component: LandingPageComponent, pathMatch: 'full' },
    { path: 'login', component: LoginComponent },
    { path: 'register', component: RegisterSelectionComponent },
    { path: 'register/volunteer', component: RegisterVolunteerComponent },
    { path: 'register/organization', component: RegisterOrganizationComponent },
    {
        path: 'admin',
        component: AdminLayoutComponent,
        children: [
            { path: 'volunteers', component: VolunteersComponent },
            { path: 'dashboard', component: DashboardComponent },
            { path: 'matches', component: MatchesComponent },
            { path: 'organizations', component: OrganizationsComponent },
            { path: 'activities', component: ActivitiesComponent },
            { path: '', redirectTo: 'volunteers', pathMatch: 'full' }
        ]
    }
];
