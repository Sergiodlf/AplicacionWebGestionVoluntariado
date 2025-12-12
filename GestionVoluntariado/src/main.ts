import { ApplicationConfig, bootstrapApplication } from '@angular/platform-browser';
import { routes } from './app/app.routes'; // Importar 'routes' desde app.routes
import { App } from './app/app';
import { provideHttpClient } from '@angular/common/http';
import { provideRouter } from '@angular/router';

export const appConfig: ApplicationConfig = {
  providers: [
    provideHttpClient(), 
    
    provideRouter(routes) 
    
  ]
};

bootstrapApplication(App, appConfig)
  .catch((err) => console.error(err));
