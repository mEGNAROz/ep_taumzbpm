package com.example.trgovinaapp

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

class OrderDetailsAdapter(
    private var items: List<OrderItem>
) : RecyclerView.Adapter<OrderDetailsAdapter.OrderDetailsViewHolder>() {

    class OrderDetailsViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val itemName: TextView = view.findViewById(R.id.itemName)
        val itemQuantity: TextView = view.findViewById(R.id.itemQuantity)
        val itemPrice: TextView = view.findViewById(R.id.itemPrice)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): OrderDetailsViewHolder {
        val view = LayoutInflater.from(parent.context).inflate(R.layout.item_order_detail, parent, false)
        return OrderDetailsViewHolder(view)
    }

    override fun onBindViewHolder(holder: OrderDetailsViewHolder, position: Int) {
        val item = items[position]
        holder.itemName.text = item.naziv
        holder.itemQuantity.text = "Količina: ${item.kolicina}"
        holder.itemPrice.text = "${item.cena_na_kos} €"
    }

    override fun getItemCount(): Int = items.size

    fun updateItems(newItems: List<OrderItem>) {
        items = newItems
        notifyDataSetChanged()
    }
}


