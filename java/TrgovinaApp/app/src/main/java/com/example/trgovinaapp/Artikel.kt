package com.example.trgovinaapp

data class Artikel(
    val id: String,
    val naziv: String,
    val opis: String,
    val cena: String,
    val povprecna_ocena: String,
    val slike: List<String> // Dodan seznam slik
)
