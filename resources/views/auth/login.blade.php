<!DOCTYPE html>
<html lang="tr">
<head>
    <script>try { if (localStorage.getItem('aysha_theme') === 'dark') document.documentElement.classList.add('dark'); } catch (e) {}</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen flex items-center justify-center p-4" style="background: radial-gradient(1200px 600px at 10% -10%, #dfeefb 0%, transparent 60%), radial-gradient(900px 500px at 100% 110%, #b9dcf6 0%, transparent 55%), #f4f7fb;">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex items-center justify-center gap-3 text-slate-900">
            
            <div class="leading-tight">
                <div class="flex items-center justify-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-xl text-white" style="background: linear-gradient(135deg, #3573fb 0%, #1557d6 100%);"><i class="fa-solid fa-building"></i></span><span class="text-left leading-none"><span class="block text-lg font-bold tracking-tight text-slate-900">TTB Turizm</span><span class="mt-1 block text-xs font-medium text-slate-500">Personel Yönetimi</span></span></div>
                
            </div>
        </div>

        <div class="card p-7 shadow-pop">
            <h1 class="mb-4 text-lg font-semibold text-slate-900">Giriş Yap</h1>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label" for="login">Kullanıcı adı veya e-posta</label>
                    <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username" class="form-input" placeholder="ayse">
                </div>
                <div>
                    <label class="form-label" for="password">Şifre</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="form-input">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600"> Beni hatırla
                </label>
                <button type="submit" class="btn-primary w-full"><i class="fa-solid fa-right-to-bracket"></i> Giriş</button>
            </form>
        </div>
    </div>
</body>
</html>
