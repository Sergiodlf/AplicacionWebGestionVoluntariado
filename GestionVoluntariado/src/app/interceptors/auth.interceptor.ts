import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Auth, idToken } from '@angular/fire/auth';
import { switchMap, take } from 'rxjs/operators';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(Auth);

  return idToken(auth).pipe(
    take(1),
    switchMap((token) => {
      // Exclude only public authentication endpoints from sending the token
      // We must ALLOW /api/auth/profile to send the token as it is protected
      const publicEndpoints = ['/auth/login', '/auth/register', '/auth/forgot-password', '/categories', '/ciclos'];
      const isPublic = publicEndpoints.some(endpoint => req.url.includes(endpoint));

      if (isPublic) {
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
