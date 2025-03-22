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
            ->orderBy('start_time', 'asc')
            ->with('track.media')
            ->get();

        return ['rooms' => $rooms];
    }
}; ?>

<div class="flex flex-col min-h-screen p-8">
    <!-- Create Room -->
    <div class="flex items-center justify-center p-4">
        <div class="w-full max-w-lg">
            <x-card shadow="sm" rounded="xl" color class="bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-2 outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950">
                <h2 class="text-2xl font-bold font-serif text-center text-on-surface-strong-800 dark:text-on-surface-strong-100">Let's listen together!</h2>
                <form wire:submit='createRoom' class="space-y-6 mt-6">
                    <x-input wire:model='name' placeholder="Listening Party Name" rounded="xl" />
                    <x-input wire:model='mediaUrl' placeholder="Media URL" rounded="xl" description="Direct Episode Link or YouTube Link, RSS Feeds will grab the latest episode." />
                    <x-datetime-picker wire:model='startTime' placeholder="Listening Party Time" rounded="xl" :min="now()->subDays(1)" />
                    <x-button type='submit' primary rounded="xl" class="w-full rounded-xl">Create Room</x-button>
                </form>
            </x-card>
        </div>
    </div>
    <!-- Existing Rooms -->
    <div class="my-20">
        <div class="max-w-lg mx-auto">
            <h3 class="text-xl font-serif mb-6 text-on-surface-strong-800 dark:text-on-surface-strong-100">Listening Parties</h3>
            <div class="rounded-xl space-y-6">
                @if ($rooms->isEmpty())
                    <div>No available rooms. Create one!</div>
                @else
                    @foreach($rooms as $room)
                        <div wire:key="{{ $room->id }}">
                            <a href="{{ route('rooms.show', $room) }}" class="block">
                                <div class="flex space-x-4 bg-surface-alt-100 items-center justify-between rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700 hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all ease-in-out duration-200 shadow-sm group">
                                    <div class="flex space-x-6">
                                        <div class="flex-shrink-0">
                                            <x-avatar src="{{ $room->track->media->artwork_url ?? 'https://placehold.co/128'}}" size="w-32 h-32" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                                        </div>
                                        <div class="flex flex-col flex-1 min-w-0 justify-around">
                                            <p class="text-md font-semibold text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">{{ $room->name }}</p>
                                            <div class="flex flex-col gap-1">
                                                <p class="text-sm truncate max-w-xs">{{ $room->track->title ?? 'No Title Available' }}</p>
                                                <p class="text-xs tracking-tight uppercase">{{ $room->media->title }}</p>
                                            </div>
                                            <div class="text-xs text-pretty" x-data="{
                                                startTime: '{{ $room->start_time->toIso8601String() }}',
                                                countdownText: '',
                                                isLive: {{ $room->start_time->isPast() && $room->is_active ? 'true' : 'false' }},
                                                updateCountdown() {
                                                    const start = new Date(this.startTime).getTime();
                                                    const now = new Date().getTime();
                                                    const remaining = start - now;

                                                    if (remaining < 0) {
                                                        this.countdownText = 'Started';
                                                        this.isLive = true;
                                                    } else {
                                                        const days = Math.floor(remaining / (1000 * 60 * 60 * 24));
                                                        const hours = Math.floor((remaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                                        const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
                                                        const seconds = Math.floor((remaining % (1000 * 60)) / 1000);
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
                                    <x-button flat sm class="text-semibold w-20 h-9 group-hover:bg-primary-500 dark:group-hover:bg-primary-600 group-hover:text-on-primary-100">Join</x-button>
                                </div>
                            </a>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
