<template>
    <div
        v-bind:class="{'border-danger':product.in_stop_list}"
        class="card h-100 position-relative">

        <!-- Чекбокс выбора -->
        <div class="position-absolute top-0 end-0 p-2">
            <input
                type="checkbox"
                class="form-check-input"
                :checked="selected"
                @change="$emit('toggle-select')"
            >
        </div>

        <div class="position-absolute top-0 start-0 p-0">
            <button type="button"
                    class="btn btn-link text-white"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" @click="$emit('edit')">Просмотр</a></li>
                <li><a class="dropdown-item" href="#" @click="$emit('edit')">Редактировать</a></li>
                <li><a class="dropdown-item" href="#" @click="$emit('edit')">Дублировать</a></li>
                <li><a class="dropdown-item" href="#">Удалить</a></li>

            </ul>
        </div>

        <img
            @click="$emit('edit')"
            v-if="product.images[0]"
            v-lazy="product.images[0].url"
            class="card-img-top"
            style="object-fit: cover; height: 120px;"
        >



<!--        <template v-if="product.images.length===0">

        </template>
        <template v-else-if="product.images.length>1">
            <div :id="'product-image-'+product.id" class="carousel slide">
                <div class="carousel-inner">
                    <div
                        v-for="(img, index) in product.images"
                        v-bind:class="{'active':index===0}"
                        class="carousel-item ">
                        <img :src="img" class="d-block w-100" alt="...">
                    </div>
                </div>
                <button class="carousel-control-prev" type="button"
                        :data-bs-target="'#product-image-'+product.id"
                        data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" :data-bs-target="'#roduct-image-'+product.id"
                        data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </template>-->


        <div v-else-if="product.images.length===0" class="bg-light d-flex align-items-center justify-content-center" style="height: 120px;">
            <span class="text-muted">Нет фото</span>
        </div>

        <!-- Текст -->
        <div class="card-body p-2">
            <h6 class="card-title mb-1">{{ product.name }}</h6>
            <p class="mb-0 small">Цена
                <template v-if="product.old_price==0">
                    <span>{{product.price}} руб.</span>
                </template>
                <template v-else>
                    <span>{{product.price}} руб.</span>
                    <span class="text-decoration-line-through small">{{product.old_price}} руб.</span>
                    (<span class="fw-bold text-danger">-{{product.old_price - product.price}} руб.</span>)
                </template>

                </p>
            <p class="fw-bold mb-2">
                <span class="badge bg-info small" v-if="product.external_source==='vk'">Вконтакте</span>
                <span class="badge bg-info small" v-if="product.external_source==='iiko'">IIKO</span>
                <span class="badge bg-info small" v-if="product.external_source==='excel'">Таблица</span>
            </p>
        </div>

    </div>
</template>

<script>


export default {
    name: 'ProductCard',

    props: {
        product: Object,
        selected: Boolean
    },
    mounted() {

    }
}
</script>
