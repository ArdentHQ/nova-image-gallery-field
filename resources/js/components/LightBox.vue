<template>
    <div
        class="image-gallery-field bg-black bg-opacity-50 fixed pin z-50 flex justify-center items-center inset-0"
        v-if="visible"
        @click="hide"
    >
        <div
            class="absolute right-0 top-0 text-white cursor-pointer text-4xl p-1 mr-3"
            @click.stop="hide"
        >
            &times;
        </div>
        <div class="flex">
            <div
                class="cursor-pointer self-center px-8"
                @click.stop="prev"
                :class="{ invisible: !hasPrev() }"
            >
                <svg
                    class="pointer-events-none"
                    fill="#fff"
                    height="48"
                    viewBox="0 0 24 24"
                    width="48"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M15.41 16.09l-4.58-4.59 4.58-4.59L14 5.5l-6 6 6 6z"
                    />
                    <path d="M0-.5h24v24H0z" fill="none" />
                </svg>
            </div>
            <div class="lightbox-image" @click.stop="">
                <img
                    :src="images[localIndex].url"
                    class="rounded-xl w-auto h-auto"
                />
            </div>
            <div
                class="cursor-pointer self-center px-8"
                @click.stop="next"
                :class="{ invisible: !hasNext() }"
            >
                <svg
                    class="pointer-events-none"
                    fill="#fff"
                    height="48"
                    viewBox="0 0 24 24"
                    width="48"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M8.59 16.34l4.58-4.59-4.58-4.59L10 5.75l6 6-6 6z"
                    />
                    <path d="M0-.25h24v24H0z" fill="none" />
                </svg>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        images: {
            type: Array,
            required: true,
        },
        index: {
            type: Number,
        },
    },
    data() {
        return {
            visible: false,
            localIndex: null,
        };
    },
    watch: {
        index(value) {
            this.localIndex = value;
            this.visible = true;
        },
    },
    methods: {
        show(index) {
            this.visible = true;
            this.localIndex = index;
        },
        hide() {
            this.visible = false;
        },
        prev() {
            if (this.hasPrev()) {
                this.localIndex--;
            }
        },
        next() {
            if (this.hasNext()) {
                this.localIndex++;
            }
        },
        hasNext() {
            return this.localIndex + 1 < this.images.length;
        },
        hasPrev() {
            return this.localIndex - 1 >= 0;
        },
        onKeydown(e) {
            if (this.visible) {
                switch (e.key) {
                    case "ArrowRight":
                        this.next();
                        break;
                    case "ArrowLeft":
                        this.prev();
                        break;
                    case "ArrowDown":
                    case "ArrowUp":
                    case " ":
                        e.preventDefault();
                        break;
                    case "Escape":
                        this.hide();
                        break;
                }
            }
        },
    },
    mounted() {
        window.addEventListener("keydown", this.onKeydown);
    },
    destroyed() {
        window.removeEventListener("keydown", this.onKeydown);
    },
};
</script>

<style>
.lightbox-image img {
    max-width: 100%;
    max-height: calc(100vh - 90px);
}
</style>
