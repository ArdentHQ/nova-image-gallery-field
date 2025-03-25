<template>
    <DefaultField :field="field" :show-help-text="false" full-width-content>
        <template #field>
            <div class="image-gallery-field">
                <div :class="{ dark: dark }">
                    <div
                        class="rounded-xl p-1.5 border-2 border-dashed border-primary-100 dark:border-gray-700 relative h-48 group"
                    >
                        <input
                            :id="field.attribute"
                            type="file"
                            class="absolute w-full h-full opacity-0 cursor-pointer"
                            @change="validateImage"
                            accept="image/jpg,image/jpeg,image/png,jpg,png"
                            multiple
                        />

                        <div
                            class="flex flex-col justify-center items-center space-y-2 w-full h-full rounded-xl dark:bg-gray-900 bg-primary-50 transition-all ease-in-out duration-300 dark:group-hover:bg-gray-900"
                        >
                            <div class="text-primary-500">
                                <svg
                                    class="fill-current w-8 h-8"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M7.1 12.7l2.9-2.6 3 2.6-3-2.6V16"
                                    ></path>
                                    <path
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15.1 16c2.2 0 3.9-1.7 3.9-3.9s-1.7-3.9-3.9-3.9c-.8 0-1.6.2-2.3.7-.6-3.2-3.7-5.4-6.9-4.8s-5.4 3.7-4.8 7c.6 2.8 3.1 4.9 6 4.9h8z"
                                    ></path>
                                </svg>
                            </div>

                            <div
                                class="font-semibold text-gray-900 dark:text-gray-200"
                            >
                                <span class="hidden md:inline"
                                    >Drag &amp; Drop your files here or
                                </span>
                                <span class="text-primary-500">
                                    Browse Files
                                </span>
                            </div>

                            <div
                                class="flex flex-col space-y-1 text-xs font-semibold text-center sm:flex-row sm:space-y-0 sm:space-x-1 text-gray-500 chunk-header"
                                v-if="field.helpText"
                            >
                                <span>{{ field.helpText }}</span>
                            </div>
                        </div>

                        <div v-show="busy">
                            <div
                                class="cursor-pointer flex items-center justify-center absolute top-0 opacity-90 transition-all ease-in-out duration-300 bg-gray-900 w-full h-full right-0 bottom-0 left-0 rounded-xl"
                            >
                                <svg
                                    class="fill-current w-20 h-20 text-white animation-spin duration-1000"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    xml:space="preserve"
                                >
                                    <circle
                                        fill="none"
                                        stroke="var(--icon-color-secondary, #E5F0F8)"
                                        stroke-width="2"
                                        stroke-miterlimit="10"
                                        cx="10"
                                        cy="10"
                                        r="9"
                                    ></circle>
                                    <path
                                        fill="none"
                                        stroke="var(--icon-color-primary, #007DFF)"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-miterlimit="10"
                                        d="M18.7 7.8c-.7-3-3-5.4-5.8-6.4"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-if="hasImages">
                        <ul class="flex flex-wrap image-list">
                            <FieldImage
                                v-for="(image, index) in visibleImages"
                                :image="image"
                                :key="`${image.id}-${index}`"
                                @delete="deleteImage(image.id)"
                                :field="field"
                                @mark-for-deletion="
                                    markImageForDeletion(image.id)
                                "
                            />
                        </ul>
                    </div>
                </div>
            </div>
        </template>
    </DefaultField>
</template>

<script>
import { FormField, HandlesValidationErrors } from "laravel-nova";
import FieldImage from "./FieldImage.vue";
import uniqid from "uniqid";
import Sortable from "sortablejs";
import Toasted from "toastedjs";

