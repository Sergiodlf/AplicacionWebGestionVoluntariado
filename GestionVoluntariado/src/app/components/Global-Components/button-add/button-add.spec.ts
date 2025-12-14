import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ButtonAdd } from './button-add';

describe('ButtonAdd', () => {
  let component: ButtonAdd;
  let fixture: ComponentFixture<ButtonAdd>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ButtonAdd]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ButtonAdd);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
