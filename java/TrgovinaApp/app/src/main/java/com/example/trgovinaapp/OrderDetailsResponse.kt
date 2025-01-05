package com.example.trgovinaapp

data class OrderDetailsResponse(
    val status: String,
    val order: OrderInfo,
    val items: List<OrderItem>
)

data class OrderInfo(
    val id: Int,
    val datum_oddaje: String,
    val skupna_cena: Double,
    val status: String
)

data class OrderItem(
    val naziv: String,
    val kolicina: Int,
    val cena_na_kos: Double
)
