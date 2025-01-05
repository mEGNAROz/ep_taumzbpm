package com.example.trgovinaapp

data class ProfileRequest(
    val id: Int,
    val vloga: String,
    val ime: String,
    val priimek: String,
    val email: String,
    val ulica: String,
    val hisna_stevilka: String,
    val posta: String,
    val postna_stevilka: String
    )