@php
    $vis = \Crumbls\Layup\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $anim = \Crumbls\Layup\View\BaseView::animationAttributes($data);
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="prose max-w-none {{ $vis }} {{ $data['class'] ?? '' }}" style="{{ \App\Layup\Support\StyleHelper::buildInlineStyles($data) }}" {!! $anim !!}>
    {!! $data['content'] ?? '' !!}
</div>
