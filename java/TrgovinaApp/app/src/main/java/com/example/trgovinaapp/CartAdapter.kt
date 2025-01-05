package com.example.trgovinaapp

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Button
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

class CartAdapter(
    private val items: MutableList<CartItem>,
    private val updateQuantity: (CartItem, Int) -> Unit
) : RecyclerView.Adapter<CartAdapter.CartViewHolder>() {

    class CartViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val nameText: TextView = view.findViewById(R.id.cartItemName)
        val priceText: TextView = view.findViewById(R.id.cartItemPrice)
        val quantityText: TextView = view.findViewById(R.id.cartItemQuantity)
        val addButton: Button = view.findViewById(R.id.addButton)
        val removeButton: Button = view.findViewById(R.id.removeButton)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): CartViewHolder {
        val view = LayoutInflater.from(parent.context).inflate(R.layout.item_cart, parent, false)
        return CartViewHolder(view)
    }

    override fun onBindViewHolder(holder: CartViewHolder, position: Int) {
        val item = items[position]
        holder.nameText.text = item.name
        holder.priceText.text = "${String.format("%.2f", item.price)} €"
        holder.quantityText.text = item.quantity.toString()


        holder.addButton.setOnClickListener {
            updateQuantity(item, item.quantity + 1)
        }

        holder.removeButton.setOnClickListener {
            if (item.quantity > 1) {
                updateQuantity(item, item.quantity - 1)
            }
        }
    }
    fun updateItems(newItems: List<CartItem>) {
        items.clear()
        items.addAll(newItems)
        notifyDataSetChanged()
    }

    override fun getItemCount(): Int = items.size
}
