package com.example.trgovinaapp

import android.os.Bundle
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class OrderDetailsActivity : AppCompatActivity() {

    private lateinit var adapter: OrderDetailsAdapter
    private lateinit var orderDateText: TextView
    private lateinit var totalPriceText: TextView
    private lateinit var orderItems: RecyclerView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_order_details)

        orderDateText = findViewById(R.id.orderDateText)
        totalPriceText = findViewById(R.id.totalPriceTextView)
        orderItems = findViewById(R.id.orderItemsRecyclerView)

        orderItems.layoutManager = LinearLayoutManager(this)
        adapter = OrderDetailsAdapter(emptyList())
        orderItems.adapter = adapter

        val orderId = intent.getIntExtra("orderId", -1)
        if (orderId != -1) {
            loadOrderDetails(orderId)
        } else {
            Toast.makeText(this, "Napaka pri pridobivanju ID-ja naročila.", Toast.LENGTH_SHORT).show()
        }
    }

    private fun loadOrderDetails(orderId: Int) {
        val apiService = ApiClient.retrofit.create(ApiService::class.java)
        val call = apiService.getOrderDetails(orderId)

        call.enqueue(object : Callback<OrderDetailsResponse> {
            override fun onResponse(call: Call<OrderDetailsResponse>, response: Response<OrderDetailsResponse>) {
                if (response.isSuccessful && response.body()?.status == "success") {
                    val orderDetails = response.body()
                    orderDateText.text = orderDetails?.order?.datum_oddaje
                    totalPriceText.text = "${orderDetails?.order?.skupna_cena} €"

                    adapter.updateItems(orderDetails?.items ?: emptyList())
                } else {
                    Toast.makeText(this@OrderDetailsActivity, "Napaka pri pridobivanju podrobnosti.", Toast.LENGTH_SHORT).show()
                }
            }

            override fun onFailure(call: Call<OrderDetailsResponse>, t: Throwable) {
                Toast.makeText(this@OrderDetailsActivity, "Napaka: ${t.message}", Toast.LENGTH_SHORT).show()
            }
        })
    }
}


