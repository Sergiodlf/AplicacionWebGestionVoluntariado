import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MisVoluntariadosOrganizacion } from './mis-voluntariados-organizacion';

describe('MisVoluntariadosOrganizacion', () => {
  let component: MisVoluntariadosOrganizacion;
  let fixture: ComponentFixture<MisVoluntariadosOrganizacion>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MisVoluntariadosOrganizacion]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MisVoluntariadosOrganizacion);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
