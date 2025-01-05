plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
    id("kotlin-kapt")
}

android {
    namespace = "com.example.trgovinaapp"
    compileSdk = 35

    defaultConfig {
        applicationId = "com.example.trgovinaapp"
        minSdk = 21
        targetSdk = 35
        versionCode = 1
        versionName = "1.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }
    kotlinOptions {
        jvmTarget = "11"
    }
    buildFeatures {
        compose = true
    }
}

dependencies {

    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.lifecycle.runtime.ktx)
    implementation(libs.androidx.activity.compose)
    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.ui)
    implementation(libs.androidx.ui.graphics)
    implementation(libs.androidx.ui.tooling.preview)
    implementation(libs.androidx.material3)
    implementation(libs.androidx.appcompat)
    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)
    androidTestImplementation(platform(libs.androidx.compose.bom))
    androidTestImplementation(libs.androidx.ui.test.junit4)
    debugImplementation(libs.androidx.ui.tooling)
    debugImplementation(libs.androidx.ui.test.manifest)
    // Retrofit za omrežno komunikacijo
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")

    // OkHttp za HTTP klice
    implementation("com.squareup.okhttp3:okhttp:4.9.1")

    // Gson za delo z JSON
    implementation("com.google.code.gson:gson:2.8.8")

    // RecyclerView za prikaz seznama
    implementation("androidx.recyclerview:recyclerview:1.2.1")

    // CardView za oblikovanje seznama
    implementation("androidx.cardview:cardview:1.0.0")
    // AndroidX knjižnice
    implementation("androidx.appcompat:appcompat:1.6.1")
    implementation("androidx.core:core-ktx:1.10.1")
    implementation("androidx.recyclerview:recyclerview:1.2.1")
    implementation("com.google.android.material:material:1.8.0")

    // Retrofit za omrežno komunikacijo
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")

    // OkHttp za HTTP klice
    implementation("com.squareup.okhttp3:okhttp:4.9.0")

    // Gson za delo z JSON
    implementation("com.google.code.gson:gson:2.8.8")
    implementation("com.github.bumptech.glide:glide:4.15.1")
    kapt("com.github.bumptech.glide:compiler:4.15.1")

    implementation("com.squareup.okhttp3:logging-interceptor:4.9.3")
    implementation("com.squareup.okhttp3:logging-interceptor:4.9.3")
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")
    implementation ("com.google.code.gson:gson:2.8.9")

}


