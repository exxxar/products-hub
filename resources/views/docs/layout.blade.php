<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Агрегатор товаров - Документация')</title>
    <meta name="description" content="@yield('description', 'Система управления товарами и workspace')">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        }
        .smooth-scroll {
            scroll-behavior: smooth;
        }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased text-gray-900 bg-white smooth-scroll">
<!-- Navigation -->
<nav class="fixed top-0 left-0 right-0 bg-white/80 backdrop-blur-md border-b border-gray-200 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-cube text-2xl text-primary-600"></i>
                <span class="font-bold text-xl">Агрегатор товаров</span>
            </div>
            <div class="hidden md:flex items-center gap-6">
                <a href="#features" class="text-gray-600 hover:text-primary-600 transition">Возможности</a>
                <a href="#workspaces" class="text-gray-600 hover:text-primary-600 transition">Workspace</a>
                <a href="#integrations" class="text-gray-600 hover:text-primary-600 transition">Интеграции</a>
                <a href="#api" class="text-gray-600 hover:text-primary-600 transition">API</a>
                <a href="{{ route('workspace.main') }}" class="px-4 py-2 text-primary-600 border border-primary-600 rounded-lg hover:bg-primary-50 transition">Войти</a>
            </div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main class="pt-16">
    @yield('content')
</main>

<!-- Footer -->
<footer class="bg-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <i class="fa-solid fa-cube text-2xl text-primary-500"></i>
                    <span class="font-bold text-xl">Агрегатор товаров</span>
                </div>
                <p class="text-gray-400">Современная система управления товарами и workspace</p>
            </div>
            <div>
                <h4 class="font-semibold mb-4">Разделы</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="#features" class="hover:text-white transition">Возможности</a></li>
                    <li><a href="#workspaces" class="hover:text-white transition">Workspace</a></li>
                    <li><a href="#integrations" class="hover:text-white transition">Интеграции</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-4">Контакты</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><i class="fa-solid fa-envelope mr-2"></i> support@example.com</li>
                    <li><i class="fa-solid fa-phone mr-2"></i> +7 (999) 123-45-67</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; {{ date('Y') }} Агрегатор товаров. Все права защищены.</p>
        </div>
    </div>
</footer>

@stack('scripts')
</body>
</html>
