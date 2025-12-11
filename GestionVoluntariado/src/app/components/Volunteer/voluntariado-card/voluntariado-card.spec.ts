import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VoluntariadoCard } from './voluntariado-card';

describe('VoluntariadoCard', () => {
  let component: VoluntariadoCard;
  let fixture: ComponentFixture<VoluntariadoCard>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [VoluntariadoCard]
    })
    .compileComponents();

    fixture = TestBed.createComponent(VoluntariadoCard);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
