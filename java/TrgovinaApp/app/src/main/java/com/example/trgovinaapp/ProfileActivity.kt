package com.example.trgovinaapp

import android.content.Intent
import android.content.SharedPreferences
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class ProfileActivity : AppCompatActivity() {

    // UI elementi
    private lateinit var imeInput: EditText
    private lateinit var priimekInput: EditText
    private lateinit var emailInput: EditText
    private lateinit var ulicaInput: EditText
    private lateinit var hisnaStevilkaInput: EditText
    private lateinit var postaInput: EditText
    private lateinit var postnaStevilkaInput: EditText
    private lateinit var editButton: Button
    private lateinit var saveButton: Button
    private lateinit var logoutButton : Button

    // SharedPreferences za shranjevanje uporabniških podatkov
    private lateinit var sharedPreferences: SharedPreferences

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_profile)

        // Inicializacija UI elementov
        imeInput = findViewById(R.id.imeInput)
        priimekInput = findViewById(R.id.priimekInput)
        emailInput = findViewById(R.id.emailInput)
        ulicaInput = findViewById(R.id.ulicaInput)
        hisnaStevilkaInput = findViewById(R.id.hisnaStevilkaInput)
        postaInput = findViewById(R.id.postaInput)
        postnaStevilkaInput = findViewById(R.id.postnaStevilkaInput)
        editButton = findViewById(R.id.editButton)
        saveButton = findViewById(R.id.saveButton)
        logoutButton = findViewById(R.id.logoutButton)

        // Nastavi začetno stanje - polja so zaklenjena
        toggleEditable(false)

        // Naloži podatke o profilu
        loadProfileData()

        // Omogoči urejanje podatkov
        editButton.setOnClickListener {
            toggleEditable(true)
        }

        // Shrani spremembe
        saveButton.setOnClickListener {
            updateProfileData()
        }

        // Odjava
        logoutButton.setOnClickListener {
            val sharedPreferences = getSharedPreferences("UserPrefs", MODE_PRIVATE)
            val editor = sharedPreferences.edit()
            editor.clear()
            editor.apply()
            startActivity(Intent(this, LoginActivity::class.java))
            finish()
        }
    }

    private fun toggleEditable(enabled: Boolean) {
        imeInput.isEnabled = enabled
        priimekInput.isEnabled = enabled
        emailInput.isEnabled = enabled
        ulicaInput.isEnabled = enabled
        hisnaStevilkaInput.isEnabled = enabled
        postaInput.isEnabled = enabled
        postnaStevilkaInput.isEnabled = enabled
    }

    private fun loadProfileData() {
        sharedPreferences = getSharedPreferences("UserPrefs", MODE_PRIVATE)
        val id = sharedPreferences.getInt("id", 0)
        val vloga = sharedPreferences.getString("vloga", "") ?: ""

        val apiService = ApiClient.retrofit.create(ApiService::class.java)
        val call = apiService.getProfile(id, vloga)

        call.enqueue(object : Callback<ProfileResponse> {
            override fun onResponse(call: Call<ProfileResponse>, response: Response<ProfileResponse>) {
                if (response.isSuccessful && response.body() != null) {
                    val profile = response.body()
                    imeInput.setText(profile?.ime)
                    priimekInput.setText(profile?.priimek)
                    emailInput.setText(profile?.email)
                    ulicaInput.setText(profile?.ulica)
                    hisnaStevilkaInput.setText(profile?.hisna_stevilka)
                    postaInput.setText(profile?.posta)
                    postnaStevilkaInput.setText(profile?.postna_stevilka)
                } else {
                    Toast.makeText(this@ProfileActivity, "Napaka pri pridobivanju podatkov.", Toast.LENGTH_SHORT).show()
                }
            }

            override fun onFailure(call: Call<ProfileResponse>, t: Throwable) {
                Toast.makeText(this@ProfileActivity, "Napaka: ${t.message}", Toast.LENGTH_SHORT).show()
            }
        })
    }

    private fun updateProfileData() {
        sharedPreferences = getSharedPreferences("UserPrefs", MODE_PRIVATE)
        val id = sharedPreferences.getInt("id", 0)
        val vloga = sharedPreferences.getString("vloga", "") ?: ""

        val updatedProfile = ProfileRequest(
            id,
            vloga,
            imeInput.text.toString(),
            priimekInput.text.toString(),
            emailInput.text.toString(),
            ulicaInput.text.toString(),
            hisnaStevilkaInput.text.toString(),
            postaInput.text.toString(),
            postnaStevilkaInput.text.toString()
        )

        val apiService = ApiClient.retrofit.create(ApiService::class.java)
        val call = apiService.updateProfile(updatedProfile)

        call.enqueue(object : Callback<UpdateResponse> {
            override fun onResponse(call: Call<UpdateResponse>, response: Response<UpdateResponse>) {
                if (response.isSuccessful) {
                    val updateResponse = response.body()
                    if (updateResponse?.status == "success") { // Tukaj je ključno preverjanje!
                        Toast.makeText(this@ProfileActivity, updateResponse.message, Toast.LENGTH_SHORT).show()
                        toggleEditable(false) // Zakleni polja po uspešni posodobitvi
                    } else {
                        Toast.makeText(this@ProfileActivity, "Napaka pri shranjevanju podatkov: ${updateResponse?.message}", Toast.LENGTH_SHORT).show()
                    }
                } else {
                    Toast.makeText(this@ProfileActivity, "Napaka pri shranjevanju podatkov.", Toast.LENGTH_SHORT).show()
                }
            }
            override fun onFailure(call: Call<UpdateResponse>, t: Throwable) {
                Toast.makeText(this@ProfileActivity, "Napaka: ${t.message}", Toast.LENGTH_SHORT).show()
            }
        })

    }
}
