<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded shadow-md max-w-md w-full text-center">
        <h2 class="text-2xl font-bold mb-4">Reset Password</h2>
        
        <p class="mb-6 text-gray-600">
            Mencoba membuka aplikasi...<br>
            Jika aplikasi tidak terbuka otomatis, silakan lanjutkan di web atau klik tombol di bawah.
        </p>

        <a id="btn-open-app" href="#" class="block w-full bg-indigo-600 text-white py-2 rounded mb-3">
            Buka di Aplikasi
        </a>

        <hr class="my-4">
        <p class="text-sm text-gray-500 mb-2">Atau reset melalui web:</p>
        
        <form action="{{ url('/api/reset-password') }}" method="POST" id="web-reset-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">
            
            <div class="mb-4 text-left">
                <label class="block text-sm font-medium text-gray-700">Password Baru</label>
                <input type="password" name="password" required class="mt-1 block w-full border border-gray-300 rounded p-2">
            </div>

            <div class="mb-4 text-left">
                <label class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" required class="mt-1 block w-full border border-gray-300 rounded p-2">
            </div>

            <button type="submit" class="w-full bg-gray-500 text-white py-2 rounded hover:bg-gray-600">
                Reset di Web
            </button>
        </form>
    </div>

    <script>
        // --- KONFIGURASI DEEP LINK ---
        // Ganti dengan scheme aplikasi Anda (info ini dari developer mobile)
        // Format: scheme://host?params
        const appScheme = "mycoolapp://reset-password?token={{ $token }}&email={{ $email }}";
        
        // Link Download Playstore/AppStore (Opsional)
        const storeLink = "https://play.google.com/store/apps/details?id=com.mycoolapp";

        // Set href tombol manual
        document.getElementById('btn-open-app').href = appScheme;

        // --- LOGIKA AUTO REDIRECT ---
        window.onload = function() {
            // 1. Coba buka aplikasi segera setelah halaman dimuat
            window.location.href = appScheme;

            // Logika tambahan (Opsional):
            // Kita bisa mendeteksi jika user masih di halaman ini setelah beberapa detik,
            // berarti aplikasi gagal terbuka (belum diinstall).
            setTimeout(function() {
                console.log("Aplikasi sepertinya tidak terinstall atau gagal dibuka.");
                // Anda bisa menyembunyikan loader atau menampilkan pesan tambahan di sini
            }, 2000);
        };
    </script>
</body>
</html>