export default {
    components: {
        FieldImage,
    },

    mixins: [FormField, HandlesValidationErrors],

    props: ["resourceName", "resourceId", "field"],

    data: () => ({
        draftId: uuidv4(),
    }),

    methods: {
        setInitialValue() {
            this.value = this.field.value || [];
        },

        fill(formData) {
            this.value.forEach((file, index) => {
                if (file.delete) {
                    formData.append(
                        `${this.field.attribute}_delete[]`,
                        file.id
                    );
                } else if (file.new && !file.error) {
                    formData.append(`${this.field.attribute}[]`, file.id);
                }
            });

            this.validImages.forEach((image) => {
                if (image.error) {
                    return;
                }

                if (image.new) {
                    formData.append(
                        `${this.field.attribute}_order[]`,
                        `new:${image.id}`
                    );
                } else {
                    formData.append(
                        `${this.field.attribute}_order[]`,
                        image.id
                    );
                }
            });
        },

        validateImage(e) {
            const files = Array.from(e.target.files);
            e.target.value = null;

            const orderStart = this.value.length;
            files.forEach(async (file, index) => {
                const data = new FormData();

                data.append("Content-Type", file.type);
                data.append("attachment", file);
                data.append("draftId", this.draftId);

                const fileId = uniqid();

                this.value.push({
                    id: fileId,
                    url: URL.createObjectURL(file),
                    busy: true,
                    order: orderStart + index + 1,
                    new: true,
                });

                try {
                    const response = await Nova.request().post(
                        `/nova-api/${this.resourceName}/field-attachment/${this.field.attribute}`,
                        data
                    );

                    const { url, id } = JSON.parse(response.data);

                    const valueIndex = this.value.findIndex(
                        (v) => v.id === fileId
                    );
                    this.value.splice(valueIndex, 1, {
                        url,
                        id,
                        order: this.value[valueIndex].order,
                        new: true,
                    });
                } catch (data) {
                    const { response } = data;

                    let error;

                    if (response.status === 422) {
                        error = response.data.message;
                    } else {
                        error = "An error occured while uploading your file.";
                    }

                    new Toasted({
                        theme: "nova",
                        position: "bottom-right",
                        duration: 6000,
                    }).show(error, {
                        type: "error",
                    });

                    const valueIndex = this.value.findIndex(
                        (v) => v.id === fileId
                    );
                    this.value[valueIndex].error = error;
                    this.value[valueIndex].busy = false;
                }
            });
        },

        deleteImage(imageId) {
            const index = this.value.findIndex((image) => image.id === imageId);
            this.value.splice(index, 1);
        },

        markImageForDeletion(imageId) {
            const index = this.value.findIndex((image) => image.id === imageId);
            this.value[index]["delete"] = true;
        },

        initSortable() {
            this.sortable = Sortable.create(
                document.querySelector(".image-list"),
                {
                    onSort: (el) => {
                        const { oldIndex, newIndex } = el;

                        this.sortHandler(oldIndex, newIndex);
                    },
                }
            );
        },

        /**
         * Assigns the new order to all elements
         * @param {number} oldIndex
         * @param {number} newIndex
         */
        sortHandler(oldIndex, newIndex) {
            const item = this.visibleImages[oldIndex];
            const images = [...this.visibleImages];
            images.splice(oldIndex, 1);
            images.splice(newIndex, 0, item);

            const newImages = [...images, ...this.deletedImages];

            newImages.forEach((image, index) => {
                image.order = index;
            });

            this.value = newImages;
        },
    },

    computed: {
        hasImages() {
            return this.visibleImages.length > 0;
        },
        sortedValue() {
            const value = [...(this.value || [])];
            value.sort((a, b) => (a.order > b.order ? 1 : -1));

            return value;
        },
        visibleImages() {
            return this.sortedValue.filter((image) => !image.delete);
        },
        validImages() {
            return this.sortedValue.filter(
                (image) => !image.delete && !image.error
            );
        },
        deletedImages() {
            return (this.value || []).filter((image) => image.delete);
        },
    },

    data() {
        return {
            sortable: null,
            busy: false,
            dark: false,
            documentMutationObserver: null,
        };
    },

    watch: {
        hasImages(hasImages) {
            if (hasImages) {
                this.$nextTick(() => {
                    this.initSortable();
                });
            }
        },
    },

    mounted() {
        this.dark = document.documentElement.classList.contains("dark");

        this.documentMutationObserver = new MutationObserver((records) => {
            records.forEach((record) => {
                this.dark = record.target.classList.contains("dark");
            });
        });

        this.documentMutationObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ["class"],
            childList: false,
            characterData: false,
        });
    },

    beforeUnmount() {
        this.documentMutationObserver.disconnect();
        this.documentMutationObserver = null;
    },
};

function uuidv4() {
    return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, (c) =>
        (
            c ^
            (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))
        ).toString(16)
    );
}
</script>
