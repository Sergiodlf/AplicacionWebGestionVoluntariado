import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CardActivity } from './card-activity';

describe('CardActivity', () => {
  let component: CardActivity;
  let fixture: ComponentFixture<CardActivity>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CardActivity]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CardActivity);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
