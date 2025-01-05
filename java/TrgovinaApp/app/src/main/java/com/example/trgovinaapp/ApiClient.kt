import okhttp3.OkHttpClient
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import com.google.gson.GsonBuilder

object ApiClient {
    // Osnovni URL do strežnika
    private const val BASE_URL = "http://10.0.2.2:8080/netbeans/Seminarska_vaje/public/"

    // Pripravi Gson parser z Lenient načinom (sprejema "malformed" JSON)
    private val gson = GsonBuilder()
        .setLenient()
        .create()
    val client = OkHttpClient.Builder()
        .addInterceptor { chain ->
            val request = chain.request().newBuilder()
                .addHeader("Cache-Control", "no-cache")
                .build()
            chain.proceed(request)
        }
        .build()
    // Ustvari Retrofit instanco
    val retrofit: Retrofit = Retrofit.Builder()
        .baseUrl(BASE_URL)
        .addConverterFactory(GsonConverterFactory.create(gson)) // Gson pretvornik
        .client(client) // Dodaj OkHttpClient
        .build()
}
