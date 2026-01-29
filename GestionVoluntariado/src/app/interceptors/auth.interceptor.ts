import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Auth, idToken } from '@angular/fire/auth';
import { switchMap, take } from 'rxjs/operators';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(Auth);

  return idToken(auth).pipe(
    take(1),
    switchMap((token) => {
      // Exclude registration endpoints from sending the token
      // This prevents 401 errors if the backend tries to validate a token for a user that doesn't fully exist yet in its DB
      // or if public access is preferred without auth overhead.
      if (req.url.includes('/register/') || req.url.includes('/categories') || req.url.includes('/ciclos')) {
        return next(req);
      }

      if (token) {
        const clonedReq = req.clone({
          headers: req.headers.set('Authorization', `Bearer ${token}`)
        });
        return next(clonedReq);
      }
      return next(req);
    })
  );
};
