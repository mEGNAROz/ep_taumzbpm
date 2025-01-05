package com.example.trgovinaapp

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class OrdersActivity : AppCompatActivity() {

    private lateinit var recyclerView: RecyclerView
    private lateinit var ordersAdapter: OrdersAdapter
    private var ordersList = mutableListOf<Order>()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_orders)

        recyclerView = findViewById(R.id.ordersRecyclerView)
        recyclerView.layoutManager = LinearLayoutManager(this)
        ordersAdapter = OrdersAdapter(ordersList) { order ->
            // Klik na naročilo za prikaz podrobnosti
            val intent = Intent(this, OrderDetailsActivity::class.java)
            intent.putExtra("orderId", order.id)
            startActivity(intent)
        }
        recyclerView.adapter = ordersAdapter

        loadOrders()
    }

    private fun loadOrders() {
        val apiService = ApiClient.retrofit.create(ApiService::class.java)
        val userId = getSharedPreferences("UserPrefs", MODE_PRIVATE).getInt("id", 0)

        val call = apiService.getOrders(userId)
        call.enqueue(object : Callback<List<Order>> {
            override fun onResponse(call: Call<List<Order>>, response: Response<List<Order>>) {
                if (response.isSuccessful && response.body() != null) {
                    ordersList.clear()
                    ordersList.addAll(response.body()!!)
                    ordersAdapter.notifyDataSetChanged()
                } else {
                    Toast.makeText(this@OrdersActivity, "Napaka pri nalaganju naročil.", Toast.LENGTH_SHORT).show()
                }
            }

            override fun onFailure(call: Call<List<Order>>, t: Throwable) {
                Toast.makeText(this@OrdersActivity, "Napaka: ${t.message}", Toast.LENGTH_SHORT).show()
            }
        })
    }
}
