import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VolunteerCard } from './volunteer-card';

describe('VolunteerCard', () => {
  let component: VolunteerCard;
  let fixture: ComponentFixture<VolunteerCard>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [VolunteerCard]
    })
    .compileComponents();

    fixture = TestBed.createComponent(VolunteerCard);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
