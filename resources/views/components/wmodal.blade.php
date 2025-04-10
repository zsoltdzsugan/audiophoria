<x-button label="Login to Chat" x-on:click="$openModal('loginModal')" class="w-full rounded-xl bg-primary px-4 py-2 text-sm text-on-primary shadow-md hover:bg-primary-dark transition-colors dark:bg-primary-dark dark:text-on-primary-dark" />
    Login to Chat
</x-button>

<x-modal-card title="Edit Customer" name="loginModal">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-input label="Name" placeholder="Your full name" />

        <x-input label="Phone" placeholder="USA phone" />

        <div class="col-span-1 sm:col-span-2">
            <x-input label="Email" placeholder="example@mail.com" />
        </div>

        <div
            class="flex items-center justify-center col-span-1 bg-gray-100 shadow-md cursor-pointer sm:col-span-2 dark:bg-secondary-700 rounded-xl h-64">
            <div class="flex flex-col items-center justify-center">
                <x-icon name="cloud-arrow-up" class="w-16 h-16 text-blue-600 dark:text-teal-600" />

                <p class="text-blue-600 dark:text-teal-600">Click or drop files here</p>
            </div>
        </div>
    </div>

    <x-slot name="footer" class="flex justify-between gap-x-4">
        <x-button flat negative label="Delete" x-on:click="close" />

        <div class="flex gap-x-4">
            <x-button flat label="Cancel" x-on:click="close" />

            <x-button primary label="Save" wire:click="save" />
        </div>
    </x-slot>
</x-modal-card>
