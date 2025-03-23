<?php

use Illuminate\Http\RedirectResponse;
use Livewire\Volt\Component;
use App\Models\Room;
use App\Models\Message;
use App\Events\NewMessageEvent;

new class extends Component {
    public Room $room;
    public bool $isFinished = false;
    public string $message = '';

    protected array $rules = [
        'message' => 'required|string|max:255',
    ];

    public function authenticateUser(): RedirectResponse
    {
        session()->put('auth_redirect', route('rooms.show', $this->room));
        return redirect()->route('login');
    }

    public function sendMessage(): void
    {
        $this->validate();

        $this->room->messages()->create([
            'user_id' => auth()->id(),
            'message' => $this->message,
        ]);

        event(new NewMessageEvent($this->room->id, $this->message));

        $this->message = '';
    }

    public function getListeners(): array
    {
        return [
            'echo:room.{room.id},.new-message' => 'refresh',
        ];
    }

    public function mount(Room $room): void
    {
        if (!$room->is_active) {
            $this->isFinished = true;
        }
        $this->room = $room->load('track.media');
    }

    public function with(): array
    {
        return [
            'messages' => $this->room->messages()->with('user')->orderBy('created_at', 'asc')->get(),
        ];
    }
}; ?>

<div x-data="{
        audio: null,
        isLoading: true,
        isLive: false,
        isPlaying: false,
        isReady: false,
        currentTime: 0,
        audioMetadataLoaded: false,
        startTimestamp: {{ $room->start_time->timestamp }},
        endTimestamp: {{ $room->end_time ? $room->end_time->timestamp : 'null' }},
        countdownText: '',
        copyNotification: false,

        init() {
            this.startCountdown();
            if (this.$refs.audioPlayer && !this.isFinished) {
                this.initializeAudioPlayer();
            }
        },

        initializeAudioPlayer() {
            this.audio = this.$refs.audioPlayer;
            this.audio.addEventListener('loadedmetadata', () => {
                this.isLoading = false;
                this.audioMetadataLoaded = true;
                this.checkLiveStatus();
            });

            this.audio.addEventListener('timeupdate', () => {
                this.currentTime = this.audio.currentTime;
                if (this.endTimestamp && this.currentTime >= (this.endTimestamp - this.startTimestamp)) {
                    this.endRoom();
                }
            });

            this.audio.addEventListener('play', () => {
                this.isPlaying = true;
                this.isReady = true;
            });

            this.audio.addEventListener('pause', () => {
                this.isPlaying = false;
            });

            this.audio.addEventListener('ended', () => {
                this.endRoom();
            });
        },

        startCountdown() {
            this.checkLiveStatus();
            setInterval(() => this.checkLiveStatus(), 1000);
        },

        checkLiveStatus() {
            const now = Math.floor(Date.now() / 1000);
            const timeUntilStart = this.startTimestamp - now;

            if (timeUntilStart <= 0) {
                this.isLive = true;
                if (this.audio && !this.isPlaying && !this.isFinished) {
                    this.playAudio();
                }
            } else {
                const days = Math.floor(timeUntilStart / 86400);
                const hours = Math.floor((timeUntilStart % 86400) / 3600);
                const minutes = Math.floor((timeUntilStart % 3600) / 60);
                const seconds = timeUntilStart % 60;
                this.countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            }
        },

        playAudio() {
            if (!this.audio) return;
            const now = Math.floor(Date.now() / 1000);
            const elapsedTime = Math.max(0, now - this.startTimestamp);
            this.audio.currentTime = elapsedTime;
            this.audio.play().catch(error => {
                console.error('Playback failed: ', error);
                this.isPlaying = false;
                this.isReady = false;
            });
        },

        confirmReady() {
            this.isReady = true;
            if (this.isLive && this.audio && !this.isFinished) {
                this.playAudio();
            }
        },

        formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2,'0')}`;
        },

        endRoom() {
            $wire.isFinished = true;
            $wire.$refresh();
            this.isPlaying = false;
            if (this.audio) {
                this.audio.pause();
            }
        },

        copyToClipboard() {
            navigator.clipboard.writeText(window.location.href);
            this.copyNotification = true;
            setTimeout(() => this.copyNotification = false, 2000);
        },

    }" x-init="init()" class="bg-surface-50 dark:bg-surface-95">
    @if($room->end_time === null)
        <div wire:poll.5s class="flex items-center justify-center min-h-screen p-4 bg-surface-50 dark:bg-surface-950" x-cloak>
            <div class="w-full max-w-xl shadow-sm bg-surface-alt-100 items-center justify-between rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700">
                <div class="flex space-x-4">
                    <div class="flex items-center py-4 gap-8 w-full">
                        <x-badge class="bg-transparent text-on-primary-100">
                            <x-slot name="prepend" class="relative flex items-center w-20 h-20">
                                <span class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-primary-500 dark:bg-primary-600 animate-ping"></span>
                                <span class="relative inline-flex w-20 h-20 rounded-full bg-primary-500 dark:bg-primary-600 justify-center items-center">
                                    <span class="material-symbols-outlined icon-big text-on-primary"> handshake </span>
                                </span>
                            </x-slot>
                        </x-badge>
                        <div>
                            <h4 class="text-md">Creating your room: <span class="font-serif">{{ $room->name }}</span> </h4>
                            <p class="text-xs">Just a moment it is being put together right now...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($isFinished)
        <div x-cloak class="flex items-center justify-center min-h-screen p-4 bg-surface-50 dark:bg-surface-950">
            <div class="w-full max-w-xl shadow-sm bg-surface-alt-100 items-center justify-between rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700">
                <div class="flex space-x-4">
                    <div class="flex items-center py-4 gap-8 w-full">
                        <x-badge flat class="bg-transparent text-on-negative">
                            <x-slot name="prepend" class="relative flex items-center w-20 h-20">
                                <span class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-negative dark:bg-negative"></span>
                                <span class="relative inline-flex w-20 h-20 rounded-full bg-negative dark:bg-negative justify-center items-center">
                                    <span class="material-symbols-outlined icon-big text-on-negative"> error </span>
                                </span>
                            </x-slot>
                        </x-badge>
                        <div>
                            <h4 class="text-md">Playlist finished!</h4>
                            <p class="text-xs">Sorry, the room you are looking for is not available anymore!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <audio x-ref="audioPlayer" :src="'{{ $room->track->media_url }}'" preload="auto"></audio>
        <div x-show="!isLive" class="flex min-h-screen p-4 gap-4 bg-surface-50 dark:bg-surface-950" x-cloak>
            <div class="w-full max-w-3xl shadow-sm bg-surface-alt-100 items-center justify-between rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700  transition-all ease-in-out duration-200 group" x-bind:class="isReady ? '' : 'hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600'">
                <div class="flex space-x-4">
                    <div class="flex items-center justify-between w-full space-x-6">
                        <div class="flex-shrink-0">
                            <x-avatar src="{{ $room->track->media->artwork_url ?? 'https://placehold.co/128'}}" size="w-32 h-32" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                        </div>
                        <div class="flex flex-col flex-1 min-w-0 justify-around">
                            <p class="text-lg font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">{{ $room->name }}</p>
                            <div class="flex flex-col gap-1">
                                <p class="text-md truncate max-w-xs">{{ $room->track->title ?? 'No Title Available' }}</p>
                                <p class="text-sm tracking-tight uppercase">{{ $room->media->title }}</p>
                            </div>
                        </div>

                        <div class="relative z-20 flex justify-start items-center">
                            <button @click="copyToClipboard();" class="flex items-center justify-center w-auto h-8 px-3 py-1 text-xs bg-surface-50 dark:bg-surface-950 border rounded-xl cursor-pointer border-neutral-200/60 hover:bg-white active:bg-white focus:bg-white focus:outline-none text-neutral-500 hover:text-neutral-600 group">
                                <span x-show="!copyNotification">Copy Room Link</span>
                                <svg x-show="!copyNotification" class="w-4 h-4 ml-1.5 stroke-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/> </svg>
                                <span x-show="copyNotification" class="tracking-tight text-green-500"
                                      x-cloak>Copied</span>
                                <svg x-show="copyNotification" class="w-4 h-4 ml-1.5 text-green-500 stroke-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" x-cloak> <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75"/> </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="text-center text-md font-serif gap-2">
                    <p>The show will start in: <span x-text="countdownText"></span></p>
                </div>
                <div class="flex w-full mt-4">
                    <x-button x-show="!isReady" class="w-full" @click="confirmReady()">I allow audio and I'm ready</x-button>
                    <h3 x-show="isReady" class="w-full text-sm text-center border-2 border-positive bg-positive text-on-surface-positive rounded-xl py-1.5 pointer-events-none">
                        You're ready for the show! Stay tuned!
                    </h3>
                </div>
            </div>
            <div class="flex flex-col gap-4 w-full max-h-screen shadow-sm bg-surface-alt-100 items-center justify-between rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700  transition-all ease-in-out duration-200 group hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600">
                <div class="flex flex-col w-full max-h-screen gap-4">
                    <div class="flex flex-col justify-end flex-1 p-4 bg-white w-full rounded-xl overflow-y-auto" id="message-container">
                        <div class="space-y-1">
                            @foreach ($messages as $message)
                                <div class="px-2 py-2 rounded hover:bg-slate-100">
                                    <div class="flex items-center">
                                        <x-avatar xs label="{{ strtoupper(substr($message->user->name, 0, 1)) }}"/>
                                        <div class="flex items-center ml-2 space-x-2">
                                            <span class="text-xs font-bold text-slate-900">{{ $message->user->name }}:</span>
                                            <p class="text-sm text-slate-700">{{ $message->message }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex w-full gap-4">
                        @auth
                            <form class="flex space-x-2" wire:submit='sendMessage'>
                                <input type="text" placeholder="Type your message..." wire:model='message' class="flex-1 px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <x-button primary label="Send" type="submit"/>
                            </form>
                        @else
                            <x-button wire:click="authenticateUser" label="Login to Chat" class="w-full"/>
                        @endauth
                    </div>
                </div>
            </div>
            <div x-show="isLive"
                 class="flex items-center justify-center min-h-screen p-4 bg-surface-50 dark:bg-surface-950" x-cloak>
                <div class="w-full max-w-3xl shadow-sm bg-surface-alt-100 items-center justify-between rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700  transition-all ease-in-out duration-200 group" x-bind:class="isReady ? '' : 'hover:outline-offset-0 hover:outline-primary-500 dark:hover:outline-primary-600'">
                    <div class="flex space-x-4">
                        <div class="flex items-center justify-between w-full space-x-6">
                            <div class="flex-shrink-0">
                                <x-avatar src="{{ $room->track->media->artwork_url ?? 'https://placehold.co/128'}}" size="w-32 h-32" icon-size="2xl" rounded="xl" alt="Media Artwork" class="border-none"/>
                            </div>
                            <div class="flex flex-col flex-1 min-w-0 justify-around">
                                <p class="text-lg font-serif text-on-surface-strong-800 dark:text-on-surface-strong-100 truncate">{{ $room->name }}</p>
                                <div class="flex flex-col gap-1">
                                    <p class="text-md truncate max-w-xs">{{ $room->track->title ?? 'No Title Available' }}</p>
                                    <p class="text-sm tracking-tight uppercase">{{ $room->media->title }}</p>
                                </div>
                            </div>

                            <div class="relative z-20 flex justify-start items-center">
                                <button @click="copyToClipboard();" class="flex flex-col items-center justify-center h-auto px-3 w-16 pt-2 font-medium pb-1.5 text-[0.65rem] uppercase bg-surface-50 dark:bg-surface-950 rounded-xl cursor-pointer border border-neutral-200/60 hover:bg-white active:bg-white focus:bg-white focus:outline-none text-neutral-500 hover:text-neutral-600 group">
                                    <svg x-show="!copyNotification" class="flex-shrink-0 w-5 h-5 mb-1 stroke-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/> </svg>
                                    <span x-show="!copyNotification">Copy</span>
                                    <svg x-show="copyNotification" class="flex-shrink-0 w-5 h-5 mb-1 text-green-500 stroke-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" x-cloak> <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75"/> </svg>
                                    <span x-show="copyNotification" class="tracking-tight text-green-500" x-cloak>Copied</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex w-full mt-4">
                        <x-button x-show="!isReady" class="w-full" @click="confirmReady()">I allow audio and I'm ready</x-button>
                        <div x-show="isReady && !isLoading" class="bg-red-500 w-full">
                            <div class="flex items-center justify-between w-full">
                                <span class="text-sm" x-text="formatTime(currentTime)"></span>
                                <span class="text-sm">
                                    @php
                                        $duration = $room->start_time->diffInSeconds($room->end_time);
                                        $minutes = floor($duration / 60);
                                        $seconds = $duration % 60;
                                    @endphp
                                    {{ sprintf('%02d:%02d', $minutes, $seconds) }}
                                </span>
                            </div>
                            <div class="h-2 rounded-xl bg-secondary-600/20 dark:bg-secondary-500/20">
                                <div class="h-2 rounded-xl bg-secondary-600 dark:bg-secondary-500" :style="`width: ${currentTime / (endTimestamp - startTimestamp) * 100}%`"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
