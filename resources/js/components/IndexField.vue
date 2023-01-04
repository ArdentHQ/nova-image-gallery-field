<template>
    <div class="image-gallery-field">
        <div v-if="count > 0" class="relative w-8 rounded overflow-hidden h-8">
            <img :src="thumbnail" class="object-cover w-full h-full" />

            <div
                class="bg-primary-500 text-white text-xs leading-none font-semibold absolute inline-flex items-center justify-center w-4 h-4 rounded-full right-0 bottom-0 m-1"
            >
                {{ count }}
            </div>
        </div>
        <div v-else class="w-8 h-8 bg-gray-50 rounded"></div>
    </div>
</template>

<script>
export default {
    props: ["resourceName", "field"],
    computed: {
        count() {
            return this.field.value && this.field.value.length
                ? this.field.value.length
                : 0;
        },
        thumbnail() {
            return this.field.value && this.field.value.length
                ? this.field.value[0].url
                : "";
        },
    },
};
</script>
