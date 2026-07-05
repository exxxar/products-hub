@extends('docs.layout')

@section('title', 'Агрегатор товаров - Система управления workspace')
@section('description', 'Современная система управления товарами, категориями и workspace с поддержкой интеграций')

@section('content')
    <!-- Hero Section -->
    <section class="relative overflow-hidden py-20 bg-gradient-to-b from-primary-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-primary-100 text-primary-700 rounded-full text-sm font-medium mb-6">
                <i class="fa-solid fa-sparkles"></i>
                <span>Современная PWA платформа</span>
            </div>
            <h1 class="text-5xl md:text-6xl font-bold mb-6">
                Управление <span class="gradient-text">товарами</span><br>
                нового поколения
            </h1>
            <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                Создавайте workspace, управляйте товарами, категориями и коллекциями.
                Интеграции с VK, IIKO, FrontPad. Быстрое переключение между досками.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('workspace.main') }}" class="px-8 py-4 bg-primary-600 text-white rounded-xl font-semibold hover:bg-primary-700 transition shadow-lg hover:shadow-xl">
                    <i class="fa-solid fa-arrow-right-to-bracket mr-2"></i>
                    Начать работу
                </a>
                <a href="#features" class="px-8 py-4 bg-white text-primary-600 border-2 border-primary-600 rounded-xl font-semibold hover:bg-primary-50 transition">
                    <i class="fa-solid fa-book mr-2"></i>
                    Документация
                </a>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section id="features" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Возможности системы</h2>
                <p class="text-gray-600 text-lg">Всё необходимое для управления товарами в одном месте</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Workspace -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                    <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-layer-group text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Workspace</h3>
                    <p class="text-gray-600">Создавайте неограниченное количество досок. Быстрое переключение между ними. Связывание досок для удобной навигации.</p>
                </div>

                <!-- Products -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                    <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-box text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Товары</h3>
                    <p class="text-gray-600">Полное управление товарами: название, описание, цены, изображения, категории. Импорт/экспорт в Excel.</p>
                </div>

                <!-- Categories -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                    <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-tags text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Категории</h3>
                    <p class="text-gray-600">Иерархическая структура категорий. Шаблоны категорий для быстрого старта. Drag-and-drop сортировка.</p>
                </div>

                <!-- Collections -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                    <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-boxes-stacked text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Коллекции</h3>
                    <p class="text-gray-600">Группируйте товары в коллекции. Гибкие правила формирования. Автоматический расчёт цены.</p>
                </div>

                <!-- Master Code -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                    <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-lock text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Защита данных</h3>
                    <p class="text-gray-600">Мастер-код для защиты редактирования. Rate limiting. Блокировка после неудачных попыток.</p>
                </div>

                <!-- Activity Log -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                    <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-clock-rotate-left text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">История действий</h3>
                    <p class="text-gray-600">Полное логирование всех действий. Кто, что и когда изменил. Фильтрация и поиск.</p>
                </div>

                <!-- Webhooks -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                    <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-bolt text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Webhooks</h3>
                    <p class="text-gray-600">Настройка вебхуков для внешних систем. Автоматическая синхронизация. Мониторинг статусов.</p>
                </div>

                <!-- PWA -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                    <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-mobile-screen text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">PWA приложение</h3>
                    <p class="text-gray-600">Установка на устройство. Работа офлайн. Push-уведомления. Быстрый запуск.</p>
                </div>

                <!-- Online Users -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                    <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                        <i class="fa-solid fa-users text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Онлайн пользователи</h3>
                    <p class="text-gray-600">Отслеживание кто сейчас в системе. Real-time обновления. Присутствие в workspace.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Workspaces Section -->
    <section id="workspaces" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold mb-6">Управление workspace</h2>
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-plus text-primary-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-1">Создание досок</h3>
                                <p class="text-gray-600">Быстрое создание workspace с настройкой цвета, логотипа и меток</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-link text-primary-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-1">Связывание досок</h3>
                                <p class="text-gray-600">Объединяйте доски в группы для быстрого переключения. Двусторонняя связь.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-shuffle text-primary-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-1">Быстрое переключение</h3>
                                <p class="text-gray-600">Горячие клавиши Ctrl+K. Выпадающий список всех связанных досок.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-lg">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 p-4 bg-primary-50 rounded-lg border-2 border-primary-500">
                            <div class="w-10 h-10 bg-primary-500 rounded-lg flex items-center justify-center text-white font-bold">МС</div>
                            <div class="flex-1">
                                <div class="font-semibold">Магазин одежды</div>
                                <div class="text-sm text-gray-600">Текущая доска</div>
                            </div>
                            <i class="fa-solid fa-check text-primary-600"></i>
                        </div>
                        <div class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center text-white font-bold">МО</div>
                            <div class="flex-1">
                                <div class="font-semibold">Магазин обуви</div>
                                <div class="text-sm text-gray-600">Связанная доска</div>
                            </div>
                            <i class="fa-solid fa-arrow-right text-gray-400"></i>
                        </div>
                        <div class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center text-white font-bold">МА</div>
                            <div class="flex-1">
                                <div class="font-semibold">Магазин аксессуаров</div>
                                <div class="text-sm text-gray-600">Связанная доска</div>
                            </div>
                            <i class="fa-solid fa-arrow-right text-gray-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Integrations Section -->
    <section id="integrations" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Интеграции</h2>
                <p class="text-gray-600 text-lg">Подключайте внешние системы для автоматизации</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- VK -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:shadow-lg transition">
                    <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mb-4">
                        <i class="fa-brands fa-vk text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">ВКонтакте</h3>
                    <p class="text-gray-600 mb-4">Синхронизация товаров с группами VK. Автоматическая публикация.</p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Экспорт товаров</li>
                        <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Синхронизация цен</li>
                        <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Несколько групп</li>
                    </ul>
                </div>

                <!-- IIKO -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:shadow-lg transition">
                    <div class="w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center mb-4">
                        <i class="fa-solid fa-utensils text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">IIKO</h3>
                    <p class="text-gray-600 mb-4">Интеграция с системой IIKO для ресторанов и кафе.</p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li><i class="fa-solid fa-check text-green-500 mr-2"></i>API подключение</li>
                        <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Синхронизация меню</li>
                        <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Обновление остатков</li>
                    </ul>
                </div>

                <!-- FrontPad -->
                <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:shadow-lg transition">
                    <div class="w-16 h-16 bg-purple-600 rounded-2xl flex items-center justify-center mb-4">
                        <i class="fa-solid fa-mobile-screen text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">FrontPad</h3>
                    <p class="text-gray-600 mb-4">Подключение к системе FrontPad для автоматизации.</p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li><i class="fa-solid fa-check text-green-500 mr-2"></i>REST API</li>
                        <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Безопасное соединение</li>
                        <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Real-time данные</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- API Section -->
    <section id="api" class="py-20 bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold mb-6">REST API</h2>
                    <p class="text-gray-400 mb-8 text-lg">Полноценный API для интеграции с внешними системами. Все endpoints документированы.</p>

                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 bg-green-600 rounded text-sm font-mono">GET</span>
                            <code class="text-gray-300">/api/workspaces</code>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 bg-blue-600 rounded text-sm font-mono">POST</span>
                            <code class="text-gray-300">/api/workspaces/{uuid}/products</code>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 bg-yellow-600 rounded text-sm font-mono">PUT</span>
                            <code class="text-gray-300">/api/workspaces/{uuid}/products/{id}</code>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 bg-red-600 rounded text-sm font-mono">DELETE</span>
                            <code class="text-gray-300">/api/workspaces/{uuid}/products/{id}</code>
                        </div>
                    </div>

                    <a href="/api/documentation" class="inline-flex items-center gap-2 mt-8 px-6 py-3 bg-primary-600 rounded-lg hover:bg-primary-700 transition">
                        <i class="fa-solid fa-book"></i>
                        Полная документация API
                    </a>
                </div>

                <div class="bg-gray-800 p-6 rounded-2xl font-mono text-sm">
                    <div class="flex items-center gap-2 mb-4 pb-4 border-b border-gray-700">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="ml-2 text-gray-400">Example Request</span>
                    </div>
                    <pre class="text-gray-300 overflow-x-auto"><code>curl -X GET https://api.example.com/workspaces \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

Response:
{
  "data": [
    {
      "id": 1,
      "uuid": "abc123...",
      "name": "Магазин одежды",
      "products_count": 156,
      "created_at": "2026-06-29T12:00:00Z"
    }
  ]
}</code></pre>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-primary-600 to-purple-600 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Готовы начать?</h2>
            <p class="text-xl mb-8 opacity-90">Присоединяйтесь к тысячам пользователей, которые уже управляют своими товарами эффективно</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="px-8 py-4 bg-white text-primary-600 rounded-xl font-semibold hover:bg-gray-100 transition shadow-lg">
                    <i class="fa-solid fa-rocket mr-2"></i>
                    Начать бесплатно
                </a>
                <a href="#features" class="px-8 py-4 bg-transparent text-white border-2 border-white rounded-xl font-semibold hover:bg-white/10 transition">
                    <i class="fa-solid fa-circle-info mr-2"></i>
                    Узнать больше
                </a>
            </div>
        </div>
    </section>
@endsection
