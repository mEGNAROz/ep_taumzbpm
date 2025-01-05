package com.example.trgovinaapp

import android.content.Intent
import android.os.Bundle
import android.util.Log
import android.widget.Button
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class MainActivity : AppCompatActivity() {

    private lateinit var recyclerView: RecyclerView
    private lateinit var adapter: ArtikelAdapter
    private var artikliList = mutableListOf<Artikel>()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Preveri, ali je uporabnik prijavljen
        val sharedPreferences = getSharedPreferences("UserPrefs", MODE_PRIVATE)
        val loggedIn = sharedPreferences.getBoolean("loggedIn", false)

        // Če ni prijavljen, preusmeri na LoginActivity
        if (!loggedIn) {
            startActivity(Intent(this, LoginActivity::class.java))
            finish() // Prepreči dostop do glavne aktivnosti brez prijave
            return
        }

        // Nastavi glavni prikaz
        setContentView(R.layout.activity_main)

        // Inicializacija RecyclerView
        recyclerView = findViewById(R.id.recyclerView)
        recyclerView.layoutManager = LinearLayoutManager(this)

        adapter = ArtikelAdapter(artikliList)
        recyclerView.adapter = adapter
        Log.d("API_REQUEST", "Pridobivam artikle iz: http://10.0.2.2:8080/netbeans/Seminarska_vaje/public/get_artikli.php?action=artikli")

        // Inicializacija API klica
        val apiService = ApiClient.retrofit.create(ApiService::class.java)

        apiService.getArtikli().enqueue(object : Callback<List<Artikel>> {

            // Uspešen odziv strežnika
            // Uspešen odziv strežnika
            override fun onResponse(call: Call<List<Artikel>>, response: Response<List<Artikel>>) {
                Log.d("API_RESPONSE", "Raw Response: ${response.raw()}")
                Log.d("API_RESPONSE", "Headers: ${response.headers()}")
                Log.d("API_RESPONSE", "Body: ${response.body()}")

                if (response.isSuccessful && response.body() != null) {
                    artikliList.clear()
                    artikliList.addAll(response.body()!!)
                    adapter.notifyDataSetChanged()
                } else {
                    val errorBody = response.errorBody()?.string()
                    Log.e("API_ERROR", "Napaka pri odzivu: $errorBody")
                    Toast.makeText(this@MainActivity, "Napaka pri pridobivanju podatkov!", Toast.LENGTH_SHORT).show()
                }
            }

            // Napaka pri povezavi ali komunikaciji
            override fun onFailure(call: Call<List<Artikel>>, t: Throwable) {
                Log.e("API_ERROR", "Napaka pri povezavi: ${t.message}")
                Toast.makeText(this@MainActivity, "Napaka pri povezavi: ${t.message}", Toast.LENGTH_SHORT).show()
            }
        })


        val logoutButton: Button = findViewById(R.id.logoutButton)
        logoutButton.setOnClickListener {
            val sharedPreferences = getSharedPreferences("UserPrefs", MODE_PRIVATE)
            val editor = sharedPreferences.edit()
            editor.clear() // Počisti shranjene podatke
            editor.apply()

            Log.d("LOGOUT", "Podatki počiščeni, preusmeritev na LoginActivity.")
            val intent = Intent(this, LoginActivity::class.java)
            startActivity(intent)
            finish()
        }
        val cartButton: Button = findViewById(R.id.cartButton)
        cartButton.setOnClickListener {
            val intent = Intent(this, CartActivity::class.java)
            startActivity(intent)
        }

        val profileButton: Button = findViewById(R.id.profileButton)
        profileButton.setOnClickListener {
            val intent = Intent(this, ProfileActivity::class.java)
            startActivity(intent)
        }
        findViewById<Button>(R.id.historyButton).setOnClickListener {
            startActivity(Intent(this, OrdersActivity::class.java))
        }


    }
}
