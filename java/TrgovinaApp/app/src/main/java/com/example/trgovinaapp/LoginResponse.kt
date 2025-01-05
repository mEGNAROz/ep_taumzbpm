package com.example.trgovinaapp

data class LoginResponse(
    val success: Boolean,  // Dodaj success, ki ga preverja LoginActivity
    val vloga: String?,
    val id: Int,
    val ime: String?,
    val priimek: String?,
    val email: String?
)