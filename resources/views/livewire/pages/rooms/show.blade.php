<?php

use Livewire\Volt\Component;
use App\Models\Room;

new class extends Component {
    public Room $room;

    public function mount(Room $room)
    {
        $this->room = $room;
    }

}; ?>

<div>
    {{ $room->name }}
</div>
