import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MisVoluntariados } from './mis-voluntariados';

describe('MisVoluntariados', () => {
  let component: MisVoluntariados;
  let fixture: ComponentFixture<MisVoluntariados>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MisVoluntariados]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MisVoluntariados);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
