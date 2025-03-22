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
        return [
            'rooms' => Room::where('is_active', true)->orderBy('start_time', 'asc')->with('track.media')->get(),
        ];
    }
}; ?>

<div class="flex flex-col min-h-screen p-8">
    <!-- Create Room -->
    <div class="flex items-center justify-center p-4">
        <div class="w-full max-w-lg">
            <x-card shadow="lg" rounded="xl" color class="bg-surface-alt-100 dark:bg-surface-alt-950 outline outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950">
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
            <h3 class="text-xl font-serif mb-6 text-on-surface-strong-800 dark:text-on-surface-strong-100">Ongoing Listening Parties</h3>
            <div class="rounded-xl bg-surface-alt-100">
                @if ($rooms->isEmpty())
                    <div>No available rooms. Create one!</div>
                @else
                    @foreach($rooms as $room)
                        <div wire:key="{{ $room->id }}">
                            <a href="{{ route('rooms.show', $room) }}" class="block">
                                <div class="flex items-center justify-between rounded-xl py-4 p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700 hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600 transition-all ease-in-out duration-200 shadow-sm">
                                    <div class="flex space-x-6">
                                        <div class="flex-shrink-0">
                                            <x-avatar src="{{ $room->track->media->artwork_url }}" size="w-32 h-32" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                                        </div>
                                        <div class="flex flex-col flex-1 min-w-0 justify-around">
                                            <p class="text-md font-semibold text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">{{ $room->name }}</p>
                                            <div class="flex flex-col gap-1">
                                                <p class="text-md text-pretty">{{ $room->track->title }}</p>
                                                <p class="text-sm tracking-tight uppercase text-pretty">{{ $room->media->title }}</p>
                                            </div>
                                                <p class="text-xs">{{ $room->start_time }}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
