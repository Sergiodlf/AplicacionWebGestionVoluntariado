export interface ProfileResponse {
    tipo: 'voluntario' | 'organizacion';
    datos: any; // Raw data to be casted later
}
