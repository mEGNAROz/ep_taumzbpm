package com.example.trgovinaapp

import android.content.Intent
import android.os.Bundle
import android.util.Log
import android.widget.Button
import android.widget.EditText
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class LoginActivity : AppCompatActivity() {

    private lateinit var emailInput: EditText
    private lateinit var passwordInput: EditText
    private lateinit var loginButton: Button

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_login)

        emailInput = findViewById(R.id.emailInput)
        passwordInput = findViewById(R.id.passwordInput)
        loginButton = findViewById(R.id.loginButton)

        loginButton.setOnClickListener {
            val email = emailInput.text.toString().trim()
            val password = passwordInput.text.toString().trim()

            if (email.isEmpty() || password.isEmpty()) {
                Toast.makeText(this, "Vnesite e-pošto in geslo", Toast.LENGTH_SHORT).show()
            } else {
                loginUser(email, password)
            }
        }
    }

    private fun loginUser(email: String, geslo: String) {
        val apiService = ApiClient.retrofit.create(ApiService::class.java)

        val loginRequest = LoginRequest(email, geslo) // Popravek za ime polja (geslo -> password)
        val call = apiService.loginUser(loginRequest)

        call.enqueue(object : Callback<LoginResponse> {
            override fun onResponse(call: Call<LoginResponse>, response: Response<LoginResponse>) {

                if (response.isSuccessful && response.body()?.success == true) {
                    val loginResponse = response.body()
                    Log.d("LoginResponse", "Prijava uspešna: $loginResponse")

                    val sharedPreferences = getSharedPreferences("UserPrefs", MODE_PRIVATE)
                    val editor = sharedPreferences.edit()
                    editor.putBoolean("loggedIn", true)
                    editor.putString("email", email)
                    editor.putBoolean("loggedIn", true)
                    editor.putInt("id", loginResponse?.id ?: 0)              // Shranjen ID
                    editor.putString("vloga", loginResponse?.vloga ?: "")    // Shranjena vloga
                    editor.putString("ime", loginResponse?.ime ?: "")        // Shranjeno ime
                    editor.putString("priimek", loginResponse?.priimek ?: "")// Shranjen priimek
                    editor.putString("email", loginResponse?.email ?: "")    // Shranjen email
                    editor.apply()

                    startActivity(Intent(this@LoginActivity, MainActivity::class.java))
                    finish()
                } else {
                    Toast.makeText(this@LoginActivity, "Nepravilni podatki", Toast.LENGTH_SHORT).show()
                }
            }

            override fun onFailure(call: Call<LoginResponse>, t: Throwable) {
                Toast.makeText(this@LoginActivity, "Napaka: ${t.message}", Toast.LENGTH_SHORT).show()
                Log.e("API_ERROR", "Napaka: ${t.message}")
            }
        })
    }
}
