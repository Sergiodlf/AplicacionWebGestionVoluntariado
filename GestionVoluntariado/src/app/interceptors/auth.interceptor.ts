import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { catchError, switchMap } from 'rxjs/operators';
import { throwError, from, Observable } from 'rxjs';
import { NotificationService } from '../services/notification.service';

export const authInterceptor: HttpInterceptorFn = (req, next) => {

  const token = localStorage.getItem('user_token');
  const router = inject(Router);
  const authService = inject(AuthService);
  const notificationService = inject(NotificationService);

  const publicEndpoints = ['/auth/login', '/auth/register', '/auth/forgot-password', '/auth/refresh-token'];
  const isPublic = publicEndpoints.some(endpoint => req.url.includes(endpoint));

  let reqToForward = req;

  if (!isPublic && token) {
    reqToForward = req.clone({
      headers: req.headers.set('Authorization', `Bearer ${token}`)
    });
  }

  return next(reqToForward).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401 && !isPublic) {
        // Try to silently refresh the token before logging out
        return from(authService.refreshToken()).pipe(
          switchMap(() => {
            // Retry the original request with the new token
            const newToken = localStorage.getItem('user_token');
            const retryReq = req.clone({
              headers: req.headers.set('Authorization', `Bearer ${newToken}`)
            });
            return next(retryReq);
          }),
          catchError((refreshError) => {
            // Refresh failed too — force logout
            authService.logout().then(() => {
              router.navigate(['/login']);
            });
            notificationService.showError('Tu sesión ha expirado. Por favor, inicia sesión de nuevo.');
            return throwError(() => refreshError);
          })
        );
      }
      return throwError(() => error);
    })
  );
};
