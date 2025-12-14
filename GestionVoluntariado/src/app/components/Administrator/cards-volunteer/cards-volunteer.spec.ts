import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CardsVolunteer } from './cards-volunteer';

describe('CardsVolunteer', () => {
  let component: CardsVolunteer;
  let fixture: ComponentFixture<CardsVolunteer>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CardsVolunteer]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CardsVolunteer);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
