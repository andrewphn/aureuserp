<div
    x-data="{
        images: $wire.entangle('inspiration_images'),
        showModal: false,
        currentIndex: null,
        uploading: false,

        openModal(index = null) {
            this.currentIndex = index;
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.currentIndex = null;
        },

        previousImage() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
            }
        },

        nextImage() {
            if (this.currentIndex < this.images.length - 1) {
                this.currentIndex++;
            }
        },

        getCurrentImage() {
            return this.images[this.currentIndex] || {};
        },

        updateMetadata(field, value) {
            if (this.currentIndex !== null) {
                this.images[this.currentIndex][field] = value;
            }
        },

        deleteImage(index) {
            if (confirm('Are you sure you want to delete this image?')) {
                this.images.splice(index, 1);
                if (this.currentIndex === index) {
                    this.closeModal();
                }
            }
        }
    }"
    class="space-y-4"
>
    <!-- Upload Section -->
    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center">
        <div class="space-y-2">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="flex text-sm text-gray-600 dark:text-gray-400">
                <label class="relative cursor-pointer rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none">
                    <span>Upload inspiration images</span>
                    <input
                        type="file"
                        class="sr-only"
                        multiple
                        accept="image/*"
                        wire:model="inspirationImageUploads"
                        x-on:change="uploading = true"
                    >
                </label>
                <p class="pl-1">or drag and drop</p>
            </div>
            <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
        </div>

        <div x-show="uploading" class="mt-4">
            <div class="text-sm text-gray-600">Uploading...</div>
        </div>
    </div>

    <!-- Gallery Grid -->
    <div x-show="images && images.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        <template x-for="(image, index) in images" :key="index">
            <div class="relative group cursor-pointer" @click="openModal(index)">
                <img
                    :src="image.url || (image.image ? '/storage/' + image.image : '')"
                    :alt="image.title || 'Inspiration image'"
                    class="w-full h-48 object-cover rounded-lg border-2 border-gray-200 dark:border-gray-700 group-hover:border-primary-500 transition"
                >
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition rounded-lg flex items-center justify-center">
                    <svg class="h-8 w-8 text-white opacity-0 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <div x-show="image.title" class="absolute bottom-2 left-2 right-2 bg-black bg-opacity-75 text-white text-sm px-2 py-1 rounded">
                    <span x-text="image.title"></span>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="!images || images.length === 0" class="text-center py-12 text-gray-500">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="mt-2">No inspiration images yet</p>
        <p class="text-sm">Upload images to get started</p>
    </div>

    <!-- Lightbox Modal -->
    <div
        x-show="showModal"
        x-cloak
        @keydown.escape.window="closeModal()"
        @keydown.arrow-left.window="previousImage()"
        @keydown.arrow-right.window="nextImage()"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex min-h-screen items-center justify-center p-4">
            <!-- Backdrop -->
            <div
                class="fixed inset-0 bg-black/75 transition-opacity"
                @click="closeModal()"
            ></div>

            <!-- Modal Content -->
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-7xl w-full max-h-[90vh] overflow-hidden">
                <!-- Close Button -->
                <button
                    @click="closeModal()"
                    class="absolute top-4 right-4 z-10 p-2 rounded-full bg-white dark:bg-gray-700 shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Navigation Arrows -->
                <button
                    @click="previousImage()"
                    x-show="currentIndex > 0"
                    class="absolute left-4 top-1/2 -translate-y-1/2 z-10 p-3 rounded-full bg-white dark:bg-gray-700 shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <button
                    @click="nextImage()"
                    x-show="images && currentIndex < images.length - 1"
                    class="absolute right-4 top-1/2 -translate-y-1/2 z-10 p-3 rounded-full bg-white dark:bg-gray-700 shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Content Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 h-[90vh]">
                    <!-- Left: Image Viewer -->
                    <div class="flex items-center justify-center bg-gray-900 p-8">
                        <template x-if="getCurrentImage().url || getCurrentImage().image">
                            <img
                                :src="getCurrentImage().url || '/storage/' + getCurrentImage().image"
                                :alt="getCurrentImage().title || 'Inspiration image'"
                                class="max-h-full max-w-full object-contain"
                            >
                        </template>
                    </div>

                    <!-- Right: Metadata Form -->
                    <div class="overflow-y-auto p-8 space-y-6">
                        <h3 class="text-2xl font-semibold mb-4">Image Details</h3>

                        <!-- This section will be replaced with Filament form fields -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Title</label>
                                <input
                                    type="text"
                                    :value="getCurrentImage().title"
                                    @input="updateMetadata('title', $event.target.value)"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    placeholder="e.g., Kitchen Island Design"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Customer Comments</label>
                                <textarea
                                    :value="getCurrentImage().customer_comments"
                                    @input="updateMetadata('customer_comments', $event.target.value)"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    rows="3"
                                    placeholder="What they like about this..."
                                ></textarea>
                            </div>

                            <!-- More metadata fields will go here -->

                            <div class="pt-4 border-t flex justify-between">
                                <button
                                    @click="deleteImage(currentIndex)"
                                    type="button"
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                                >
                                    Delete Image
                                </button>

                                <button
                                    @click="closeModal()"
                                    type="button"
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                                >
                                    Done
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
