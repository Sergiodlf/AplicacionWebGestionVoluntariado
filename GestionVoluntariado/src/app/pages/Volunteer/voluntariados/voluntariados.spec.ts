import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Voluntariados } from './voluntariados';

describe('Voluntariados', () => {
  let component: Voluntariados;
  let fixture: ComponentFixture<Voluntariados>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Voluntariados]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Voluntariados);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
