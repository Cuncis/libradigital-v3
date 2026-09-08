{{-- Same rendering as the built-in Timeline widget — this preset only
     changes the type/label/icon and default placeholder content. --}}
@include('layup::components.timeline', ['data' => $data, 'children' => $children ?? []])
