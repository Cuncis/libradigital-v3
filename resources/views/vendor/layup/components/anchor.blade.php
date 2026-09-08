@if(!empty($data['anchor_id']))
<div id="{{ $data['anchor_id'] }}" @if(($data['offset'] ?? 0) != 0) style="scroll-margin-top: {{ abs((int)$data['offset']) }}px" @endif @if(empty($data['invisible'])) class="{{ $data['class'] ?? '' }}" @endif></div>
@endif
