<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="{{ \Crumbls\Layup\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }}" style="{{ \Crumbls\Layup\View\BaseView::buildInlineStyles($data) }}" {!! \Crumbls\Layup\View\BaseView::animationAttributes($data) !!}>
    @if(!empty($data['cover']))
        <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('layup.uploads.disk', 'public'))->url($data['cover']) }}" alt="{{ $data['title'] ?? '' }}" class="w-full h-auto rounded mb-3" />
    @endif
    @if(!empty($data['title']))
        <p class="font-semibold">{{ $data['title'] }}</p>
    @endif
    @if(!empty($data['artist']))
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $data['artist'] }}</p>
    @endif
    @php $src = !empty($data['url']) ? $data['url'] : (!empty($data['file']) ? \Illuminate\Support\Facades\Storage::disk(config('layup.uploads.disk', 'public'))->url($data['file']) : ''); @endphp
    @if($src)
        <audio controls class="w-full" preload="metadata">
            <source src="{{ $src }}"></audio>
    @endif
</div>
