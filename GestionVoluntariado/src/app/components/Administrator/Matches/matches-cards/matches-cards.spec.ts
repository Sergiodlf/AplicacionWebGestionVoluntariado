import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MatchesCards } from './matches-cards';

describe('MatchesCards', () => {
  let component: MatchesCards;
  let fixture: ComponentFixture<MatchesCards>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MatchesCards]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MatchesCards);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
