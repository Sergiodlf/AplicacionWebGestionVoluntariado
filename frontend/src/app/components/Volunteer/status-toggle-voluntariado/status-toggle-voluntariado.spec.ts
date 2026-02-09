import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StatusToggleVoluntariado } from './status-toggle-voluntariado';

describe('StatusToggleVoluntariado', () => {
  let component: StatusToggleVoluntariado;
  let fixture: ComponentFixture<StatusToggleVoluntariado>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [StatusToggleVoluntariado]
    })
    .compileComponents();

    fixture = TestBed.createComponent(StatusToggleVoluntariado);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
