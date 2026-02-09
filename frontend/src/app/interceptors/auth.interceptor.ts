import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const authService = inject(AuthService); // Inject AuthService if needed, or just access localStorage directly
  const token = localStorage.getItem('user_token');

  // Exclude public endpoints if needed, but 'Bearer null' or no header is fine if we just check token existence
  // The logic below ensures we don't send Authorization if no token.
  // We explicitly want to send it for profile, but not for public login/register if we want strictness,
  // though sending a potentially old token to login is usually ignored by backend.
  
  // Public endpoints list - simplified
  const publicEndpoints = ['/auth/login', '/auth/register', '/auth/forgot-password'];
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
};
