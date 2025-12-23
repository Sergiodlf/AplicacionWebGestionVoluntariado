import { Routes } from '@angular/router';
import { LandingPageComponent } from './pages/Landing/landing-page.component';
import { LoginComponent } from './pages/Auth/login/login.component';
import { RegisterSelectionComponent } from './pages/Auth/register-selection/register-selection.component';
import { RegisterVolunteerComponent } from './pages/Auth/register-volunteer/register-volunteer.component';
import { RegisterOrganizationComponent } from './pages/Auth/register-organization/register-organization.component';
import { VolunteersComponent } from './pages/Administrator/volunteers/volunteers.component';
import { DashboardComponent } from './pages/Administrator/dashboard/dashboard';

import { MatchesComponent } from './pages/Administrator/matches/matches';
import { OrganizationsComponent } from './pages/Administrator/organizations/organizations';
import { ActivitiesComponent } from './pages/Administrator/activities/activities';

import { Voluntariados } from './pages/Volunteer/voluntariados/voluntariados';
import { MisVoluntariados } from './pages/Volunteer/mis-voluntariados/mis-voluntariados';

import { MisVoluntariadosOrganizacion } from './pages/Organization/mis-voluntariados-organizacion/mis-voluntariados-organizacion';

export const routes: Routes = [
  { path: '', component: LandingPageComponent },
  { path: 'login', component: LoginComponent },
  { path: 'register', component: RegisterSelectionComponent },
  { path: 'register/volunteer', component: RegisterVolunteerComponent },
  { path: 'register/organization', component: RegisterOrganizationComponent },
  { path: 'admin/dashboard', component: DashboardComponent },
  { path: 'admin/volunteers', component: VolunteersComponent },
  { path: 'admin/organizations', component: OrganizationsComponent },
  { path: 'admin/activities', component: ActivitiesComponent },
  { path: 'admin/matches', component: MatchesComponent },
  { path: 'volunteer/voluntariados', component: Voluntariados},
  { path: 'volunteer/mis-voluntariados', component: MisVoluntariados},
  { path: 'organization/mis-voluntariados-organizacion', component: MisVoluntariadosOrganizacion}
];
