package com.example.appgestionvoluntariado.Models;

import com.google.gson.annotations.SerializedName;

public class UserProfile<T> {
    @SerializedName("tipo")
    private String tipo;

    @SerializedName("datos")
    private T datos;

    public String getTipo() {
        return tipo;
    }

    public void setTipo(String tipo) {
        this.tipo = tipo;
    }

    public T getDatos() {
        return datos;
    }

    public void setDatos(T datos) {
        this.datos = datos;
    }
}
