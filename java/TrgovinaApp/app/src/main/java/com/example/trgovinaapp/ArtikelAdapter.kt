package com.example.trgovinaapp

import android.content.Intent
import android.util.Log
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

class ArtikelAdapter(private val artikliList: List<Artikel>) :
    RecyclerView.Adapter<ArtikelAdapter.ArtikelViewHolder>() {

    class ArtikelViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        val naziv: TextView = itemView.findViewById(R.id.naziv)
        val opis: TextView = itemView.findViewById(R.id.opis)
        val cena: TextView = itemView.findViewById(R.id.cena)
        val ocena: TextView = itemView.findViewById(R.id.ocena) // Dodano
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ArtikelViewHolder {
        val view = LayoutInflater.from(parent.context).inflate(R.layout.artikel_item, parent, false)
        return ArtikelViewHolder(view)
    }

    override fun onBindViewHolder(holder: ArtikelViewHolder, position: Int) {
        val artikel = artikliList[position]
        holder.naziv.text = artikel.naziv
        holder.opis.text = artikel.opis
        holder.cena.text = "${artikel.cena} €"
        holder.ocena.text = "Ocena: ${artikel.povprecna_ocena}" // Prikaz ocene

        // Klik za prikaz podrobnosti
        holder.itemView.setOnClickListener {
            val intent = Intent(holder.itemView.context, ArtikelDetailActivity::class.java)
            intent.putExtra("naziv", artikel.naziv)
            intent.putExtra("opis", artikel.opis)
            intent.putExtra("cena", artikel.cena)
            intent.putExtra("povprecna_ocena", artikel.povprecna_ocena)
            intent.putExtra("slika", artikel.slike.firstOrNull() ?: "") // Privzeta slika, če ni slike
            Log.d("ArtikelAdapter", "Slika artikla: ${artikel.slike.firstOrNull() ?: "Ni slike"}")
            holder.itemView.context.startActivity(intent)
        }
    }

    override fun getItemCount(): Int {
        val size = artikliList.size
        if (size == 0) {
            Log.d("ArtikelAdapter", "Seznam artiklov je prazen!")
        }
        return size
    }

}
