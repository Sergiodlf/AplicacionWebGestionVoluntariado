import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StatusNavegation } from './status-navegation';

describe('StatusNavegation', () => {
  let component: StatusNavegation;
  let fixture: ComponentFixture<StatusNavegation>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [StatusNavegation]
    })
    .compileComponents();

    fixture = TestBed.createComponent(StatusNavegation);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
