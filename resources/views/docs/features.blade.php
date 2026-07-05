@extends('docs.layout')

@section('title', 'Возможности системы - Документация')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header -->
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Возможности системы</h1>
            <p class="text-xl text-gray-600">Подробное описание всех функций и возможностей</p>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar Navigation -->
            <div class="lg:col-span-1">
                <nav class="sticky top-24 space-y-2">
                    <a href="#workspace" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Workspace</a>
                    <a href="#products" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Товары</a>
                    <a href="#categories" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Категории</a>
                    <a href="#collections" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Коллекции</a>
                    <a href="#security" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Безопасность</a>
                    <a href="#integrations" class="block px-4 py-2 rounded-lg hover:bg-gray-100 transition">Интеграции</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-3 space-y-16">
                <!-- Workspace -->
                <section id="workspace" class="scroll-mt-24">
                    <h2 class="text-3xl font-bold mb-6 flex items-center gap-3">
                        <i class="fa-solid fa-layer-group text-primary-600"></i>
                        Workspace
                    </h2>
                    <div class="prose prose-lg max-w-none text-gray-600">
                        <p class="mb-4">
                            Workspace — это ваша рабочая область, где вы управляете товарами, категориями и коллекциями.
                            Вы можете создавать неограниченное количество workspace и быстро переключаться между ними.
                        </p>

                        <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Основные возможности:</h3>
                        <ul class="space-y-2 mb-6">
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Создание неограниченного количества досок</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Настройка цвета, логотипа и меток</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Связывание досок для быстрого переключения</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Горячие клавиши (Ctrl+K)</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Экспорт/импорт данных</li>
                        </ul>

                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
                            <p class="text-sm text-blue-800">
                                <i class="fa-solid fa-lightbulb mr-2"></i>
                                <strong>Совет:</strong> Используйте связывание досок, чтобы быстро переключаться между связанными проектами.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Products -->
                <section id="products" class="scroll-mt-24">
                    <h2 class="text-3xl font-bold mb-6 flex items-center gap-3">
                        <i class="fa-solid fa-box text-primary-600"></i>
                        Товары
                    </h2>
                    <div class="prose prose-lg max-w-none text-gray-600">
                        <p class="mb-4">
                            Полноценное управление товарами с поддержкой всех необходимых полей: название, описание,
                            цены, изображения, категории, атрибуты и многое другое.
                        </p>

                        <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Функции:</h3>
                        <ul class="space-y-2 mb-6">
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Массовое редактирование</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Импорт из Excel</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Экспорт в Excel</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Загрузка изображений</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Управление остатками</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Система скидок</li>
                        </ul>
                    </div>
                </section>

                <!-- More sections... -->
            </div>
        </div>
    </div>
@endsection
