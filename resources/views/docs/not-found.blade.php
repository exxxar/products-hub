@extends('docs.layout')

@section('title', 'Страница не найдена')

@section('content')
    <!-- Уведомление о 404 -->
    <div class="bg-gradient-to-r from-red-50 to-orange-50 border-b border-red-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-compass text-2xl text-red-600"></i>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">
                        Страница не найдена
                    </h2>
                    <p class="text-gray-600 text-sm mb-3">
                        Похоже, вы перешли по несуществующему адресу. Но не переживайте —
                        ниже вы найдёте всю информацию о нашей системе.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('docs.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition">
                            <i class="fa-solid fa-house"></i>
                            На главную
                        </a>
                        <a href="{{ route('workspace.main') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                            Войти в систему
                        </a>
                    </div>
                </div>
                <div class="hidden sm:block">
                    <div class="text-6xl font-bold text-red-200">404</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Основной контент лендинга -->
    @include('docs.partials.landing-content')
@endsection
