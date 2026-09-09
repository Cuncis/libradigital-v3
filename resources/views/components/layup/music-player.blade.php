@php
    $vis = \Crumbls\Layup\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    // audio_file is a Media Library record id (see MusicPlayerWidget), not
    // a storage path.
    $audioUrl = !empty($data['audio_file'])
        ? \App\Models\Media::query()->find($data['audio_file'])?->url
        : null;
@endphp
@if ($audioUrl)
    <div
        @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
        x-data="{
            playing: false,
            toggle() {
                this.playing ? this.$refs.audio.pause() : this.$refs.audio.play();
                this.playing = !this.playing;
            },
        }"
        x-init="
            if ({{ !empty($data['autoplay']) ? 'true' : 'false' }}) {
                $refs.audio.play().then(() => playing = true).catch(() => {});
            }
        "
        class="fixed bottom-5 right-5 z-50 {{ $vis }} {{ $data['class'] ?? '' }}"
        style="{{ \Crumbls\Layup\View\BaseView::buildInlineStyles($data) }}"
    >
        <audio x-ref="audio" src="{{ $audioUrl }}" loop preload="none"></audio>

        <button
            type="button"
            @click="toggle()"
            class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-900/80 text-white shadow-lg backdrop-blur hover:bg-gray-900"
            aria-label="Toggle background music"
        >
            <svg x-show="!playing" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                <path d="M9 18V5l12-2v13" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                <circle cx="6" cy="18" r="3" stroke="currentColor" stroke-width="1.5" fill="none" />
                <circle cx="18" cy="16" r="3" stroke="currentColor" stroke-width="1.5" fill="none" />
            </svg>
            <svg x-show="playing" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6v12M15 6v12" />
            </svg>
        </button>
    </div>
@endif
