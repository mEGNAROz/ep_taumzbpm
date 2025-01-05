package com.example.trgovinaapp

import retrofit2.Call
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {
    @GET("get_artikli.php?action=artikli")
    fun getArtikli(): Call<List<Artikel>>

    @POST("get_artikli.php?action=login")
    fun loginUser(@Body loginRequest: LoginRequest): Call<LoginResponse>

    @GET("get_artikli.php?action=profil")
    fun getProfile(
        @Query("id") id: Int,
        @Query("vloga") vloga: String
    ): Call<ProfileResponse>


    @POST("get_artikli.php?action=profil")
    fun updateProfile(
        @Body profileRequest: ProfileRequest
    ): Call<UpdateResponse>

    // Pridobi vsebino košarice za določenega uporabnika
    @GET("get_artikli.php?action=kosarica")
    fun getCart(
        @Query("id") userId: Int
    ): Call<List<CartItem>>

    // Posodobi artikel v košarici (spremeni količino)
    @POST("get_artikli.php?action=posodobi_kosarico")
    fun updateCartItem(@Body body: HashMap<String, Any>): Call<CheckoutResponse>


    @POST("get_artikli.php?action=zakljuci_nakup")
    fun checkout(@Body body: HashMap<String, Int>): Call<CheckoutResponse>

    @GET("get_artikli.php?action=orders")
    fun getOrders(
        @Query("id") userId: Int
    ): Call<List<Order>>


    @GET("get_artikli.php?action=orders_details")
    fun getOrderDetails(
        @Query("id") orderId: Int
    ): Call<OrderDetailsResponse>
}
