@php $vis = \Crumbls\Layup\View\BaseView::visibilityClasses($data['hide_on'] ?? []); @endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="{{ $vis }} {{ $data['class'] ?? '' }} max-w-md mx-auto"
     style="{{ \Crumbls\Layup\View\BaseView::buildInlineStyles($data) }}"
     {!! \Crumbls\Layup\View\BaseView::animationAttributes($data) !!}
>
    @if(!empty($data['title']))
        <h3 class="text-lg font-semibold mb-4 text-center">{{ $data['title'] }}</h3>
    @endif

    @isset($layupPage)
        @if (session('rsvp_submitted'))
            <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                Thanks for your RSVP!
            </p>
        @else
            <form method="POST" action="{{ route('rsvps.store', ['invitation' => $layupPage->slug]) }}" class="space-y-3">
                @csrf

                <input
                    type="text"
                    name="name"
                    placeholder="Your name"
                    required
                    class="w-full rounded-lg border-gray-300 text-sm"
                />

                <select name="attending" required class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="attending">Attending</option>
                    <option value="maybe">Maybe</option>
                    <option value="not_attending">Not attending</option>
                </select>

                <input
                    type="number"
                    name="party_size"
                    min="1"
                    max="20"
                    value="1"
                    placeholder="Number of guests"
                    class="w-full rounded-lg border-gray-300 text-sm"
                />

                <textarea
                    name="message"
                    rows="3"
                    placeholder="Message (optional)"
                    class="w-full rounded-lg border-gray-300 text-sm"
                ></textarea>

                @error('name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @error('attending')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                <button
                    type="submit"
                    class="w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                >
                    {{ $data['submit_label'] ?? 'Send RSVP' }}
                </button>
            </form>
        @endif
    @else
        <p class="text-center text-sm text-gray-400">RSVP form (preview — connects to a live invitation once published)</p>
    @endisset
</div>
