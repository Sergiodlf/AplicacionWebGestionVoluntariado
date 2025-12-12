import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CrearVoluntariadoModal } from './crear-voluntariado-modal';

describe('CrearVoluntariadoModal', () => {
  let component: CrearVoluntariadoModal;
  let fixture: ComponentFixture<CrearVoluntariadoModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CrearVoluntariadoModal]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CrearVoluntariadoModal);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
