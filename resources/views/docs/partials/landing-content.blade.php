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
            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-layer-group text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Workspace</h3>
                <p class="text-gray-600">Создавайте неограниченное количество досок. Быстрое переключение между ними.</p>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-box text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Товары</h3>
                <p class="text-gray-600">Полное управление товарами: название, описание, цены, изображения, категории.</p>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-tags text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Категории</h3>
                <p class="text-gray-600">Иерархическая структура категорий. Шаблоны для быстрого старта.</p>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-boxes-stacked text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Коллекции</h3>
                <p class="text-gray-600">Группируйте товары в коллекции с гибкими правилами формирования.</p>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-lock text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Защита данных</h3>
                <p class="text-gray-600">Мастер-код для защиты редактирования. Rate limiting после неудачных попыток.</p>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-clock-rotate-left text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">История действий</h3>
                <p class="text-gray-600">Полное логирование всех действий. Кто, что и когда изменил.</p>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-bolt text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Webhooks</h3>
                <p class="text-gray-600">Настройка вебхуков для внешних систем. Автоматическая синхронизация.</p>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-mobile-screen text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">PWA приложение</h3>
                <p class="text-gray-600">Установка на устройство. Работа офлайн. Push-уведомления.</p>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:border-primary-500 hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-users text-2xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Онлайн пользователи</h3>
                <p class="text-gray-600">Отслеживание кто сейчас в системе. Real-time обновления.</p>
            </div>
        </div>
    </div>
</section>

<!-- Integrations -->
<section id="integrations" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Интеграции</h2>
            <p class="text-gray-600 text-lg">Подключайте внешние системы для автоматизации</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:shadow-lg transition">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mb-4">
                    <i class="fa-brands fa-vk text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">ВКонтакте</h3>
                <p class="text-gray-600 mb-4">Синхронизация товаров с группами VK.</p>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Экспорт товаров</li>
                    <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Синхронизация цен</li>
                    <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Несколько групп</li>
                </ul>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:shadow-lg transition">
                <div class="w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center mb-4">
                    <i class="fa-solid fa-utensils text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">IIKO</h3>
                <p class="text-gray-600 mb-4">Интеграция с системой IIKO для ресторанов.</p>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><i class="fa-solid fa-check text-green-500 mr-2"></i>API подключение</li>
                    <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Синхронизация меню</li>
                    <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Обновление остатков</li>
                </ul>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-gray-200 hover:shadow-lg transition">
                <div class="w-16 h-16 bg-purple-600 rounded-2xl flex items-center justify-center mb-4">
                    <i class="fa-solid fa-mobile-screen text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">FrontPad</h3>
                <p class="text-gray-600 mb-4">Подключение к системе FrontPad.</p>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><i class="fa-solid fa-check text-green-500 mr-2"></i>REST API</li>
                    <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Безопасное соединение</li>
                    <li><i class="fa-solid fa-check text-green-500 mr-2"></i>Real-time данные</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20 bg-gradient-to-r from-primary-600 to-purple-600 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">Готовы начать?</h2>
        <p class="text-xl mb-8 opacity-90">Присоединяйтесь к тысячам пользователей</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('workspace.main') }}" class="px-8 py-4 bg-white text-primary-600 rounded-xl font-semibold hover:bg-gray-100 transition shadow-lg">
                <i class="fa-solid fa-rocket mr-2"></i>
                Начать бесплатно
            </a>
        </div>
    </div>
</section>
