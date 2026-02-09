import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SidebarVolunteer } from './sidebar-volunteer';

describe('SidebarVolunteer', () => {
  let component: SidebarVolunteer;
  let fixture: ComponentFixture<SidebarVolunteer>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SidebarVolunteer]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SidebarVolunteer);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
