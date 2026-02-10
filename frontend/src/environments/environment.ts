// @ts-ignore - window.env puede estar definido en runtime
const windowEnv = (window as any).env || {};

export const environment = {
  production: false,
  // La URL de la API se configura según el entorno:
  // - En Docker: usa el nombre del servicio backend-web
  // - En desarrollo local: usa localhost:8000
  apiUrl: windowEnv.API_URL || 'http://localhost:8000/api',
  firebase: {
    apiKey: "AIzaSyCc_oGUxB14FPY_w-4_hAXQdhtbFqpq1Hc",
    authDomain: "proyecto-voluntariado-9c2d5.firebaseapp.com",
    projectId: "proyecto-voluntariado-9c2d5",
    storageBucket: "proyecto-voluntariado-9c2d5.firebasestorage.app",
    messagingSenderId: "738090619738",
    appId: "1:738090619738:web:df4cc9721b75f647181963",
    measurementId: "G-2DVRZCY7G0"
  }
};
