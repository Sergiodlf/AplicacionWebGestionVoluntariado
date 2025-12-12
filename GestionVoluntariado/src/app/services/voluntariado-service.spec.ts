import { TestBed } from '@angular/core/testing';

import { VoluntariadoService } from './voluntariado-service';

describe('VoluntariadoService', () => {
  let service: VoluntariadoService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(VoluntariadoService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
