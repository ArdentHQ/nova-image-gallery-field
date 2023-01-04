<template>
    <PanelItem :index="index" :field="field">
        <template #value>
            <div
                class="image-gallery-field"
                v-if="field.value && field.value.length"
            >
                {{ current }}
                <light-box :index="selected" :images="images" />
                <ul class="flex flex-wrap image-list -mt-4">
                    <FieldImage
                        v-for="(image, index) in images"
                        :image="image"
                        :key="image.id"
                        readonly
                        @click="select(index)"
                    />
                </ul>
            </div>
            <div class="image-gallery-field" v-else>-</div>
        </template>
    </PanelItem>
</template>

<script>
import FieldImage from "./FieldImage.vue";
import LightBox from "./LightBox.vue";

export default {
    props: ["index", "resource", "resourceName", "resourceId", "field"],
    components: {
        FieldImage,
        LightBox,
    },
    data() {
        return {
            selected: null,
        };
    },
    computed: {
        images() {
            return Array.from(this.field.value);
        },
    },
    methods: {
        select(index) {
            this.selected = index;
        },
    },
};
</script>
