package com.example.trgovinaapp

import android.os.Bundle
import android.util.Log
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class CartActivity : AppCompatActivity() {

    private lateinit var recyclerView: RecyclerView
    private lateinit var totalPriceText: TextView
    private lateinit var checkoutButton: Button
    private lateinit var refreshButton: Button
    private lateinit var cartAdapter: CartAdapter
    private var cartItems = mutableListOf<CartItem>()
    private var totalPrice = 0.0

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_cart)

        recyclerView = findViewById(R.id.recyclerViewCart)
        totalPriceText = findViewById(R.id.totalPriceText)
        checkoutButton = findViewById(R.id.checkoutButton)
        refreshButton = Button(this).apply {
            text = "Osveži košarico"
        }
        recyclerView.layoutManager = LinearLayoutManager(this)
        cartAdapter = CartAdapter(cartItems, this::updateCart)
        recyclerView.adapter = cartAdapter

        loadCartData()

        checkoutButton.setOnClickListener {
            performCheckout()
        }
        // Dodaj funkcionalnost gumba "Osveži košarico"
        refreshButton.setOnClickListener {
            loadCartData()
        }
//        val refreshButton: Button = findViewById(R.id.refreshButton)
//        refreshButton.setOnClickListener {
//            loadCartData() // Ponovno naloži podatke
//            Toast.makeText(this, "Košarica osvežena.", Toast.LENGTH_SHORT).show()
//        }

    }

    private fun loadCartData() {
        val apiService = ApiClient.retrofit.create(ApiService::class.java)

        // Pridobitev ID-ja prijavljenega uporabnika
        val sharedPreferences = getSharedPreferences("UserPrefs", MODE_PRIVATE)
        val userId = sharedPreferences.getInt("id", 0)

        val call = apiService.getCart(userId)
        call.enqueue(object : Callback<List<CartItem>> {
            override fun onResponse(call: Call<List<CartItem>>, response: Response<List<CartItem>>) {
                if (response.isSuccessful && response.body() != null) {
                    val newItems = response.body()!!
                    cartAdapter.updateItems(newItems) // Uporabi updateItems
                    calculateTotal() // Preračunaj skupno ceno
//                    Toast.makeText(this@CartActivity, "Količina posodobljena.", Toast.LENGTH_SHORT).show()
//                    loadCartData() // Osveži košarico po posodobitvi
                } else {
                    Toast.makeText(this@CartActivity, "Napaka pri nalaganju košarice.", Toast.LENGTH_SHORT).show()
                }
            }

            override fun onFailure(call: Call<List<CartItem>>, t: Throwable) {
                Toast.makeText(this@CartActivity, "Napaka: ${t.message}", Toast.LENGTH_SHORT).show()
            }
        })
    }

    private fun calculateTotal() {
        totalPrice = cartItems.sumOf { it.price * it.quantity }
        totalPriceText.text = "Skupaj: ${String.format("%.2f", totalPrice)} €"
        cartAdapter.notifyDataSetChanged()
    }

    // Posodobi količino izdelka
    private fun updateCart(item: CartItem, quantity: Int) {
        // Posodobi lokalno količino
        item.quantity = quantity
        calculateTotal()
        val userId = getSharedPreferences("UserPrefs", MODE_PRIVATE).getInt("id", 0)

        // API klic za posodobitev količine na strežniku
        val apiService = ApiClient.retrofit.create(ApiService::class.java)
        val requestBody = hashMapOf<String, Any>(
            "id" to userId,              // ID uporabnika
            "artikel_id" to item.id,     // ID izdelka
            "kolicina" to quantity       // Nova količina
        )

        val call = apiService.updateCartItem(requestBody)
        call.enqueue(object : Callback<CheckoutResponse> {
            override fun onResponse(call: Call<CheckoutResponse>, response: Response<CheckoutResponse>) {
                if (response.isSuccessful && response.body()?.status == "success") {
                    Toast.makeText(this@CartActivity, "Količina posodobljena.", Toast.LENGTH_SHORT).show()
                } else {
                    Toast.makeText(this@CartActivity, "Napaka pri posodabljanju.", Toast.LENGTH_SHORT).show()
                }
            }

            override fun onFailure(call: Call<CheckoutResponse>, t: Throwable) {
                Toast.makeText(this@CartActivity, "Napaka: ${t.message}", Toast.LENGTH_SHORT).show()
            }
        })
    }


    private fun performCheckout() {
        if (cartItems.isEmpty()) {
            Toast.makeText(this, "Košarica je prazna!", Toast.LENGTH_SHORT).show()
            return
        }

        val apiService = ApiClient.retrofit.create(ApiService::class.java)
        val userId = getSharedPreferences("UserPrefs", MODE_PRIVATE).getInt("id", 0)

        // Ustvari telo zahteve
        val requestBody = HashMap<String, Int>()
        requestBody["id"] = userId
//      val requestBody = hashMapOf("id" to userId) // lahko namesto zgornjih dveh vrstic
        val call = apiService.checkout(requestBody)
        call.enqueue(object : Callback<CheckoutResponse> {
            override fun onResponse(call: Call<CheckoutResponse>, response: Response<CheckoutResponse>) {
                if (response.isSuccessful) {
                    try {
                        val body = response.body()
                        if (body?.status == "success") {
                            Toast.makeText(this@CartActivity, "Nakup uspešen!", Toast.LENGTH_SHORT).show()
                            cartItems.clear()
                            calculateTotal()
                        } else {
                            Toast.makeText(this@CartActivity, "Napaka: ${body?.message}", Toast.LENGTH_SHORT).show()
                        }
                    } catch (e: Exception) {
                        Log.d("CheckoutResponse", "Napaka pri parsiranju: ${response.errorBody()?.string()}")
                        Toast.makeText(this@CartActivity, "Napaka pri parsiranju odgovora.", Toast.LENGTH_SHORT).show()
                    }
                } else {
                    Log.d("CheckoutResponse", "Neuspešen odgovor: ${response.errorBody()?.string()}")
                    Toast.makeText(this@CartActivity, "Napaka pri zahtevi.", Toast.LENGTH_SHORT).show()
                }
            }


            override fun onFailure(call: Call<CheckoutResponse>, t: Throwable) {
                Toast.makeText(this@CartActivity, "Napaka: ${t.message}", Toast.LENGTH_SHORT).show()
            }
        })
    }

}
