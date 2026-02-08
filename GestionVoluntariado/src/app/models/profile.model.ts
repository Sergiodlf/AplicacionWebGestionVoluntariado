export interface ProfileResponse {
    tipo: 'voluntario' | 'organizacion' | 'admin';
    datos: any; // Raw data to be casted later
}
