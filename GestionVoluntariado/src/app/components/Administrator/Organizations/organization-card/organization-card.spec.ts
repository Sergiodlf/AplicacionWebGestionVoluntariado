import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OrganizationCard } from './organization-card';

describe('OrganizationCard', () => {
  let component: OrganizationCard;
  let fixture: ComponentFixture<OrganizationCard>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [OrganizationCard]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OrganizationCard);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
