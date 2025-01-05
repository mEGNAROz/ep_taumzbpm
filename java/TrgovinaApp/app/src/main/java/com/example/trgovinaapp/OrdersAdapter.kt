package com.example.trgovinaapp

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

class OrdersAdapter(
    private val orders: List<Order>,
    private val onItemClick: (Order) -> Unit
) : RecyclerView.Adapter<OrdersAdapter.OrderViewHolder>() {

    class OrderViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val orderNumber: TextView = view.findViewById(R.id.orderNumber)
        val orderDate: TextView = view.findViewById(R.id.orderDate)
        val orderTotalPrice: TextView = view.findViewById(R.id.orderTotalPrice)
        val orderStatus: TextView = view.findViewById(R.id.orderStatus) // Novo
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): OrderViewHolder {
        val view = LayoutInflater.from(parent.context).inflate(R.layout.item_order, parent, false)
        return OrderViewHolder(view)
    }

    override fun onBindViewHolder(holder: OrderViewHolder, position: Int) {
        val order = orders[position]
        holder.orderNumber.text = "Naročilo #${order.id}"
        holder.orderDate.text = order.date
        holder.orderTotalPrice.text = "${order.totalPrice} €"
        holder.orderStatus.text = "Status: ${order.status}" // Novo

        holder.itemView.setOnClickListener {
            onItemClick(order)
        }
    }

    override fun getItemCount(): Int = orders.size
}

