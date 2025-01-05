package com.example.trgovinaapp

import com.google.gson.annotations.SerializedName

data class CartItem(
    @SerializedName("id") val id: Int,
    @SerializedName("naziv") val name: String,
    @SerializedName("cena") val price: Double,
    @SerializedName("kolicina") var quantity: Int
)
