<?php

use Livewire\Volt\Component;
use App\Models\Room;
use App\Models\Track;
use Livewire\Attributes\Validate;
use App\Jobs\ProcessMediaUrl;

new class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = "";

    #[Validate('required')]
    public $startTime;

    #[Validate('required|url')]
    public string $mediaUrl = "";

    public function createRoom()
    {
        $this->validate();

        $track = Track::create([
            'media_url' => $this->mediaUrl,
        ]);

        $room = Room::create([
            'track_id' => $track->id,
            'name' => $this->name,
            'start_time' => $this->startTime,
        ]);

        ProcessMediaUrl::dispatch($this->mediaUrl, $room, $track);

        return redirect()->route('rooms.show', $room);
    }

    public function with()
    {
        $rooms = Room::where('is_active', true)
            ->where('end_time', ">", now())
            ->orderBy('start_time', 'asc')
            ->with('track.media')
            ->get();

        return ['rooms' => $rooms];
    }
}; ?>

<div class="w-full min-h-screen">
    <div class="h-1/2 min-h-[50vh]">
        <h1 class="font-cursive text-center text-primary-500 dark:text-primary-600 text-4xl py-2 underline underline-offset-4">audiophoria</h1>
        <div class="w-full h-full flex gap-4 p-4">
            <div class="w-1/3">
                <div class="w-full max-w-xl mx-auto p-4 overflow-hidden">
                    <div class="bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 max-h-[42vh] min-h-[42vh] shadow-sm rounded-xl py-2 px-4">
                        <h3 class="mt-2 text-center text-xl font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 sticky top-0 bg-surface-alt-100 dark:bg-surface-alt-950">Search Rooms</h3>
                        <x-input rounded="xl" icon="magnifying-glass" placeholder="Search for rooms" class="bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 rounded-xl hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all duration 200 focus-within:outline-none shadow-sm my-4" />
                        <div class="max-h-[28.5vh] overflow-y-auto">
                            <a href="#" class="block my-6 pl-2 pr-4">
                                <div class="flex gap-2 bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all ease-in-out duration-200 shadow-sm group p-2 rounded-xl">
                                    <div class="flex flex-shrink-0">
                                        <x-avatar src="https://placehold.co/128" size="w-20 h-20" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                                    </div>
                                    <div class="flex flex-col w-full">
                                        <h4 class="text-md font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">
                                            Name
                                        </h4>
                                        <div class="my-1">
                                            <h3 class="text-sm truncate font-medium">
                                               Title
                                            </h3>
                                            <h5 class="text-xs tracking-tight uppercase">Track</h5>
                                            <div class="text-xs font-medium">
                                                <div>
                                                    Starts in: <span>countdownText</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end items-start">
                                        <x-button flat color="secondary" xs class="group-hover:text-primary-500 dark:group-hover:text-primary-600">
                                            <span class="material-symbols-outlined">
                                            login
                                            </span>
                                        </x-button>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="block my-6 px-2">
                                <div class="flex gap-2 bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all ease-in-out duration-200 shadow-sm group p-2 rounded-xl">
                                    <div class="flex flex-shrink-0">
                                        <x-avatar src="https://placehold.co/128" size="w-20 h-20" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                                    </div>
                                    <div class="flex flex-col w-full">
                                        <h4 class="text-md font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">
                                            Name
                                        </h4>
                                        <div class="my-1">
                                            <h3 class="text-sm truncate font-medium">
                                               Title
                                            </h3>
                                            <h5 class="text-xs tracking-tight uppercase">Track</h5>
                                            <div class="text-xs font-medium">
                                                <div>
                                                    Starts in: <span>countdownText</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end items-start">
                                        <x-button flat color="secondary" xs class="group-hover:text-primary-500 dark:group-hover:text-primary-600">
                                            <span class="material-symbols-outlined">
                                            login
                                            </span>
                                        </x-button>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="block my-6 px-2">
                                <div class="flex gap-2 bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all ease-in-out duration-200 shadow-sm group p-2 rounded-xl">
                                    <div class="flex flex-shrink-0">
                                        <x-avatar src="https://placehold.co/128" size="w-20 h-20" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                                    </div>
                                    <div class="flex flex-col w-full">
                                        <h4 class="text-md font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">
                                            Name
                                        </h4>
                                        <div class="my-1">
                                            <h3 class="text-sm truncate font-medium">
                                               Title
                                            </h3>
                                            <h5 class="text-xs tracking-tight uppercase">Track</h5>
                                            <div class="text-xs font-medium">
                                                <div>
                                                    Starts in: <span>countdownText</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end items-start">
                                        <x-button flat color="secondary" xs class="group-hover:text-primary-500 dark:group-hover:text-primary-600">
                                            <span class="material-symbols-outlined">
                                            login
                                            </span>
                                        </x-button>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-1/3 flex-shrink-0 flex justify-center">
                <div class="w-full max-w-lg flex items-center p-4">
                    <div class="bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 max-h-[42vh] min-h-[42vh] shadow-sm rounded-xl p-4">
                        <h2 class="text-2xl font-bold font-serif text-center text-on-surface-strong-800 dark:text-on-surface-strong-100">Let's listen together!</h2>
                        <form wire:submit='createRoom' class="flex flex-col w-full gap-6 mt-6">
                            <x-input wire:model='name' placeholder="Room Name" rounded="xl" />
                            <x-password wire:model='password' placeholder="Room Password" rounded="xl" />
                            <x-input wire:model='mediaUrl' placeholder="Media URL" rounded="xl" />
                            <div class="-mt-4">
                            <p class="text-xs font-light px-3 -mb-4">Direct Episode Link or YouTube Link, RSS Feeds will grab the latest episode.</p>
                            </div>
                            <x-datetime-picker wire:model='startTime' placeholder="Listening Party Time" rounded="xl" :min="now()" class="h-12" requires-confirmation="true" />
                            <x-button type='submit' primary rounded="xl" class="w-full rounded-xl">Create Room</x-button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="w-1/3">
                <div class="w-full max-w-xl mx-auto p-4 overflow-hidden">
                    <div class="bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 max-h-[42vh] min-h-[42vh] shadow-sm rounded-xl py-2 px-4">
                        <h3 class="mt-2 text-center text-xl font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 sticky top-0 bg-surface-alt-100 dark:bg-surface-alt-950">Your Rooms</h3>
                        <x-input rounded="xl" icon="magnifying-glass" placeholder="Search in rooms" class="bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 rounded-xl hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all duration 200 focus-within:outline-none shadow-sm my-4" />
                        <div class="max-h-[28.5vh] overflow-y-auto">
                            <a href="#" class="block my-6 pl-2 pr-4">
                                <div class="flex gap-2 bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all ease-in-out duration-200 shadow-sm group p-2 rounded-xl">
                                    <div class="flex flex-shrink-0">
                                        <x-avatar src="https://placehold.co/128" size="w-20 h-20" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                                    </div>
                                    <div class="flex flex-col w-full">
                                        <h4 class="text-md font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">
                                            Name
                                        </h4>
                                        <div class="my-1">
                                            <h3 class="text-sm truncate font-medium">
                                               Title
                                            </h3>
                                            <h5 class="text-xs tracking-tight uppercase">Track</h5>
                                            <div class="text-xs font-medium">
                                                <div>
                                                    Starts in: <span>countdownText</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end items-start">
                                        <x-button flat color="secondary" xs class="group-hover:text-primary-500 dark:group-hover:text-primary-600">
                                            <span class="material-symbols-outlined">
                                            login
                                            </span>
                                        </x-button>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="block my-6 px-2">
                                <div class="flex gap-2 bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all ease-in-out duration-200 shadow-sm group p-2 rounded-xl">
                                    <div class="flex flex-shrink-0">
                                        <x-avatar src="https://placehold.co/128" size="w-20 h-20" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                                    </div>
                                    <div class="flex flex-col w-full">
                                        <h4 class="text-md font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">
                                            Name
                                        </h4>
                                        <div class="my-1">
                                            <h3 class="text-sm truncate font-medium">
                                               Title
                                            </h3>
                                            <h5 class="text-xs tracking-tight uppercase">Track</h5>
                                            <div class="text-xs font-medium">
                                                <div>
                                                    Starts in: <span>countdownText</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end items-start">
                                        <x-button flat color="secondary" xs class="group-hover:text-primary-500 dark:group-hover:text-primary-600">
                                            <span class="material-symbols-outlined">
                                            login
                                            </span>
                                        </x-button>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="block my-6 px-2">
                                <div class="flex gap-2 bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all ease-in-out duration-200 shadow-sm group p-2 rounded-xl">
                                    <div class="flex flex-shrink-0">
                                        <x-avatar src="https://placehold.co/128" size="w-20 h-20" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                                    </div>
                                    <div class="flex flex-col w-full">
                                        <h4 class="text-md font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">
                                            Name
                                        </h4>
                                        <div class="my-1">
                                            <h3 class="text-sm truncate font-medium">
                                               Title
                                            </h3>
                                            <h5 class="text-xs tracking-tight uppercase">Track</h5>
                                            <div class="text-xs font-medium">
                                                <div>
                                                    Starts in: <span>countdownText</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end items-start">
                                        <x-button flat color="secondary" xs class="group-hover:text-primary-500 dark:group-hover:text-primary-600">
                                            <span class="material-symbols-outlined">
                                            login
                                            </span>
                                        </x-button>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="h-1/2 w-full flex bg-green-500">
        <div class="w-1/2 bg-blue-500">
            <div class="w-full max-w-md items-center p-4">
                <h3 class="text-xl font-serif mb-6 text-on-surface-strong-800 dark:text-on-surface-strong-100">Currently Live Rooms</h3>
                @if ($rooms->isEmpty())
                    <div>No available rooms. Create one!</div>
                @else
                    @foreach($rooms as $room)
                        <a href="{{ route('rooms.show', $room) }}" class="block my-6">
                            <div wire:key="{{ $room->id }}" shadow="sm" rounded="xl" color class="flex gap-2 bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all ease-in-out duration-200 shadow-sm group p-2 rounded-xl">
                                <div class="flex flex-shrink-0">
                                    <x-avatar src="{{ $room->track->media->artwork_url ?? 'https://placehold.co/128'}}" size="w-20 h-20" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                                </div>
                                <div class="flex flex-col w-full">
                                    <h4 class="text-md font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">
                                        {{ $room->name }}
                                    </h4>
                                    <div class="my-1">
                                        <h3 class="text-sm truncate font-medium">
                                            {{ $room->track->title ?? 'No Title Available' }}
                                        </h3>
                                        <h5 class="text-xs tracking-tight uppercase">{{ $room->media->title }}</h5>
                                        <div class="text-xs font-medium" x-data="{
                                            startTime: '{{ $room->start_time->timestamp }}',
                                            countdownText: '',
                                            isLive: {{ $room->start_time->isPast() && $room->is_active ? 'true' : 'false' }},
                                            updateCountdown() {
                                                const now = Math.floor(Date.now() / 1000);
                                                const timeUntilStart = this.startTime - now;

                                                if (timeUntilStart < 0) {
                                                    this.isLive = true;
                                                } else {
                                                    const days = Math.floor(timeUntilStart / 86400);
                                                    const hours = Math.floor((timeUntilStart % 86400) / 3600);
                                                    const minutes = Math.floor((timeUntilStart % 3600) / 60);
                                                    const seconds = timeUntilStart % 60;
                                                    this.countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                                                }
                                            }
                                        }" x-init="updateCountdown(); setInterval(() => updateCountdown(), 1000);">
                                            <div x-show="isLive">
                                                <x-badge flat primary label="Live" color="text-rose-500 bg-rose-200/75 rounded-xl">
                                                    <x-slot name="prepend" class="relative flex items-center w-2 h-2">
                                                        <span
                                                            class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-rose-500 animate-ping"></span>

                                                        <span class="relative inline-flex w-2 h-2 rounded-full bg-rose-500"></span>
                                                    </x-slot>
                                                </x-badge>
                                            </div>
                                            <div x-show="!isLive">
                                                Starts in: <span x-text="countdownText"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end items-start">
                                    <x-button flat color="secondary" xs class="group-hover:text-primary-500 dark:group-hover:text-primary-600">
                                        <span class="material-symbols-outlined">
                                        login
                                        </span>
                                    </x-button>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
        <div class="w-1/2 bg-blue-500">
            <div class="w-full max-w-md items-center p-4">
                <h3 class="text-xl font-serif mb-6 text-on-surface-strong-800 dark:text-on-surface-strong-100">Upcoming Rooms</h3>
                @if ($rooms->isEmpty())
                    <div>No available rooms. Create one!</div>
                @else
                    @foreach($rooms as $room)
                        <a href="{{ route('rooms.show', $room) }}" class="block my-6">
                            <div wire:key="{{ $room->id }}" shadow="sm" rounded="xl" color class="flex gap-2 bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950 hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all ease-in-out duration-200 shadow-sm group p-2 rounded-xl">
                                <div class="flex flex-shrink-0">
                                    <x-avatar src="{{ $room->track->media->artwork_url ?? 'https://placehold.co/128'}}" size="w-20 h-20" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                                </div>
                                <div class="flex flex-col w-full">
                                    <h4 class="text-md font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">
                                        {{ $room->name }}
                                    </h4>
                                    <div class="my-1">
                                        <h3 class="text-sm truncate font-medium">
                                            {{ $room->track->title ?? 'No Title Available' }}
                                        </h3>
                                        <h5 class="text-xs tracking-tight uppercase">{{ $room->media->title }}</h5>
                                        <div class="text-xs font-medium" x-data="{
                                            startTime: '{{ $room->start_time->timestamp }}',
                                            countdownText: '',
                                            isLive: {{ $room->start_time->isPast() && $room->is_active ? 'true' : 'false' }},
                                            updateCountdown() {
                                                const now = Math.floor(Date.now() / 1000);
                                                const timeUntilStart = this.startTime - now;

                                                if (timeUntilStart < 0) {
                                                    this.isLive = true;
                                                } else {
                                                    const days = Math.floor(timeUntilStart / 86400);
                                                    const hours = Math.floor((timeUntilStart % 86400) / 3600);
                                                    const minutes = Math.floor((timeUntilStart % 3600) / 60);
                                                    const seconds = timeUntilStart % 60;
                                                    this.countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                                                }
                                            }
                                        }" x-init="updateCountdown(); setInterval(() => updateCountdown(), 1000);">
                                            <div x-show="isLive">
                                                <x-badge flat primary label="Live" color="text-rose-500 bg-rose-200/75 rounded-xl">
                                                    <x-slot name="prepend" class="relative flex items-center w-2 h-2">
                                                        <span
                                                            class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-rose-500 animate-ping"></span>

                                                        <span class="relative inline-flex w-2 h-2 rounded-full bg-rose-500"></span>
                                                    </x-slot>
                                                </x-badge>
                                            </div>
                                            <div x-show="!isLive">
                                                Starts in: <span x-text="countdownText"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end items-start">
                                    <x-button flat color="secondary" xs class="group-hover:text-primary-500 dark:group-hover:text-primary-600">
                                        <span class="material-symbols-outlined">
                                        login
                                        </span>
                                    </x-button>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
