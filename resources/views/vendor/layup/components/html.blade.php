<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="{{ \Crumbls\Layup\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }}" style="{{ \App\Layup\Support\StyleHelper::buildInlineStyles($data) }}" {!! \Crumbls\Layup\View\BaseView::animationAttributes($data) !!}>
    @if(config('layup.allow_raw_html', true))
        {!! $data['content'] ?? '' !!}
    @else
        {{ $data['content'] ?? '' }}
    @endif
</div>
