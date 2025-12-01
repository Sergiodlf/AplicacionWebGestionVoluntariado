import { Injectable, signal } from '@angular/core';

export interface Organization {
  name: string;
  type: string;
  location: string;
  description: string;
  tags: string[];
}

@Injectable({
  providedIn: 'root'
})
export class OrganizationService {
  private organizationsSignal = signal<Organization[]>([
    {
      name: 'Fundación Ayuda',
      type: 'ONG',
      location: 'Madrid',
      description: 'Ayuda a personas sin hogar',
      tags: ['Cocina', 'Logística']
    },
    {
      name: 'Centro Cultural',
      type: 'Cultural',
      location: 'Valencia',
      description: 'Promoción de la cultura local',
      tags: ['Arte', 'Música']
    }
  ]);

  getOrganizations() {
    return this.organizationsSignal.asReadonly();
  }

  addOrganization(org: Organization) {
    this.organizationsSignal.update(orgs => [...orgs, org]);
  }

  removeOrganization(name: string) {
    this.organizationsSignal.update(orgs => orgs.filter(o => o.name !== name));
  }
}
