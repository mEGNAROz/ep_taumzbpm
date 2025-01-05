package com.example.trgovinaapp

import com.google.gson.annotations.SerializedName

data class Order(
    val id: Int,
    val date: String,
    val totalPrice: Double,
    val status: String // Dodano polje
)

