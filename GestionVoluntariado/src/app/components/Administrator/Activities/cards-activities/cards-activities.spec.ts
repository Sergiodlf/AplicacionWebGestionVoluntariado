import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CardsActivities } from './cards-activities';

describe('CardsActivities', () => {
  let component: CardsActivities;
  let fixture: ComponentFixture<CardsActivities>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CardsActivities]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CardsActivities);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
