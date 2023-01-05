<template>
    <li class="relative group mr-4 mt-4 image-list-item w-36">
        <div
            v-if="busy"
            v-cloak
            class="flex items-center justify-center absolute inset-0 transition-default bg-theme-secondary-900 w-full h-full z-10"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                xml:space="preserve"
                class="w-20 h-20 text-white animation-spin duration-1000"
            >
                <circle
                    fill="none"
                    stroke="var(--icon-color-secondary, #E5F0F8)"
                    stroke-width="2"
                    stroke-miterlimit="10"
                    cx="10"
                    cy="10"
                    r="9"
                />
                <path
                    fill="none"
                    stroke="var(--icon-color-primary, #007DFF)"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-miterlimit="10"
                    d="M18.7 7.8c-.7-3-3-5.4-5.8-6.4"
                />
            </svg>
        </div>

        <div
            class="rounded-xl overflow-hidden border-2 h-36"
            :class="{
                'border-transparent': !error,
                'border-red-500': !!error,
                'pointer-events-none opacity-50': busy,
                'cursor-pointer': !busy,
            }"
        >
            <img
                class="object-cover object-center w-full h-full"
                :src="imageUrl"
            />
        </div>

        <span v-if="error" class="text-red-500 text-xs flex">{{ error }}</span>

        <div
            v-if="!readonly && !busy"
            class="absolute inset-0 transition-all ease-in-out duration-300 opacity-0 group-hover:opacity-100"
        >
            <div
                class="select-none rounded-xl flex flex-col items-center justify-center opacity-70 w-full h-full cursor-pointer bg-gray-900"
            >
                <svg
                    class="fill-current w-8 h-8 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 32 32"
                    xml:space="preserve"
                >
                    <path
                        d="M20.3 12.4V11c0-.8-.6-1.4-1.4-1.4s-1.4.6-1.4 1.4v-.7c0-.8-.6-1.4-1.4-1.4-.8 0-1.4.6-1.4 1.4v.7c0-.8-.6-1.4-1.4-1.4-.8 0-1.4.6-1.4 1.4v2.2c-1.6 0-2.9 1.3-2.9 2.9 0 1.4.4 2.8 1.3 3.9l1.1 1.5c.8 1.1 2.1 1.8 3.5 1.8h3.7c1.3 0 2.6-.5 3.5-1.4s1.4-2.2 1.4-3.5v-5.9c0-.8-.6-1.4-1.4-1.4-1.1-.1-1.8.5-1.8 1.3h0zm-8.6.2v2.2m2.9-2.9v-1.4m2.9 1.4v-1.4m1.6-6.4L16 1l-3.1 3.1m6.2 23.8L16 31l-3.1-3.1m-8.8-15L1 16l3.1 3.1m23.8-6.2L31 16l-3.1 3.1"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    ></path>
                </svg>

                <p class="mt-3 text-xs font-semibold text-gray-500">
                    Drag to reposition
                </p>
            </div>

            <button
                type="button"
                data-action=""
                class="absolute top-0 right-0 p-1 -mt-2 -mr-2 rounded cursor-pointer bg-red-100 text-red-500"
                @click="deleteButtonHandler"
            >
                <svg
                    class="fill-current w-4 h-4"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                >
                    <g
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path
                            d="M.954 18.981l18-18M18.954 18.981l-18-18"
                        ></path>
                    </g>
                </svg>
            </button>
        </div>
    </li>
</template>

<script>
export default {
    props: {
        image: {
            type: Object,
            required: true,
        },
        field: {
            type: Object,
            default: undefined,
        },
        readonly: {
            type: Boolean,
            default: false,
        },
    },

    computed: {
        isNewFile() {
            return this.image.new;
        },
        imageUrl() {
            return this.image.url;
        },
        error() {
            return this.image.error;
        },
        busy() {
            return !!this.image.busy;
        },
    },
    methods: {
        deleteButtonHandler() {
            if (this.isNewFile) {
                this.$emit("delete");
            } else {
                this.$emit("mark-for-deletion");
            }
        },
    },
};
</script>
