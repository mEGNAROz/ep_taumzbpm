package com.example.trgovinaapp

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.ImageView
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import com.bumptech.glide.Glide

class ArtikelDetailActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_artikel_detail)

        // Pridobi podatke iz Intenta
        val naziv = intent.getStringExtra("naziv") ?: "Ni podatka"
        val opis = intent.getStringExtra("opis") ?: "Ni opisa"
        val cena = intent.getStringExtra("cena") ?: "0.00"
        val ocena = intent.getStringExtra("povprecna_ocena") ?: "Ni ocen"
        val slika = intent.getStringExtra("slika") ?: ""

        // Poveži komponente
        val detailNaziv: TextView = findViewById(R.id.detailNaziv)
        val detailOpis: TextView = findViewById(R.id.detailOpis)
        val detailCena: TextView = findViewById(R.id.detailCena)
        val detailOcena: TextView = findViewById(R.id.detailOcena)
        val detailSlika: ImageView = findViewById(R.id.detailSlika)

        // Nastavi podatke
        detailNaziv.text = naziv
        detailOpis.text = opis
        detailCena.text = "$cena €"
        detailOcena.text = "Ocena: $ocena"

        // Prikaz slike
        Glide.with(this)
            .load(slika) // URL slike
            .placeholder(R.drawable.placeholder_image) // Nadomestna slika
            .error(R.drawable.placeholder_image) // V primeru napake
            .into(detailSlika)
    }
}
