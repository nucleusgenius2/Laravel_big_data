<template>
    <div class="wrap-news max">
        <!-- фильтр -->
        <div class="wrap-filter">
            <div class="wrap-field">
                <div class="heading-field text">Новости</div>
                <input class='field-admin' v-model="filter.name">
            </div>

            <div class="wrap-field">
                <div class="heading-field text">Дата публикации от</div>
                <VueDatePicker
                    v-model="filter.created_at_from"
                    :max-date="new Date()"
                    prevent-min-max-navigation
                    model-type="dd.MM.yyyy"
                    auto-apply
                    placeholder="('DD - MM - YYYY')"
                    :enable-time-picker="false"
                    locale="ru"
                    format="dd/MM/yyyy"
                />
            </div>


            <div class="wrap-field">
                <div class="heading-field text">Дата публикации до</div>
                <VueDatePicker
                    v-model="filter.created_at_to"
                    :max-date="new Date()"
                    prevent-min-max-navigation
                    model-type="dd.MM.yyyy"
                    auto-apply
                    placeholder="('DD - MM - YYYY')"
                    :enable-time-picker="false"
                    format="dd/MM/yyyy"
                    locale="ru"
                />
            </div>

            <div class="wrap-field">
                <div class="heading-field text">Выборка</div>
                <select class='field-admin' v-model="filter.date_fixed">
                    <option value="day">новости за день</option>
                    <option value="week">новости за неделю</option>
                    <option value="month">новости за месяц</option>
                    <option value="year">новости за год</option>
                </select>
            </div>

            <div class="wrap-field">
                <div class="heading-field text">Рейтинг</div>
                <select class='field-admin' v-model="filter.rating">
                    <option value="20">20</option>
                    <option value="19">19</option>
                    <option value="18">18</option>
                    <option value="17">17</option>
                    <option value="16">16</option>
                    <option value="15">15</option>
                    <option value="14">14</option>
                    <option value="13">13</option>
                    <option value="12">12</option>
                    <option value="11">11</option>
                    <option value="10">10</option>
                    <option value="9">9</option>
                    <option value="8">8</option>
                    <option value="7">7</option>
                    <option value="6">6</option>
                    <option value="5">5</option>
                    <option value="4">4</option>
                    <option value="3">3</option>
                    <option value="2">2</option>
                    <option value="1">1</option>
                </select>
            </div>

            <div class="wrap-button-submit" style="margin-right:20px;">
                <div class="button-blue-all button-admin"  style="margin-bottom: 10px;" @click="paginationListing('filter')" >
                    Применить фильтр
                </div>
            </div>

            <div class="wrap-button-submit">
                <div class="button-blue-all-style-2 button-admin" style="margin-bottom: 10px;"  @click="clearFilter" >
                    Очистить
                </div>
            </div>
        </div>


        <div class="news-list">
            <div class="post-el" v-for="(post) in arrayPosts">
                <div class="heading-post">{{ post.name }}</div>
                <div class="wrap-section-post">
                    <div class="post-short-text">
                        <div class="content-short-post">{{ post.short_description }}</div>
                    </div>
                </div>
                <div class="rating">Рейтинг: <span>{{ post.rating }}</span></div>
                <div>дата публикации: {{ convertTime(post.created_at) }}</div>
                <a :href="'/posts/'+post.id">Читать далее</a>
            </div>

        </div>

        <pagination v-model="pageModel" :records="pageTotal" :per-page="1" @paginate="paginationListing"/>

        <div class="empty-list" v-if="emptyPage">По вашему запросу не найдено новостей</div>
    </div>
</template>




<script setup>
import {ref} from 'vue';
import {useRoute} from "vue-router";
import {authRequest} from "@/api.js";
import VueDatePicker from "@vuepic/vue-datepicker";
import '@vuepic/vue-datepicker/dist/main.css'
import Pagination from "v-pagination-3";
import {convertTime} from "../script/convertTime.js";

const route = useRoute();
let arrayPosts = ref([]);
let emptyPage = ref(false);
let pageModel = ref(1)
let pageTotal = ref(1)
let filter = ref({
    'name' : '',
    'created_at_from' : '',
    'created_at_to' : '',
    'date_fixed' : '',
    'rating' : '',
});

async function paginationListing(filterClick = '') {
    if (filterClick === 'filter') {
        pageModel.value = 1;
    }
    let stringFilter = '';
    if (filter.value.name !== '') {
        stringFilter += '&name=' + filter.value.name;
    }
    if (filter.value.created_at_from && filter.value.created_at_from !== '') {
        stringFilter += '&created_at_from=' + filter.value.created_at_from;
    }
    if (filter.value.created_at_to && filter.value.created_at_to !== '') {
        stringFilter += '&created_at_to=' + filter.value.created_at_to;
    }
    if (filter.value.date_fixed!== '') {
        stringFilter += '&date_fixed=' + filter.value.date_fixed;
    }
    if (filter.value.rating!== '') {
        stringFilter += '&rating=' + filter.value.rating;
    }

    let response = await authRequest('/api/posts?page=' + pageModel.value + stringFilter, 'get');

    if (response.data.status === 'success') {
        emptyPage.value = false;
        arrayPosts.value = response.data.json['data'];
        pageTotal.value = response.data.json['count'] ?? 1;
    }
    else{
        pageTotal.value = response.data.json['count'] ?? 1;
        arrayPosts.value = []
        emptyPage.value = true;
    }
}
paginationListing();

function clearFilter (){
    filter.value.name = '';
    filter.value.created_at_from = '';
    filter.value.created_at_to = '';
    filter.value.chunk = '';
    paginationListing();
}
</script>


<style scoped>
.post-el {
    display: flex;
    flex-direction: column;
    width: 50%;
    margin-bottom: 30px;
}

.thumb-post img {
    max-width: 100px;
}

.news-list {
    display: flex;
    flex-wrap: wrap;
    margin-top: 30px;
}

.wrap-section-post {
    display: flex;
}

.post-el:nth-child(odd) {
    padding-right: 30px;
}

.post-el:nth-child( even) {
    padding-left: 30px;
}

.post-short-text {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.content-short-post {
    max-width: 400px;
    margin: 10px 0;
}

.heading-post {
    font-size: 20px;
}
.rating span {
    font-weight:600
}
</style>
