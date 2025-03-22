<?php

use Livewire\Volt\Component;
use App\Models\Room;
use App\Models\Track;
use Livewire\Attributes\Validate;

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

        return redirect()->route('rooms.show', $room);
    }

    public function with()
    {
        return [
            'rooms' => Room::all(),
        ];
    }
}; ?>

<div class="flex items-center justify-center min-h-screen bg-surface-50 dark:bg-surface-950">
    <div class="max-w-lg w-full px-4">
        <form wire:submit='createRoom' class="space-y-6">
            <x-input wire:model='name' placeholder="Listening Party Name" />
            <x-input wire:model='mediaUrl' placeholder="Media URL" description="Direct Episode Link or YouTube Link, RSS Feeds will grab the latest episode."/>
            <x-datetime-picker wire:model='startTime' placeholder="Listening Party Time" />
            <x-button type='submit' primary>Create Room</x-button>
        </form>
    </div>
</div>
