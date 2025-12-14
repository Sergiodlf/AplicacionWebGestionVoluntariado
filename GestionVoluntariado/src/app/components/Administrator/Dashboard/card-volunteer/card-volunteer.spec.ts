import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CardVolunteer } from './card-volunteer';

describe('CardVolunteer', () => {
  let component: CardVolunteer;
  let fixture: ComponentFixture<CardVolunteer>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CardVolunteer]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CardVolunteer);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
